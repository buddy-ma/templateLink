<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\AppSettingsService;

/**
 * Resolves font stylesheet URL and CSS font-family stack from app settings.
 */
final class BrandingFont
{
    public const UPLOAD_FACE_NAME = 'AppUploadedFont';

    /** @return list<array{value: string, label: string}> */
    public static function googleFontSelectOptions(): array
    {
        $fonts = [
            'Poppins',
            'Montserrat',
            'Open Sans',
            'Roboto',
            'Lato',
            'Raleway',
            'Nunito',
            'Work Sans',
            'Ubuntu',
            'Merriweather',
        ];
        $out = [];
        foreach ($fonts as $name) {
            $out[] = ['value' => $name, 'label' => $name];
        }

        return $out;
    }

    public static function googleStylesheetUrl(string $family): string
    {
        $q = str_replace(' ', '+', $family);

        return "https://fonts.googleapis.com/css2?family={$q}:wght@400;500;600;700&display=swap";
    }

    public static function googleFontStack(string $family): string
    {
        $quoted = "'".str_replace("'", "\\'", $family)."'";

        return "{$quoted}, ui-sans-serif, system-ui, sans-serif";
    }

    /**
     * External stylesheet for first paint (null when using uploaded font — @font-face is injected client-side).
     */
    public static function stylesheetHref(AppSettingsService $settings): ?string
    {
        $source = $settings->get('branding.font_source');
        if ($source === null || $source === '') {
            return FontFamilies::bunnyUrl(self::resolvedPresetSlug($settings));
        }

        return match ((string) $source) {
            'google' => self::googleStylesheetUrl((string) $settings->get('branding.google_font_family', 'Poppins')),
            'preset' => FontFamilies::bunnyUrl(self::resolvedPresetSlug($settings)),
            default => null,
        };
    }

    public static function fontStack(AppSettingsService $settings): string
    {
        $source = $settings->get('branding.font_source');
        if ($source === null || $source === '') {
            return FontFamilies::fontStack(self::resolvedPresetSlug($settings));
        }

        return match ((string) $source) {
            'preset' => FontFamilies::fontStack(self::resolvedPresetSlug($settings)),
            'google' => self::googleFontStack((string) $settings->get('branding.google_font_family', 'Poppins')),
            'upload' => '\''.self::UPLOAD_FACE_NAME.'\', ui-sans-serif, system-ui, sans-serif',
            default => FontFamilies::fontStack('instrument-sans'),
        };
    }

    public static function fontFaceName(AppSettingsService $settings): ?string
    {
        $source = (string) $settings->get('branding.font_source', '');

        return $source === 'upload' ? self::UPLOAD_FACE_NAME : null;
    }

    public static function fontUploadPublicUrl(AppSettingsService $settings): ?string
    {
        $url = $settings->get('branding.font_upload_url');

        return is_string($url) && $url !== '' ? $url : null;
    }

    private static function resolvedPresetSlug(AppSettingsService $settings): string
    {
        $slug = (string) ($settings->get('branding.font_preset') ?? $settings->get('branding.font_family', 'instrument-sans'));
        if (! in_array($slug, FontFamilies::validSlugs(), true)) {
            return 'instrument-sans';
        }

        return $slug;
    }
}
