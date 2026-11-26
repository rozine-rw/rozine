<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

/**
 * The changeable policy values in force (see `App\Domain\Operations\PolicyChange`). A value changes only
 * once both founders have approved the change, so every reader sees the founders' decision at once.
 */
interface PolicySettings
{
    /** The value in force for one setting, its default when no row exists yet. */
    public function value(string $key): int;

    /** @return array<string, int> every setting's value in force */
    public function values(): array;

    /**
     * The most of any one raise this Party may hold, in percent: the institution cap when it invests
     * for an approved institution, the individual cap otherwise.
     */
    public function holdingCapPercent(string $partyId): int;
}
