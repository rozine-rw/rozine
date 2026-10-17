<?php

declare(strict_types=1);

use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\RepaymentComponentAllocation;
use App\Domain\Primary\UnitRights;

arch('receipt component arithmetic cannot acquire persistence authority or reprice original rights')
    ->expect(RepaymentComponentAllocation::class)
    ->not->toUse(['App\Application', 'App\Infrastructure', 'App\Models', 'Illuminate', PrimaryTerms::class, UnitRights::class]);
