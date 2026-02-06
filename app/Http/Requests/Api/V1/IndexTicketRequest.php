<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketRequest extends FormRequest
{
    /**
     * Authorize request using policy (viewAny tickets).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', \App\Models\Ticket::class) ?? false;
    }

    /**
     * Validation rules for filtering/sorting tickets.
     */
    public function rules(): array
    {
        $validStatuses = ['open', 'pending', 'completed', 'cancelled'];
        $validSorts = ['created_at', 'status'];

        return [
            'sort' => ['sometimes', 'string', Rule::in($validSorts)],
            'filter.status' => ['sometimes', 'string', Rule::in($validStatuses)],
        ];
    }
}
