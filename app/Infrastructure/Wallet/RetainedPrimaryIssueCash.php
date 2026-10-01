<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Wallet\Contracts\PrimaryCashReceipts;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Support\Facades\DB;

/**
 * Historical issue-cash authentication for the owned settlement driver. The caller separately
 * authenticates funding, purchases and closing authority and retains the full financial locks.
 * A closing ID supplied here is a binding, never proof that the closing itself is authorized.
 * SELECT-only: no locks, balance reads, postings, current admission or financial authority.
 */
final readonly class RetainedPrimaryIssueCash
{
    public function __construct(private PrimaryCashReceipts $originalCash, private CanonicalJson $json) {}

    public function verify(string $partyId, string $walletId, WalletMoney $principal, PostingSource $source,
        string $holdId, string $commitId, string $issueId, PostingCause $closing): void
    {
        $this->originalCash->verify($partyId, $walletId, $principal, $source, $holdId, $commitId);
        $entry = LedgerEntry::query()->whereKey($issueId)->first()
            ?? throw new WalletViolation('PRIMARY_ISSUED_CASH_REQUIRED');
        if ($entry->kind !== 'primary_issue' || $entry->wallet_id !== $walletId || $entry->source_type !== $source->type
            || $entry->source_id !== $source->id || $entry->origin_operation_id !== $source->originOperationId
            || $entry->currency !== 'RWF' || $entry->cause_type !== $closing->type || $entry->cause_id !== $closing->id) {
            throw new WalletViolation('PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
        }
        $journal = JournalEntry::primary('primary_issue', $principal);
        $expectedLines = array_map(fn ($line): array => ['account' => $line->account, 'direction' => $line->direction,
            'amount' => $line->amount->amount()], $journal->lines);
        $expected = ['entry_id' => $issueId, 'wallet_id' => $walletId, 'kind' => 'primary_issue',
            'source_type' => $source->type, 'source_id' => $source->id, 'origin_operation_id' => $source->originOperationId,
            'lines' => $expectedLines, 'recorded_at' => $entry->created_at->utc()->toIso8601String(),
            'cause' => ['type' => $closing->type, 'id' => $closing->id]];
        $payload = $this->json->encode($entry->payload);
        if (! hash_equals($entry->sha256, hash('sha256', $payload)) || $payload !== $this->json->encode($expected)) {
            throw new WalletViolation('PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
        }
        $lines = DB::table((new LedgerLine)->getTable().' as l')->join((new LedgerAccount)->getTable().' as a', 'a.id', '=', 'l.account_id')
            ->where('l.entry_id', $issueId)->orderByDesc('l.direction')->get(['l.direction', 'l.amount', 'a.kind', 'a.wallet_id', 'a.currency'])
            ->map(fn (object $line): array => ['account' => $line->kind, 'direction' => $line->direction, 'amount' => (string) $line->amount,
                'wallet_id' => $line->wallet_id, 'currency' => $line->currency])->all();
        $expectedMovements = array_map(fn (array $line): array => [...$line,
            'wallet_id' => $line['account'] === 'disbursement_settlement' ? null : $walletId, 'currency' => 'RWF'], $expectedLines);
        if ($lines !== $expectedMovements) {
            throw new WalletViolation('PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
        }
    }
}
