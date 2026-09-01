<?php

declare(strict_types=1);

namespace App\Enums;

enum DemandValidatorStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Skipped = 'skipped';
}
