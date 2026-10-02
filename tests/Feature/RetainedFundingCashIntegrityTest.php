<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(fn () => $this->freezeSecond());

it('refuses a corrupted original cash receipt digest before projecting retained funding', function (string $kind): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $source = app(CampaignFundingEvidence::class);
    $funding = $source->find($campaign->id);
    $entry = LedgerEntry::query()->whereKey($funding['commitments'][0]['cash'][$kind === 'primary_hold' ? 'hold_entry_id' : 'commit_entry_id'])->sole();
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER ledger_entries_protected');
    try {
        $entry->forceFill(['sha256' => str_repeat('0', 64)])->save();
    } finally {
        DB::statement('ALTER TABLE ledger_entries ENABLE TRIGGER ledger_entries_protected');
    }
    expect(fn () => $source->find($campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
})->with(['primary_hold', 'primary_commit']);

it('refuses digest-consistent original cash receipt envelope forgeries', function (string $kind, string $field): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $source = app(CampaignFundingEvidence::class);
    $funding = $source->find($campaign->id);
    $entry = LedgerEntry::query()->whereKey($funding['commitments'][0]['cash'][$kind === 'primary_hold' ? 'hold_entry_id' : 'commit_entry_id'])->sole();
    $payload = $entry->payload;
    if ($field === 'lines') {
        $payload['lines'][0]['amount'] = '5000';
    } else {
        $payload[$field] = 'foreign';
    }
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER ledger_entries_protected');
    try {
        $entry->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE ledger_entries ENABLE TRIGGER ledger_entries_protected');
    }
    expect(fn () => $source->find($campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
})->with(['primary_hold', 'primary_commit'])->with(['entry_id', 'wallet_id', 'kind', 'source_type', 'source_id', 'origin_operation_id', 'recorded_at', 'lines', 'extra']);
