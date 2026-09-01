<?php

declare(strict_types=1);

namespace App\Http\Requests\Demand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
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
        $brandId = $this->route('brand')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('brands', 'sku')->ignore($brandId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
