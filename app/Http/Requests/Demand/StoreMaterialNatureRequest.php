<?php

declare(strict_types=1);

namespace App\Http\Requests\Demand;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialNatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->can('demands.manage_catalog') || $user->can('demands.create'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:material_natures,name'],
        ];
    }
}
