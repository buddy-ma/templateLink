<?php

declare(strict_types=1);

namespace App\Enums;

enum DemandStatus: string
{
    case Draft = 'draft';
    case PendingManager = 'pending_manager';
    case PendingValidation = 'pending_validation';
    case Refused = 'refused';
    case Blocked = 'blocked';
    case PendingBusinessDev = 'pending_business_dev';
    case PendingClosure = 'pending_closure';
    case Closed = 'closed';

    public function isEditableByCreator(): bool
    {
        return in_array($this, [self::Draft, self::Refused], true);
    }
}
