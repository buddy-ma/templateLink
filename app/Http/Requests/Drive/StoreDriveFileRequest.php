<?php

declare(strict_types=1);

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreDriveFileRequest extends FormRequest
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
        // ClamAV scanning is disabled for now (Windows/local lacks ext-sockets / daemon).
        // Re-enable with the `clamav` rule when CLAMAV_SKIP_VALIDATION=false and ClamAV is available.
        return [
            'file' => [
                'required',
                File::defaults()
                    ->max(100 * 1024) // 100 MB
                    ->types([
                        'pdf',
                        'doc',
                        'docx',
                        'xls',
                        'xlsx',
                        'ppt',
                        'pptx',
                        'txt',
                        'csv',
                        'png',
                        'jpg',
                        'jpeg',
                        'gif',
                        'webp',
                        'svg',
                        'zip',
                        'mp4',
                        'mov',
                    ]),
            ],
            'folder_id' => ['nullable', 'integer', 'exists:drive_folders,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
