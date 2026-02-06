<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Ticket;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket'); 
        return $ticket ? $this->user()->can('update', $ticket) : false;
    }
    public function rules(): array
    {
        return [
            'data.attributes.title' => 'sometimes|string|max:255',
            'data.attributes.description' => 'sometimes|string',
            'data.attributes.status' => 'sometimes|in:open,completed,pending,cancelled',
            'data.relationships.author.data.id' => 'sometimes|integer|exists:users,id',
        ];
    }
    public function messages(): array
    {
        return [
            'data.attributes.status.in' => 'The status is invalid. Allowed values: open, completed, pending, cancelled.',
            'data.relationships.author.data.id.exists' => 'The selected user does not exist.',
        ];
    }
}
