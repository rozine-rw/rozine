<?php

declare(strict_types=1);

namespace App\Application\Primary;

/**
 * The Profile facts for the authenticated Investor (MVP-INVESTOR-SCR-11), read under current
 * Investor authority through `GetInvestorViewer`: where their identity verification stands and the
 * verified funding methods on their wallet. The Investor role and its verification are for a
 * person only (`RoleAccess`, `InvestorVerificationCase`), so every Investor is an individual. No
 * verification expiry is recorded, so `expired` is never reported.
 *
 * @phpstan-type ProfileFacts array{identity_context_revision: int, kyc: 'verified'|'pending'|'unverified', investor_type: 'individual',
 *     methods: list<array<string, mixed>>}
 */
final class GetInvestorProfile
{
    public const array SECTIONS = ['overview', 'linked', 'statements', 'automation'];

    public function __construct(private GetInvestorViewer $viewer) {}

    /** @return ProfileFacts */
    public function handle(int $userId, ?int $contextRevision): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);
        /** @var list<array<string, mixed>> $methods */
        $methods = $viewer['wallet']['funding']['methods'] ?? [];

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'investor_type' => 'individual', 'methods' => $methods,
            'kyc' => match ($viewer['verification']) {
                null => 'verified',
                'pending' => 'pending',
                'required' => 'unverified',
            }];
    }
}
