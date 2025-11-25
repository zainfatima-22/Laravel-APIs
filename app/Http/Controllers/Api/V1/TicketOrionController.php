<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\TicketResource;
use Orion\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Policies\TicketPolicy;
use Illuminate\Database\Eloquent\Model;
use Orion\Http\Requests\Request; 
use Symfony\Component\HttpFoundation\Response; 

class TicketOrionController extends Controller
{
    protected $model = Ticket::class;
    protected $policy = TicketPolicy::class;
    protected $resource = TicketResource::class;
    // protected $jsonApi = true; 
    protected $guard = 'sanctum';

    protected $fillable = ['title', 'description', 'status', 'user_id']; 
    protected $shouldReturnDeletedEntity = false;
    protected function beforeStore(Request $request, Model $ticket)
    {
        $attributes = $request->input('data.attributes', []); 
        
        $ticket->title = $attributes['title'] ?? null;
        $ticket->description = $attributes['description'] ?? null;
        $ticket->status = $attributes['status'] ?? 'open'; 
        
        $ticket->user_id = $request->user()->id; 
    }
    
    protected function beforeUpdate(Request $request, Model $ticket)
    {
        $attributes = $request->input('data.attributes', []); 
        
        if (isset($attributes['title'])) $ticket->title = $attributes['title'];
        if (isset($attributes['description'])) $ticket->description = $attributes['description'];
        if (isset($attributes['status'])) $ticket->status = $attributes['status'];
    }

    public function destroy(Request $request, ...$args): Response
    {
        $key = $args[0]; 

        $baseQuery = $this->queryBuilder->buildQuery($this->model::query(), $request);
        $entity = $baseQuery->findOrFail($key);
        $this->authorize('delete', $entity);
        $entity->delete();

        return response()->json([
            'message' => 'Ticket deleted successfully.',
            'deleted_id' => $key 
        ], 200); 
    }
}