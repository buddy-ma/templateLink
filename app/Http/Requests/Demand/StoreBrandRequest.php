<?php

declare(strict_types=1);

namespace App\Http\Requests\Demand;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('demands.manage_catalog') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:brands,sku'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
