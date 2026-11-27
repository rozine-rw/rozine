<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

/** A consistent read of issued notes. No collection, notification or payment effects. */
interface StaffServicingStore
{
    /** @return array<string, mixed> */
    public function read(): array;
}
