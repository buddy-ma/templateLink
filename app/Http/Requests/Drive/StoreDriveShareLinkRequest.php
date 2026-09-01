<?php

declare(strict_types=1);

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriveShareLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => ['nullable', 'string', 'min:4', 'max:128'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
