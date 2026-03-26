<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Helpers for reading/writing nested locale JSON as flat dot-key maps.
 */
final class LocaleMessageFile
{
    /**
     * @return array<string, string>
     */
    public static function flatten(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_string($value)) {
                $out[$path] = $value;
            } elseif (is_array($value)) {
                $out = array_merge($out, self::flatten($value, $path));
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $flat
     */
    public static function unflatten(array $flat): array
    {
        $result = [];
        foreach ($flat as $dot => $value) {
            if (! is_string($dot) || ! is_string($value)) {
                continue;
            }
            $keys = explode('.', $dot);
            $ref = &$result;
            $last = count($keys) - 1;
            for ($i = 0; $i <= $last; $i++) {
                $k = $keys[$i];
                if ($i === $last) {
                    $ref[$k] = $value;
                } else {
                    if (! isset($ref[$k]) || ! is_array($ref[$k])) {
                        $ref[$k] = [];
                    }
                    $ref = &$ref[$k];
                }
            }
        }

        return $result;
    }

    /**
     * Recursively sort keys for stable JSON output.
     */
    public static function ksortRecursive(array &$array): void
    {
        foreach ($array as &$v) {
            if (is_array($v)) {
                self::ksortRecursive($v);
            }
        }
        ksort($array);
    }
}
