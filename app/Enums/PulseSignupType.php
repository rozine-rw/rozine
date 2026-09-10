<?php

declare(strict_types=1);

namespace App\Enums;

enum PulseSignupType: string
{
    case Investor = 'investor';
    case Business = 'business';
}
