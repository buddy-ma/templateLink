<?php

declare(strict_types=1);

namespace App\Http\Requests\Demand;

use Illuminate\Foundation\Http\FormRequest;

class UpdateValidationPipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('demands.manage_pipeline') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'validator_ids' => ['required', 'array', 'min:3'],
            'validator_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }
}
