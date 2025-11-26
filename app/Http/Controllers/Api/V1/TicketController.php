<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\DeleteTicketRequest;
use App\Http\Requests\Api\V1\IndexTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\V1\TicketResource;
use App\Jobs\SendTicketCreatedEmailJob;
use App\Models\Ticket;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketController extends ApisController
{
    use ApiResponses, AuthorizesRequests;
    protected array $allowedFilters;
    protected array $allowedSorts;
    public function __construct()
    {
        $this->allowedFilters = [
            AllowedFilter::partial('title'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('user_id'),
        ];
        $this->allowedSorts = [
            'created_at',
            'status',
        ];
    }

    /**
     * List all tickets (admin = all, user = own).
     */
    public function index(IndexTicketRequest $request): AnonymousResourceCollection|JsonResponse
    {
        $authUser = Auth::user();
        $query = Ticket::with('user');

        if (!$authUser->hasRole('admin')) {
            $query->where('user_id', $authUser->id);

            if ($request->input('filter.user_id') && $request->input('filter.user_id') != $authUser->id) {
                return $this->forbidden('You are not authorized to view other users\' tickets.');
            }
        }

        $tickets = $this->buildQuery($query, $request)->paginate()->appends($request->query());

        return $tickets->isEmpty()
            ? $this->notFound('No tickets found.')
            : TicketResource::collection($tickets);
    }

    /**
     * View a single ticket.
     */
    public function show(Ticket $ticket): TicketResource
    {
        $this->authorize('view', [Ticket::class, $ticket]);
        return new TicketResource($ticket->loadMissing('user'));
    }

    /**
     * Create a new ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $authUser = Auth::user();
        $requestedUserId = $request->input('data.relationships.author.data.id');

        try {
            if ($authUser->hasRole('admin')) {
                $targetUserId = $requestedUserId ?? $authUser->id;
                User::findOrFail($targetUserId);
            } else {
                if ($requestedUserId && $requestedUserId != $authUser->id) {
                    throw new AuthorizationException(
                        'You cannot assign tickets to other users.'
                    );
                }
                $targetUserId = $authUser->id;
            }
        } catch (ModelNotFoundException) {
            return $this->notFound('Target user not found.');
        }

        $ticket = Ticket::create(array_merge(
            $request->validated(),
            ['user_id' => $targetUserId]
        ));

        SendTicketCreatedEmailJob::dispatch($ticket->id);

        return $this->created(
            'Ticket created successfully',
            (new TicketResource($ticket->load('user')))->response()->getData(true)
        );
    }
    /**
     * Update a ticket.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
{
    $updateData = $request->validated()['data']['attributes'] ?? [];

    if ($request->filled('data.relationships.author.data.id')) {
        $updateData['user_id'] = $request->input('data.relationships.author.data.id');
    }

    $ticket->update($updateData);

    return new TicketResource($ticket->load('user'));
}
    /**
     * Delete a ticket.
     */
    public function destroy(DeleteTicketRequest $request, Ticket $ticket): JsonResponse
    {
        try {
            $ticket->delete();
            return $this->ok('Ticket successfully deleted');
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket not found.');
        }
    }

    /**
     * Get all tickets for a specific user.
     */
    public function getTicketsByUser(IndexTicketRequest $request, User $user): AnonymousResourceCollection|JsonResponse
    {
        $tickets = $this->buildQuery($user->tickets()->with('user')->getQuery(), $request)
            ->paginate()
            ->appends($request->query());

        return $tickets->isEmpty()
            ? $this->notFound('No tickets found for this user.')
            : TicketResource::collection($tickets);
    }

    /**
     * Get a specific ticket for a specific user.
     */
    public function getSpecificTicketByUser(User $user, Ticket $ticket): TicketResource|JsonResponse
    {
        $this->authorize('viewUserTicket', [Ticket::class, $ticket, $user]);
        return $ticket->user_id !== $user->id
            ? $this->notFound('Ticket not found for this user.')
            : new TicketResource($ticket->loadMissing('user'));
    }

    /**
     * Send batch emails for latest 5 tickets.
     */
    public function sendBatchEmails(): JsonResponse
    {
        $tickets = Ticket::latest()->take(5)->get();

        $jobs = $tickets->map(fn($ticket, $i) =>
            (new SendTicketCreatedEmailJob($ticket->id))
                ->delay(now()->addSeconds($i * 10))
        )->toArray();

        $batch = Bus::batch($jobs)->dispatch();

        return $this->ok('Batch email job started', [
            'batch_id' => $batch->id,
        ]);
    }

    /**
     * Build query using Spatie.
     */
    protected function buildQuery(Builder $query, IndexTicketRequest $request): QueryBuilder
    {
        return QueryBuilder::for($query)
            ->allowedFilters($this->allowedFilters)
            ->allowedSorts($this->allowedSorts);
    }

    /**
     * Resolve target user ID for create/update.
     */
    private function resolveTargetUserId($request): int
    {
        $authUser = Auth::user();
        $requestedUserId = $request->input('data.relationships.author.data.id');

        if ($authUser->hasRole('admin')) {
            $targetUserId = $requestedUserId ?? $authUser->id;
            User::findOrFail($targetUserId);
            return $targetUserId;
        }

        if ($requestedUserId && $requestedUserId != $authUser->id) {
            throw new AuthorizationException('Non-admin users cannot assign tickets to other users.');
        }

        return $authUser->id;
    }
}
