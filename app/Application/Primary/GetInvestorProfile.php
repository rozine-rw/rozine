<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Identity\Contracts\InvestorVerificationStore;

/**
 * The Profile facts for the authenticated Investor (MVP-INVESTOR-SCR-11), read under current
 * Investor authority through `GetInvestorViewer`: where their identity verification stands and the
 * verified funding methods on their wallet. The Investor role and its verification are for a
 * person only (`RoleAccess`, `InvestorVerificationCase`), so every Investor is an individual. No
 * verification expiry is recorded, so `expired` is never reported. The identity document is the one
 * staff approved, and only its last four characters leave the server.
 *
 * @phpstan-type ProfileFacts array{identity_context_revision: int, kyc: 'verified'|'pending'|'unverified', investor_type: 'individual',
 *     methods: list<array<string, mixed>>, document: array{id_type: 'national_id'|'passport'|'drivers_license', id_number: string}|null}
 */
final class GetInvestorProfile
{
    public const array SECTIONS = ['overview', 'personal', 'plan', 'security', 'linked', 'statements', 'help', 'terms', 'privacy', 'automation'];

    public function __construct(private GetInvestorViewer $viewer, private InvestorVerificationStore $verifications) {}

    /** @return ProfileFacts */
    public function handle(int $userId, ?int $contextRevision): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);
        /** @var list<array<string, mixed>> $methods */
        $methods = $viewer['wallet']['funding']['methods'] ?? [];
        $document = $this->verifications->approvedDocument($userId);

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'investor_type' => 'individual', 'methods' => $methods,
            'kyc' => match ($viewer['verification']) {
                null => 'verified',
                'pending' => 'pending',
                'required' => 'unverified',
            },
            'document' => $document === null ? null : ['id_type' => $document['id_type'], 'id_number' => self::masked($document['id_number'])]];
    }

    /** Every character but the last four hidden; a 16-digit card number is grouped in fours as printed. */
    private static function masked(string $number): string
    {
        $hidden = str_repeat('•', max(0, mb_strlen($number) - 4)).mb_substr($number, -4);

        return mb_strlen($number) === 16 ? implode(' ', mb_str_split($hidden, 4)) : $hidden;
    }
}
