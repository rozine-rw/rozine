<?php

declare(strict_types=1);

namespace App\Enums;

enum PulseContactMethod: string
{
    case Phone = 'phone';
    case Email = 'email';
}
