<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('access_admin');
    }

    public function rules(): array
    {
        return [
            'flat' => ['required', 'array'],
            'flat.*' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $flat = $this->input('flat', []);
            if (! is_array($flat)) {
                return;
            }
            foreach ($flat as $key => $_) {
                if (! is_string($key)) {
                    $v->errors()->add('flat', 'Translation keys must be strings.');

                    return;
                }
                if ($key === '' || ! preg_match('/^[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*$/', $key)) {
                    $v->errors()->add("flat.$key", 'Invalid translation key format.');

                    return;
                }
            }
        });
    }
}
