<?php

declare(strict_types=1);

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriveShareRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
