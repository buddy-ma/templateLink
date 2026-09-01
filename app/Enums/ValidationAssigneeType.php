<?php

declare(strict_types=1);

namespace App\Enums;

enum ValidationAssigneeType: string
{
    case User = 'user';
    case Role = 'role';
}
