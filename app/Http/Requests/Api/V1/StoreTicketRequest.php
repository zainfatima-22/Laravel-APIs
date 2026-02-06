<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }
    protected function prepareForValidation()
    {
        $attributes = $this->input('data.attributes', []);
        $relationshipData = $this->input('data.relationships.author.data', []);

        $userId = Auth::id();

        $targetAuthorId = $relationshipData['user_id'] ?? null;

        if (Auth::user()->role === 'admin' && $targetAuthorId) {
            $userId = $targetAuthorId;
        }

        $this->merge([
            'title' => $attributes['title'] ?? null,
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'] ?? 'open',
            'user_id' => $userId,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['nullable', 'string', 'in:open,completed,pending,cancelled'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}