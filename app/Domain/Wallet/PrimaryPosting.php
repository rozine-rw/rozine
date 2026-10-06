<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

/**
 * The primary purchase posting lifecycle for one reservation or commitment source. A hold opens it
 * and ends exactly once, committed or released; a commit then ends exactly once, refunded or issued
 * (a verified disbursement moving it to settlement), never both. Each later movement is for exactly
 * the amount it follows, so no bucket is overdrawn and nothing is refunded or issued beyond what was
 * committed.
 */
final readonly class PrimaryPosting
{
    /** @var array<string, array{string, string}> */
    public const array MOVEMENTS = [
        'primary_hold' => ['investor_available', 'investor_held'],
        'primary_commit' => ['investor_held', 'investor_committed'],
        'primary_release' => ['investor_held', 'investor_available'],
        'primary_refund' => ['investor_committed', 'investor_available'],
        'primary_issue' => ['investor_committed', 'disbursement_settlement'],
    ];

    /** The source types a primary posting may name. */
    public const array SOURCES = ['primary_reservation', 'primary_commitment'];

    /**
     * The posting this kind must follow, or null for the opening hold.
     */
    public static function follows(string $kind): ?string
    {
        return match ($kind) {
            'primary_hold' => null,
            'primary_commit', 'primary_release' => 'primary_hold',
            'primary_refund', 'primary_issue' => 'primary_commit',
            default => throw new WalletViolation('WALLET_POSTING_KIND_INVALID'),
        };
    }

    /**
     * Refuses a movement the source's recorded postings do not allow: a second opening, a movement
     * with nothing open to follow, a movement after the hold already ended, or a refund or issue of
     * a commitment that was already refunded or issued.
     *
     * @param  list<string>  $recorded  kinds already posted for this source
     */
    public static function assertAllowed(string $kind, array $recorded): void
    {
        $follows = self::follows($kind);
        $ended = in_array('primary_commit', $recorded, true) || in_array('primary_release', $recorded, true);
        if (($follows === null && $recorded !== []) || ($follows !== null && ! in_array($follows, $recorded, true))
            || (in_array($kind, ['primary_commit', 'primary_release'], true) && $ended) || in_array($kind, $recorded, true)
            || (in_array($kind, ['primary_refund', 'primary_issue'], true) && array_intersect(['primary_refund', 'primary_issue'], $recorded) !== [])) {
            throw new WalletViolation('WALLET_POSTING_STATE_INVALID');
        }
    }
}
