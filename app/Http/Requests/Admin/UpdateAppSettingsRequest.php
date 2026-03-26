<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\FontFamilies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission:access_admin middleware enforces admin-only access
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            // Branding
            'branding.app_name' => ['required', 'string', 'max:120'],
            'branding.primary_color' => ['nullable', 'string', 'max:50'],
            'branding.primary_foreground_color' => ['nullable', 'string', 'max:50'],
            'branding.sidebar_primary_color' => ['nullable', 'string', 'max:50'],
            'branding.font_source' => ['required', 'string', 'in:preset,google,upload'],
            'branding.font_preset' => [
                'nullable',
                'string',
                Rule::requiredIf(fn () => $this->input('branding.font_source') === 'preset'),
                Rule::in(FontFamilies::validSlugs()),
            ],
            'branding.google_font_family' => [
                'nullable',
                'string',
                'max:120',
                Rule::requiredIf(fn () => $this->input('branding.font_source') === 'google'),
            ],

            // Localization
            'localization.default_locale' => ['required', 'string', 'max:10'],
            'localization.supported_locales' => ['required', 'array', 'min:1'],
            'localization.supported_locales.*' => ['string', 'max:10'],

            // Theme
            'theme.default_appearance' => ['required', 'string', 'in:light,dark,system'],
            'theme.force_appearance' => ['nullable', 'string', 'in:light,dark'],

            // Auth
            'auth.password_login_enabled' => ['boolean'],
            'auth.zoho_enabled' => ['boolean'],
            'auth.zoho_client_id' => ['nullable', 'string', 'max:255'],
            'auth.zoho_client_secret' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'branding.app_name.required' => 'The application name is required.',
            'theme.default_appearance.in' => 'The appearance must be light, dark, or system.',
            'localization.default_locale.required' => 'A default locale is required.',
        ];
    }
}
