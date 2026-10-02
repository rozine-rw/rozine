<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Wallet\Contracts\PrimaryCashReceipts;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\InvestorWallet;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Support\Facades\DB;

final readonly class EloquentPrimaryCashReceipts implements PrimaryCashReceipts
{
    public function __construct(private CanonicalJson $json) {}

    public function verify(string $partyId, string $walletId, WalletMoney $principal, PostingSource $source, string $holdId, string $commitId): void
    {
        if ($source->type !== 'primary_reservation' || $principal->isZero()
            || ! InvestorWallet::query()->whereKey($walletId)->where('party_id', $partyId)->where('currency', 'RWF')->exists()) {
            throw new WalletViolation('PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
        }
        $entries = LedgerEntry::query()->whereKey([$holdId, $commitId])->get()->keyBy('id');
        foreach (['primary_hold' => $holdId, 'primary_commit' => $commitId] as $kind => $id) {
            $entry = $entries->get($id);
            if ($entry === null || $entry->kind !== $kind || $entry->wallet_id !== $walletId || $entry->source_type !== $source->type
                || $entry->source_id !== $source->id || $entry->origin_operation_id !== $source->originOperationId
                || $entry->currency !== 'RWF' || $entry->cause_type !== null || $entry->cause_id !== null) {
                throw new WalletViolation('PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
            }
            $journal = JournalEntry::primary($kind, $principal);
            $expectedLines = array_map(fn ($line): array => ['account' => $line->account, 'direction' => $line->direction,
                'amount' => $line->amount->amount()], $journal->lines);
            $expected = ['entry_id' => $id, 'wallet_id' => $walletId, 'kind' => $kind, 'source_type' => $source->type, 'source_id' => $source->id,
                'origin_operation_id' => $source->originOperationId, 'lines' => $expectedLines, 'recorded_at' => $entry->created_at->utc()->toIso8601String()];
            $payload = $this->json->encode($entry->payload);
            if (! hash_equals($entry->sha256, hash('sha256', $payload)) || $payload !== $this->json->encode($expected)) {
                throw new WalletViolation('PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
            }
            $lines = DB::table((new LedgerLine)->getTable().' as l')->join((new LedgerAccount)->getTable().' as a', 'a.id', '=', 'l.account_id')
                ->where('l.entry_id', $id)->orderByDesc('l.direction')->get(['l.direction', 'l.amount', 'a.kind', 'a.wallet_id', 'a.currency'])
                ->map(fn (object $line): array => ['account' => $line->kind, 'direction' => $line->direction, 'amount' => (string) $line->amount,
                    'wallet_id' => $line->wallet_id, 'currency' => $line->currency])->all();
            if ($lines !== array_map(fn (array $line): array => [...$line, 'wallet_id' => $walletId, 'currency' => 'RWF'], $expectedLines)) {
                throw new WalletViolation('PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
            }
        }
    }
}
