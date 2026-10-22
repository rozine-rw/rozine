<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\LedgerJournal;
use App\Application\Operations\Contracts\OperationRecords;
use App\Domain\Operations\CommandRejection;
use Carbon\CarbonImmutable;

/**
 * The Admin Ledger for Treasury, Compliance and superadmin (`ledger.view`): every wallet ledger
 * posting, newest first, searched on the server and paged back with a cursor, and one entry opened
 * to its lines. Nothing here can be posted, edited or reversed.
 *
 * An entry records only the operation its money flow started in, not the one that posted it: a
 * confirmation, release, refund or issue keeps its reservation's origin. So the opened entry names
 * that origin's actor and time as the origin, never as who posted the entry.
 *
 * @phpstan-import-type Entry from LedgerJournal
 * @phpstan-import-type Line from LedgerJournal
 *
 * @phpstan-type Origin array{actor: string, at: string}
 * @phpstan-type OpenedEntry array{
 *     id: string, kind: string, at: string, reference: string|null, operation_id: string|null, lines: list<Line>, origin: Origin|null
 * }
 */
final readonly class BrowseLedger
{
    private const string ZONE = 'Africa/Kigali';

    public function __construct(private LedgerJournal $journal, private AuthorizeStaffPermission $staff, private OperationRecords $operations) {}

    /** @return array{entries: list<Entry>, total: int, next_before: string|null, entry: OpenedEntry|null} */
    public function handle(int $actorId, ?string $search, int $limit, ?string $entryId, ?string $before = null): array
    {
        $this->staff->check($actorId, 'ledger.view');
        $entry = $entryId === null ? null : $this->journal->entry($entryId);
        if ($entryId !== null && $entry === null) {
            throw new CommandRejection('LEDGER_ENTRY_NOT_FOUND', 404);
        }

        return [...$this->journal->entries($search, $limit, $before),
            'entry' => $entry === null ? null : [...$entry, 'origin' => $this->origin($entry['operation_id'])]];
    }

    /**
     * The fee revenue the ledger holds, for the Finance section: Rozine's share of platform fees,
     * the Audit Partners' share still payable and the deposit fees, each in whole RWF. Given Kigali
     * dates, only what was posted from the first day through the last counts.
     *
     * @param  string|null  $from  the first Kigali date `Y-m-d`, or null for the ledger's start
     * @param  string|null  $to  the last Kigali date `Y-m-d`, or null for now
     * @return array{platform_fee: string, audit_partner_share: string, deposit_fees: string}
     */
    public function revenue(int $actorId, ?string $from = null, ?string $to = null): array
    {
        $this->staff->check($actorId, 'ledger.view');
        $day = fn (string $date): CarbonImmutable => CarbonImmutable::parse($date, self::ZONE)->startOfDay();
        $credits = $this->journal->credits(['platform_fee_revenue', 'audit_partner_fee_payable', 'deposit_fee_revenue'],
            $from === null ? null : $day($from)->utc()->toIso8601String(), $to === null ? null : $day($to)->addDay()->utc()->toIso8601String());

        return ['platform_fee' => $credits['platform_fee_revenue'] ?? '0', 'audit_partner_share' => $credits['audit_partner_fee_payable'] ?? '0',
            'deposit_fees' => $credits['deposit_fee_revenue'] ?? '0'];
    }

    /**
     * The newest postings, for the Operations Center's activity feed.
     *
     * @return list<Entry>
     */
    public function latest(int $actorId, int $limit): array
    {
        $this->staff->check($actorId, 'ledger.view');

        return $this->journal->entries(null, $limit)['entries'];
    }

    /**
     * Who started the entry's money flow and when, by reference and never by name; null when the
     * entry carries no operation or none is recorded for it.
     *
     * @return Origin|null
     */
    private function origin(?string $operationId): ?array
    {
        $attribution = $operationId === null ? null : $this->operations->attribution($operationId);
        if ($attribution === null) {
            return null;
        }
        $actor = $attribution['actor_key'];

        return ['actor' => match (true) {
            str_starts_with($actor, 'party:') => 'PARTY-'.mb_strtoupper(mb_substr($actor, -8)),
            str_starts_with($actor, 'staff:') => 'STAFF-'.mb_substr($actor, 6),
            default => mb_strtoupper($actor),
        }, 'at' => $attribution['recorded_at']];
    }
}
