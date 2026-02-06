<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Ticket;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
       return $this->user()->can('create', Ticket::class);
    }

    protected function prepareForValidation(): void
    {
        $attributes = $this->input('data.attributes', []);
        $relationshipData = $this->input('data.relationships.author.data', []);

        $userId = $this->user()->id;

        if ($this->user()->hasRole('admin')) {
            $userId = $relationshipData['id'] ?? $relationshipData['user_id'] ?? $userId;
        }

        $this->merge([
            'title' => $attributes['title'] ?? null,
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'] ?? 'open',
            'user_id' => $userId,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tickets', 'title')
                    ->where(fn($query) => $query->where('user_id', $this->user_id)),
            ],
            'description' => ['required', 'string'],
            'status' => ['nullable', 'string', 'in:open,completed,pending,cancelled'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The status is invalid. Allowed values: open, completed, pending, cancelled.',
            'user_id.exists' => 'The assigned user does not exist.',
        ];
    }
}
