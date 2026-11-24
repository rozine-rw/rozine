<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\OnboardingFacts;
use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Identity\OnboardingHub;

/**
 * The dashboard's progress hub for one person (Erastus, 24 Nov 2026): their setup steps, what needs
 * them now, what happens next, what is done, and their newest onboarding updates.
 *
 * @phpstan-import-type Hub from OnboardingHub
 */
final readonly class GetOnboardingHub
{
    /** Onboarding updates the hub lists, newest first. */
    private const int RECENT = 4;

    public function __construct(private OnboardingFacts $facts, private PartyNotices $notices) {}

    /** @return Hub|null */
    public function handle(int $userId): ?array
    {
        $facts = $this->facts->facts($userId);
        if ($facts === null) {
            return null;
        }
        $recent = array_values(array_filter($this->notices->inbox($userId, 30)['notices'],
            fn (array $notice): bool => str_starts_with($notice['kind'], 'verification.') || str_starts_with($notice['kind'], 'role.')));

        return OnboardingHub::build($facts, array_slice($recent, 0, self::RECENT));
    }
}
