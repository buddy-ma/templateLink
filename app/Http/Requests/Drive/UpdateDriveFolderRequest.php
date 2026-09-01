<?php

declare(strict_types=1);

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriveFolderRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:drive_folders,id'],
        ];
    }
}
