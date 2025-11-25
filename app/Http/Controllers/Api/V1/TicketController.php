<?php

namespace App\Http\Controllers\Api\V1;

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
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TicketController extends ApisController
{
    use AuthorizesRequests, ApiResponses;

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
     * Send batch emails for latest 5 tickets.
     */
    public function sendBatchEmails(): JsonResponse
    {
        $this->authorize('sendBatchEmails', Ticket::class);

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
     * List all tickets (admin = all, user = own).
     */
    public function index(IndexTicketRequest $request): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $authUser = Auth::user();
        $query = Ticket::query()->with('user');

        if (!$authUser->hasRole('admin')) {
            // Normal users can only see their own
            $query->where('user_id', $authUser->id);

            if ($request->has('filter.user_id') &&
                $request->input('filter.user_id') != $authUser->id) {
                return $this->forbidden('You are not authorized to view other users\' tickets.');
            }
        }

        $tickets = $this->buildQuery($query, $request)->paginate()->appends($request->query());

        if ($tickets->isEmpty()) {
            return $this->notFound('No tickets found.');
        }

        return TicketResource::collection($tickets);
    }

    /**
     * Get all tickets for a specific user.
     */
    public function getTicketsByUser(IndexTicketRequest $request, User $user): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewUserTickets', [Ticket::class, $user]);

        // your original working query preserved
        $query = $user->tickets()->getQuery()->with('user');

        $tickets = $this->buildQuery($query, $request)->paginate()->appends($request->query());

        if ($tickets->isEmpty()) {
            return $this->notFound('No tickets found for this user.');
        }

        return TicketResource::collection($tickets);
    }

    /**
     * View a single ticket.
     */
    public function show(Ticket $ticket): TicketResource|JsonResponse
    {
        $this->authorize('view', $ticket);

        return new TicketResource($ticket->loadMissing('user'));
    }

    /**
     * Get a specific ticket for a specific user.
     */
    public function getSpecificTicketByUser(User $user, Ticket $ticket): TicketResource|JsonResponse
    {
        $this->authorize('viewUserTicket', [Ticket::class, $ticket, $user]);

        if ($ticket->user_id !== $user->id) {
            return $this->notFound('Ticket not found for this user.');
        }

        return new TicketResource($ticket->loadMissing('user'));
    }

    /**
     * Create a new ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $this->authorize('create', Ticket::class);

        $authUser = Auth::user();
        $requestedUserId = $request->input('data.relationships.author.data.id');

        try {
            if ($authUser->hasRole('admin')) {
                $targetUserId = $requestedUserId ?? $authUser->id;

                if ($targetUserId !== $authUser->id) {
                    User::findOrFail($targetUserId);
                }
            } else {
                if ($requestedUserId && $requestedUserId != $authUser->id) {
                    throw new AuthorizationException(
                        'Non-admin users cannot assign tickets to other users.'
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
    public function update(UpdateTicketRequest $request, $ticket_id): TicketResource|JsonResponse
    {
        try {
            $ticket = Ticket::with('user')->findOrFail($ticket_id);

            $this->authorize('update', $ticket);

            $updateData = $request->input('data.attributes', []);

            if (Auth::user()->hasRole('admin')) {
                $newUserId = $request->input('data.relationships.author.data.id');

                if ($newUserId) {
                    $updateData['user_id'] = User::findOrFail($newUserId)->id;
                }
            }

            $ticket->update($updateData);

            return new TicketResource($ticket->load('user'));
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket or target user not found.');
        } catch (AuthorizationException) {
            return $this->forbidden('Unauthorized to update this ticket.');
        }
    }

    /**
     * Delete a ticket.
     */
    public function destroy($ticket_id): JsonResponse
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);
            $this->authorize('delete', $ticket);

            $ticket->delete();

            return $this->ok('Ticket successfully deleted');
        } catch (ModelNotFoundException) {
            return $this->notFound('Ticket not found.');
        }
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
}
