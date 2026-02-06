<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; 
    }
    public function rules(): array
    {
        $validStatuses = ['open', 'pending', 'completed', 'cancelled'];
        
        return [
            'sort' => [
                'sometimes', 
                'string', 
                Rule::in(['created_at', 'status'])
            ],
            
            'filter.status' => [
                'sometimes', 
                'string', 
                Rule::in($validStatuses) 
            ],
        ];
    }
}