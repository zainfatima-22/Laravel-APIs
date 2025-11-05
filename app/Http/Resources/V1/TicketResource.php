<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public static $wrap = "ticket";
    public function toArray(Request $request): array
    {
        return [
            'type' => 'ticket',
            'id' => $this->id,
            'attributes' => [
                'title' => $this->title,
                'description' => $this->when(
                    !$request->routeIs('ticket.index'),
                    $this->description
                ),
                'status' => $this->status,
                'created at' => $this->created_at,
                'updated at' => $this->updated_at
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'user',
                        'id'=> $this->user_id
                    ],
                    /* 'links' => [
                        'self' => route('users.show' , ['users' => $this->user_id])
                    ] */
                ] 
            ],
            'includes' => new UserResource($this->whenLoaded('user')),
            'links' => [
                'self' => route('ticket.show' , ['ticket' => $this->id])
            ]
        ];
    }
}
