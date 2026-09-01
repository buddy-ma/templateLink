<?php

declare(strict_types=1);

namespace App\Http\Requests\Demand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class DemandActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:20000'],
            'reason' => ['nullable', 'string', 'max:20000'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'extensions:pdf,doc,docx,ppt,pptx,jpg,jpeg,png,webp', 'max:20480'],
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function uploadedFiles(): array
    {
        $files = $this->file('files', []);

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(
            $files,
            fn ($file): bool => $file instanceof UploadedFile,
        ));
    }
}
