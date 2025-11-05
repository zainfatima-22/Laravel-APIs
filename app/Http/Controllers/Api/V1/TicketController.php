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
use Illuminate\Support\Facades\Gate;

class TicketController extends ApisController
{
    use AuthorizesRequests;
    public function index(TicketFilter $filters)
    {
        return TicketResource::collection(Ticket::filter($filters)->paginate());
    }
    public function userTickets(TicketFilter $filters)
    {
        $userId = Auth::id();
        $tickets = Ticket::where('user_id', $userId)
                         ->filter($filters)
                         ->paginate();
                         
        return TicketResource::collection($tickets);
    }
    
    public function userTicketsById(TicketFilter $filters, $user_id)
    {
        try {
            User::findOrFail($user_id);
        } catch (ModelNotFoundException $e) {
            return $this->error('User not found.');
        }

        $tickets = Ticket::where('user_id', $user_id)
                         ->filter($filters)
                         ->paginate();
                         
        return TicketResource::collection($tickets);
    }

    /**
     * Store a newly created resource in storage.
     */
/*      public function store(StoreTicketRequest $request)
    {
        try{
            $user = User::findorFail($request->input('data.relationships.author.data.id'));
        }catch(ModelNotFoundException $exception){
            return $this->ok('User not found!', [
                "error" => "The provided user id doesn't exist"
            ]);
        }
        $model = [
            'title' => $request->input('data.attributes.title'),
            'description' => $request->input('data.attributes.description'),
            'status' => $request->input('data.attributes.status'),
            'user_id' => $request->input('data.relationships.author.data.id'),
            'name' => $request->input('data.relationships.author.data.name'),
            'role' => $request->input('data.relationships.author.data.role'),
        ];
        return new TicketResource(Ticket::create($model));
    }  */
    public function store(StoreTicketRequest $request)
    {
        Gate::define('create-ticket', function ($user) {
            return $user->role === 'admin';
        });

        try {
            $user = User::findOrFail($request->input('data.relationships.author.data.id'));
        } catch (ModelNotFoundException $exception) {
            return $this->ok('User not found!', [
                "error" => "The provided user id doesn't exist"
            ]);
        }

        $model = [
            'title' => $request->input('data.attributes.title'),
            'description' => $request->input('data.attributes.description'),
            'status' => $request->input('data.attributes.status'),
            'user_id' => $request->input('data.relationships.author.data.id'),
            'name' => $request->input('data.relationships.author.data.name'),
            'role' => $request->input('data.relationships.author.data.role')
        ];

        return new TicketResource(Ticket::create($model));
    } 


    /**
     * Display the specified resource.
     */
    public function show($ticket_id)
    {
        try{
            $ticket = Ticket::findorFail($ticket_id);
            if ($this->include('author')){
            return new TicketResource($ticket->load('user'));
        } 
        return new TicketResource($ticket);
        }catch(ModelNotFoundException $exception){
            return $this->error('Ticket not found');
        }
        
    }

    /**
     * Show the form for editing the specified resource. PUT
     */
    public function edit(EditTicketRequest $request, $ticket_id)
    {
        try{
            $ticket = Ticket::findorFail($ticket_id);
            $model = [
            'title' => $request->input('data.attributes.title'),
            'description' => $request->input('data.attributes.description'),
            'status' => $request->input('data.attributes.status'),
            'user_id' => $request->input('data.relationships.author.data.id'),
        ];
        $ticket->update($model);
        return new TicketResource($ticket);

        }catch(ModelNotFoundException $exception){
            return $this->ok('Ticket not found!', [
                "error" => "The provided ticket id doesn't exist"
            ]);
        }
    }
    /**
     * Update the specified resource in storage. PATCH
     */
    // app/Http/Controllers/Api/V1/TicketController.php
    public function update(UpdateTicketRequest $request, $ticket_id)
    {
        try{
            $ticket = Ticket::findorFail($ticket_id);
            $this->authorize('update', $ticket);
            $validatedData = $request->validated();
            $model = [];

            if (isset($validatedData['data']['attributes'])) {
                $attributes = $validatedData['data']['attributes'];
                
                if (isset($attributes['title'])) {
                    $model['title'] = $attributes['title'];
                }
                if (isset($attributes['description'])) {
                    $model['description'] = $attributes['description'];
                }
                if (isset($attributes['status'])) {
                    $model['status'] = $attributes['status'];
                }
            }

            if ($request->input('data.relationships.author.data.id')) {
                $model['user_id'] = $request->input('data.relationships.author.data.id');
            }
            if (!empty($model)) {
                $ticket->update($model); 
            }
            return new TicketResource($ticket);

        }catch(ModelNotFoundException $exception){
            return $this->ok('Ticket not found!', [
                "error" => "The provided ticket id doesn't exist"
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($ticket_id)
    {
        try{
            $ticket = Ticket::findorFail($ticket_id);
            $this->authorize('delete', $ticket);
            $ticket->delete();
            return $this->ok('Ticket Successfully Deleted.');
        }catch(ModelNotFoundException $exception){
            return $this->error('Ticket not found');
        }
        
    }
}
