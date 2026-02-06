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
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;    
use Illuminate\Support\Facades\Bus;

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

    public function sendBatchEmails()
    {
        $tickets = Ticket::latest()->take(5)->get();

        $jobs = $tickets->map(fn($ticket) => new SendTicketCreatedEmailJob($ticket->id))->toArray();

        $batch = Bus::batch($jobs)->dispatch();

        return [
            'message' => 'Batch email job started',
            'batch_id' => $batch->id,
        ];
    }

    public function index(IndexTicketRequest $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $authUser = Auth::user();
        $query = Ticket::query()->with('user'); 

        if (!$authUser->hasRole('admin')) {
            $query->where('user_id', $authUser->id);

            if ($request->has('filter.user_id') && $request->input('filter.user_id') != $authUser->id) {
                 return $this->forbidden('You are not authorized to view other users\' tickets.');
            }
        }

        $tickets = QueryBuilder::for($query)
            ->allowedFilters($this->allowedFilters)
            ->allowedSorts($this->allowedSorts)
            ->allowedIncludes(['user'])
            ->paginate()
            ->appends($request->query());

        if ($tickets->isEmpty()) {
            return $this->notFound('No tickets found.');
        }

        return TicketResource::collection($tickets);
    }

    public function getTicketsByUser(User $user)
    {
        $this->authorize('viewUserTickets', [Ticket::class, $user]);

        $query = $user->tickets()->with('user');

        $tickets = QueryBuilder::for($query)
            ->allowedFilters($this->allowedFilters)
            ->allowedSorts($this->allowedSorts)
            ->paginate()
            ->appends(request()->query());

        if ($tickets->isEmpty()) {
            return $this->notFound('No tickets found for this user.');
        }

        return TicketResource::collection($tickets);
    }

    public function show($ticket_id) 
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id)->load('user'); 
            $this->authorize('view', $ticket); 
            return new TicketResource($ticket);
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Ticket not found.');
        }
    }

    public function getSpecificTicketByUser(User $user, Ticket $ticket)
    {
        try {
            $this->authorize('viewUserTicket', [Ticket::class, $ticket, $user]);
            if ($ticket->user_id !== $user->id) {
                return $this->notFound('Ticket not found for this user.');
            }
            return new TicketResource($ticket->load('user'));
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Ticket not found for this user.');
        }
    }

    public function store(StoreTicketRequest $request)
    {
        $authUser = Auth::user();
        $requestedUserId = $request->input('data.relationships.author.data.id')
            ?? $request->input('data.relationships.author.data.user_id');
        try {
            if ($authUser->hasRole('admin')) {
                $targetUserId = $requestedUserId ?? $authUser->id;

                if ($targetUserId !== $authUser->id) {
                    User::findOrFail($targetUserId);
                }
            } else {
                if ($requestedUserId && $requestedUserId != $authUser->id) {
                    throw new AuthorizationException('Non-admin users cannot assign tickets to other users.');
                }

                $targetUserId = $authUser->id;
            }
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Target user not found.');
        }
        $this->authorize('create', Ticket::class);

        $ticketData = array_merge(
            $request->validated(),
            ['user_id' => $targetUserId]
        );
        $ticket = Ticket::create($ticketData);
        SendTicketCreatedEmailJob::dispatch($ticket->id)
            ->delay(now()->addSeconds(2))
            ->afterCommit();

        return $this->created(
            'Ticket created successfully',
            (new TicketResource($ticket->load('user')))->response()->getData(true)
        );
    }


    public function update(UpdateTicketRequest $request, $ticket_id)
    {
        $authUser = Auth::user();

        try {
            $ticket = Ticket::with('user')->findOrFail($ticket_id);
            $this->authorize('update', $ticket);

            $updateData = $request->input('data.attributes', []); 

            if ($authUser->hasRole('admin')) {
                $newUserId = $request->input('data.relationships.author.data.id');
                if ($newUserId) {
                    $targetUser = User::findOrFail($newUserId);
                    $updateData['user_id'] = $targetUser->id;
                }
            }

            if (!empty($updateData)) {
                $ticket->update($updateData);
            }

            return new TicketResource($ticket->load('user'));

        } catch (ModelNotFoundException $e) {
            return $this->notFound('Ticket or target user not found.');
        } catch (AuthorizationException $e) {
            return $this->forbidden('Unauthorized to update this ticket.');
        }
    }

    public function destroy($ticket_id) 
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id); 
            $this->authorize('delete', $ticket);
            $ticket->delete();

            return $this->ok('Ticket successfully deleted');
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Ticket not found.');
        }
    }
}
