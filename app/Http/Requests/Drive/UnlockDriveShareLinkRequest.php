<?php

declare(strict_types=1);

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class UnlockDriveShareLinkRequest extends FormRequest
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
            'password' => ['required', 'string', 'max:128'],
        ];
    }
}
