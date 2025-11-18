<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Filters\V1\TicketFilter;
use App\Http\Requests\Api\V1\EditTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TicketController extends ApisController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the tickets.
     * Admin sees all tickets, regular users see only their tickets.
     */
    public function index()
    {
        $user = Auth::user();

        $query = Ticket::query();

        // Admin sees all tickets; users see only own tickets
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        // Apply custom TicketFilter
        $tickets = (new TicketFilter(request()))
            ->apply($query)
            ->paginate()
            ->appends(request()->query());

        return TicketResource::collection($tickets);
    }

    /**
     * Get tickets by a specific user id.
     */
    public function getTicketsByUser(User $user)
    {
        $authUser = Auth::user();

        // Authorization: only admin or owner can view
        if ($authUser->role !== 'admin' && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get the underlying query builder from the relationship
        $query = $user->tickets()->getQuery(); // <-- important fix

        $tickets = (new TicketFilter(request()))
            ->apply($query)
            ->paginate()
            ->appends(request()->query());

        return TicketResource::collection($tickets);
    }


    /**
     * Get specific ticket of a user.
     */
    public function getSpecificTicketByUser(User $user, Ticket $ticket)
    {
        $authUser = Auth::user();

        // Authorization
        if ($authUser->role !== 'admin' && $authUser->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Ensure ticket belongs to the user
        if ($ticket->user_id !== $user->id) {
            return response()->json(['message' => 'Ticket not found for this user'], 404);
        }

        return new TicketResource($ticket->load('user'));
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request)
    {
        $this->authorize('create', Ticket::class);

        $userId = Auth::id();

        if (Auth::user()->role === 'admin' && $request->input('data.relationships.author.data.id')) {
            try {
                $user = User::findOrFail($request->input('data.relationships.author.data.id'));
                $userId = $user->id;
            } catch (ModelNotFoundException $exception) {
                return $this->ok('User not found!', [
                    "error" => "The provided user id doesn't exist"
                ]);
            }
        }

        $model = [
            'title' => $request->input('data.attributes.title'),
            'description' => $request->input('data.attributes.description'),
            'status' => $request->input('data.attributes.status'),
            'user_id' => $userId,
        ];

        return new TicketResource(Ticket::create($model));
    }

    /**
     * Display a specific ticket.
     */
    public function show($ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);
            $this->authorize('view', $ticket);

            if ($this->include('author')) {
                return new TicketResource($ticket->load('user'));
            }

            return new TicketResource($ticket);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Ticket not found');
        }
    }

    /**
     * Edit a ticket.
     */
    public function edit(EditTicketRequest $request, $ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);
            $this->authorize('update', $ticket);

            $model = [
                'title' => $request->input('data.attributes.title'),
                'description' => $request->input('data.attributes.description'),
                'status' => $request->input('data.attributes.status'),
            ];

            if (Auth::user()->role === 'admin' && $request->input('data.relationships.author.data.id')) {
                $model['user_id'] = $request->input('data.relationships.author.data.id');
            }

            $ticket->update($model);

            return new TicketResource($ticket);
        } catch (ModelNotFoundException $exception) {
            return $this->ok('Ticket not found!', [
                "error" => "The provided ticket id doesn't exist"
            ]);
        }
    }

    /**
     * Update a ticket partially.
     */
    public function update(UpdateTicketRequest $request, $ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);
            $this->authorize('update', $ticket);

            $attributes = $request->input('data.attributes', []);
            $model = [];

            if (isset($attributes['title'])) $model['title'] = $attributes['title'];
            if (isset($attributes['description'])) $model['description'] = $attributes['description'];
            if (isset($attributes['status'])) $model['status'] = $attributes['status'];

            if (Auth::user()->role === 'admin' && $request->input('data.relationships.author.data.id')) {
                $model['user_id'] = $request->input('data.relationships.author.data.id');
            }

            if (!empty($model)) $ticket->update($model);

            return new TicketResource($ticket);
        } catch (ModelNotFoundException $exception) {
            return $this->ok('Ticket not found!', [
                "error" => "The provided ticket id doesn't exist"
            ]);
        }
    }

    /**
     * Delete a ticket.
     */
    public function destroy($ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);
            $this->authorize('delete', $ticket);
            $ticket->delete();

            return $this->ok('Ticket Successfully Deleted.');
        } catch (ModelNotFoundException $exception) {
            return $this->error('Ticket not found');
        }
    }
}
