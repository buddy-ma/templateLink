<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Bunny-hosted webfonts mapped to CSS font stacks (see BrandProvider + app.css --font-sans).
 *
 * @phpstan-type FontDef array{label: string, bunny: string, stack: string}
 */
final class FontFamilies
{
    /** @var array<string, FontDef> */
    public const KEYS = [
        'instrument-sans' => [
            'label' => 'Instrument Sans',
            'bunny' => 'https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700',
            'stack' => "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
        ],
        'inter' => [
            'label' => 'Inter',
            'bunny' => 'https://fonts.bunny.net/css?family=inter:400,500,600,700',
            'stack' => "'Inter', ui-sans-serif, system-ui, sans-serif",
        ],
        'dm-sans' => [
            'label' => 'DM Sans',
            'bunny' => 'https://fonts.bunny.net/css?family=dm-sans:400,500,600,700',
            'stack' => "'DM Sans', ui-sans-serif, system-ui, sans-serif",
        ],
        'nunito-sans' => [
            'label' => 'Nunito Sans',
            'bunny' => 'https://fonts.bunny.net/css?family=nunito-sans:400,500,600,700',
            'stack' => "'Nunito Sans', ui-sans-serif, system-ui, sans-serif",
        ],
        'source-sans-3' => [
            'label' => 'Source Sans 3',
            'bunny' => 'https://fonts.bunny.net/css?family=source-sans-3:400,500,600,700',
            'stack' => "'Source Sans 3', ui-sans-serif, system-ui, sans-serif",
        ],
        'ibm-plex-sans' => [
            'label' => 'IBM Plex Sans',
            'bunny' => 'https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700',
            'stack' => "'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif",
        ],
        'poppins' => [
            'label' => 'Poppins',
            'bunny' => 'https://fonts.bunny.net/css?family=poppins:400,500,600,700',
            'stack' => "'Poppins', ui-sans-serif, system-ui, sans-serif",
        ],
        'montserrat' => [
            'label' => 'Montserrat',
            'bunny' => 'https://fonts.bunny.net/css?family=montserrat:400,500,600,700',
            'stack' => "'Montserrat', ui-sans-serif, system-ui, sans-serif",
        ],
        'system-ui' => [
            'label' => 'System UI',
            'bunny' => '',
            'stack' => 'ui-sans-serif, system-ui, sans-serif',
        ],
    ];

    public static function bunnyUrl(string $slug): string
    {
        return self::KEYS[$slug]['bunny'] ?? '';
    }

    public static function fontStack(string $slug): string
    {
        return self::KEYS[$slug]['stack'] ?? self::KEYS['instrument-sans']['stack'];
    }

    /** @return list<string> */
    public static function validSlugs(): array
    {
        return array_keys(self::KEYS);
    }

    /**
     * Options for admin UI / Inertia (slug + human label).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function selectOptions(): array
    {
        $out = [];
        foreach (self::KEYS as $slug => $def) {
            $out[] = ['value' => $slug, 'label' => $def['label']];
        }

        return $out;
    }
}
