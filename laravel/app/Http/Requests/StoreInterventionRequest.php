<?php

namespace App\Http\Requests;

use App\Models\Intervention;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInterventionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Intervention::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(['academic', 'financial', 'other'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'scheduled_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
