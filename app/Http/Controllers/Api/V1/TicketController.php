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

class TicketController extends ApisController
{
    use AuthorizesRequests, ApiResponses;
    protected array $allowedFilters;
    protected array $allowedSorts;
    public function __construct()
    {
        // Now, initialize the properties inside a method
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

        return TicketResource::collection($tickets);
    }
    /**
     * Get tickets for a specific user (GET /users/{user}/tickets).
     */
    public function getTicketsByUser(User $user)
    {
        $this->authorize('viewUserTickets', [Ticket::class, $user]);
        $query = $user->tickets()->with('user'); 

        $tickets = QueryBuilder::for($query)
            ->allowedFilters($this->allowedFilters)
            ->allowedSorts($this->allowedSorts)
            ->paginate()
            ->appends(request()->query());

        return TicketResource::collection($tickets);
    }

    /**
     * Show a specific ticket.
     */
    public function show(Ticket $ticket) 
    {
        $ticket->load('user');
        $this->authorize('view', $ticket); 

        return new TicketResource($ticket);
    }

    /**
     * Show a specific ticket for a given user (GET /users/{user}/tickets/{ticket}).
     */
    public function getSpecificTicketByUser(User $user, Ticket $ticket)
    {
        $this->authorize('viewUserTicket', [Ticket::class, $ticket, $user]);
        if ($ticket->user_id !== $user->id) {
            throw new ModelNotFoundException('Ticket not found for this user');
        }

        return new TicketResource($ticket->load('user'));
    }

    /**
     * Store a new ticket.
     */
    public function store(StoreTicketRequest $request)
    {
        $authUser = Auth::user();

        $requestedUserId = $request->input('data.relationships.author.data.id')
            ?? $request->input('data.relationships.author.data.user_id');
            
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
        
        $this->authorize('create', Ticket::class); 

        $ticketData = array_merge(
            $request->validated(),
            ['user_id' => $targetUserId]
        );

        $ticket = Ticket::create($ticketData);
        SendTicketCreatedEmailJob::dispatch($ticket)->delay(now()->addSeconds(3));
        return $this->created(
            'Ticket created successfully',
            (new TicketResource($ticket->load('user')))->response()->getData(true)
        );
    }


    /**
     * Update a ticket.
     */
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
            return $this->notFound('Resource or target user not found'); 

        } catch (AuthorizationException $e) {
            return $this->forbidden('Unauthorized to update this ticket.');
        }
    }

    /**
     * Delete a ticket.
     */
    public function destroy(Ticket $ticket) 
    {
        $this->authorize('delete', $ticket); 
        $ticket->delete();
        return $this->ok('Ticket successfully deleted');
    }
}