<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Models\BusinessCampaign;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;

it('refuses to return an unlocked source outside the caller transaction', function (): void {
    expect(DB::transactionLevel())->toBe(0);
    expect(fn () => app(PrimaryCampaignSource::class)->lock((string) Str::ulid()))->toThrow(LogicException::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('retains both Business and campaign locks through the callers outer transaction', function (): void {
    $this->freezeSecond();
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $campaigns = app(BusinessCampaignStore::class);
    $campaigns->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Verified release.', (string) Str::uuid());
    $campaigns->publish($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
        $fixture['application']->id, $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $campaign = BusinessCampaign::query()->sole();
    config(['database.connections.primary_contender' => config('database.connections.pgsql')]);
    $contender = DB::connection('primary_contender');
    $contender->statement("SET lock_timeout = '200ms'");
    $targets = ['business_profiles' => $campaign->business_id, 'business_campaigns' => $campaign->id];
    try {
        DB::beginTransaction();
        DB::transaction(fn (): array => app(PrimaryCampaignSource::class)->lock($campaign->id));
        foreach ($targets as $table => $id) {
            expect(fn () => $contender->transaction(fn () => $contender->table($table)->where('id', $id)->lockForUpdate()->first()))
                ->toThrow(QueryException::class, 'lock timeout');
        }
        DB::rollBack();
        foreach ($targets as $table => $id) {
            expect($contender->transaction(fn () => $contender->table($table)->where('id', $id)->lockForUpdate()->first())?->id)->toBe($id);
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('primary_contender');
    }
});
