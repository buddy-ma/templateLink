<?php

declare(strict_types=1);

namespace App\Enums;

enum DriveSharePermission: string
{
    case Viewer = 'viewer';
    case Editor = 'editor';

    public function canEdit(): bool
    {
        // Viewer/editor levels were removed; any share grants full access.
        return true;
    }

    public static function default(): self
    {
        return self::Editor;
    }

    public function labelKey(): string
    {
        return match ($this) {
            self::Viewer => 'drive.permissions.viewer',
            self::Editor => 'drive.permissions.editor',
        };
    }
}
