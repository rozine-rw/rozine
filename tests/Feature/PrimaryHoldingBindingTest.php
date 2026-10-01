<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\HoldingSource;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessCampaign;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryHolding;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * A Holding is bound to the Primary evidence S3-C retains. Every case inserts with raw SQL against a
 * campaign bought through the real checkout and locked by the real funding lock; no commitment id
 * is invented. PostgreSQL compares what it can read; rights and terms are the verifier's.
 */

/** The write is refused by the schema with exactly this SQLSTATE and message, and leaves nothing behind. */
function holdingRefused(Closure $write, string $message, string $code = '23514'): void
{
    $before = DB::table('primary_holdings')->count();
    try {
        DB::transaction(fn () => $write());
    } catch (QueryException $exception) {
        expect($exception->getCode())->toBe($code)->and($exception->getMessage())->toContain($message)
            ->and(DB::table('primary_holdings')->count())->toBe($before);

        return;
    }
    throw new RuntimeException('The schema accepted a write it must refuse: '.$message);
}

/** @return array<string, mixed> the reservation and confirmed revision a commitment retains */
function holdingSource(PrimaryCommitment $commitment): array
{
    return ['root' => PrimaryReservationRecord::query()->whereKey($commitment->primary_reservation_id)->sole(),
        'confirmed' => PrimaryReservationVersion::query()->whereKey($commitment->primary_reservation_version_id)->sole(),
        'revisions' => PrimaryReservationVersion::query()->where('primary_reservation_id', $commitment->primary_reservation_id)->orderBy('revision')->get()];
}

/**
 * Rewrites retained evidence the way only a holder of the encryption key could: new payload, matching digest.
 * The immutability trigger is lifted inside the caller's savepoint and returns when that is rolled back;
 * nothing in the application can do this.
 */
function holdingForge(string $table, string $id, Closure $change): void
{
    $trigger = ['primary_reservations' => 'primary_reservations_immutable', 'primary_reservation_versions' => 'primary_reservation_versions_immutable',
        'primary_campaign_fundings' => 'primary_funding_immutable'][$table];
    $payload = $change(json_decode(Crypt::decryptString((string) DB::table($table)->where('id', $id)->value('payload')), true, 512, JSON_THROW_ON_ERROR));
    DB::statement("ALTER TABLE {$table} DISABLE TRIGGER {$trigger}");
    DB::table($table)->where('id', $id)->update(['payload' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
        'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
}

beforeEach(fn () => $this->freezeSecond());

it('accepts one immutable Holding per commitment that equals its retained facts', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $requoted, $last]] = PrimaryHoldingFixture::committed(['1080', '600', '480'], [1]);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $holdings = [PrimaryHoldingFixture::insert($first->id, $closing)];
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing), 'primary_holdings_commitment_id_unique', '23505');
    $holdings = [...$holdings, PrimaryHoldingFixture::insert($requoted->id, $closing),
        PrimaryHoldingFixture::insert($last->id, $closing, ['payload' => encrypt(json_encode(['source' => 'synthetic']), false)])];
    foreach ([$first, $requoted, $last] as $commitment) {
        PrimaryHoldingFixture::issue($commitment->id, $closing);
    }
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    foreach ($holdings as $holding) {
        app(HoldingSource::class)->verify($holding);
    }
    $rows = DB::table('primary_holdings')->orderBy('units')->get();
    expect($rows->pluck('units')->all())->toBe([480, 600, 1080])
        ->and($rows->pluck('ordinals')->map(fn (string $ordinals): array => json_decode($ordinals, true))->all())
        ->toEqual([[['first' => 1681, 'last' => 2160]], [['first' => 1081, 'last' => 1680]], [['first' => 1, 'last' => 1080]]])
        ->and($rows->pluck('confirmation_revision')->all())->toBe([2, 3, 2])
        ->and(json_decode($rows[1]->terms, true)['disclosure_version'])->toBe('synthetic-disclosure-2')
        ->and($rows->sum('principal'))->toEqual($campaign->principal);
    $read = PrimaryHolding::query()->whereKey($holdings[2])->sole();
    expect([$read->principal, $read->ordinals, $read->effective_date->format('Y-m-d'), $read->payload, $read->confirmation_revision, $read->terms['term_months']])
        ->toEqual(['2400000', [['first' => 1681, 'last' => 2160]], '2027-01-31', ['source' => 'synthetic'], 2, 6]);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing), 'A Holding issues once from its campaign\'s issued closing, within its principal');
    holdingRefused(fn () => DB::table('primary_holdings')->where('id', $holdings[0])->update(['units' => 1]), 'Holdings are immutable');
    holdingRefused(fn () => DB::table('primary_holdings')->where('id', $holdings[0])->delete(), 'Holdings are immutable');
    holdingRefused(fn () => DB::table('primary_commitments')->where('id', $first->id)->delete(), 'Primary reservation and commitment evidence is immutable');
});

it('refuses a Holding whose Party, units or principal differ from its commitment', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $second]] = PrimaryHoldingFixture::committed(['1080', '600', '480']);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $other = holdingSource($second)['root'];
    $message = 'A Holding must keep its commitment\'s campaign, Party, units and principal';
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['party_id' => $other->party_id]), $message);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['units' => 1079]), $message);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['principal' => '5395000']), $message);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['units' => 600, 'principal' => '3000000']), $message);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['units' => 1081, 'principal' => '5405000']), $message);
    PrimaryHoldingFixture::insert($first->id, $closing);
});

it('refuses a Holding of another campaign even when a forged disbursement copies the funding facts', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first]] = PrimaryHoldingFixture::committed();
    $forged = PrimaryHoldingFixture::id();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign, ['business_campaign_id' => $forged]);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['business_campaign_id' => $forged]),
        'A Holding must keep its commitment\'s campaign, Party, units and principal');
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing), 'A Holding issues once from its campaign\'s issued closing');
});

it('refuses a Holding issued from a disbursement that is not its funding record\'s', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first]] = PrimaryHoldingFixture::committed();
    foreach ([['business_id' => '01hzzzzzzzzzzzzzzzzzzzzzzz'], ['exposure_reservation_id' => '01hzzzzzzzzzzzzzzzzzzzzzzz'], ['amount' => '10805000']] as $snapshot) {
        holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, PrimaryHoldingFixture::issuedClosing($campaign, $snapshot)),
            'A Holding must issue from the disbursement of its commitment\'s funding record');
    }
    PrimaryHoldingFixture::insert($first->id, PrimaryHoldingFixture::issuedClosing($campaign));
});

it('keeps the closing rules: an issued closing of the same campaign, its effective instant and its principal', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first]] = PrimaryHoldingFixture::committed();
    $message = 'A Holding issues once from its campaign\'s issued closing, within its principal';
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, PrimaryHoldingFixture::issuedClosing($campaign, kind: 'failed_closing'),
        ['disbursement_effective_at' => null, 'effective_date' => null]), $message);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, PrimaryHoldingFixture::issuedClosing($campaign, ['amount' => '5395000'])), $message);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, PrimaryHoldingFixture::id()), $message);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['effective_date' => '2027-02-01']), $message);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['disbursement_effective_at' => '2027-01-31T07:59:59Z']), $message);
    PrimaryHoldingFixture::insert($first->id, $closing);
});

it('refuses a Holding of another funded campaign\'s commitment', function (): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    ['commitments' => [$foreign]] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($foreign->id, $closing), 'A Holding issues once from its campaign\'s issued closing');
    holdingRefused(fn () => PrimaryHoldingFixture::insert($foreign->id, $closing, ['business_campaign_id' => $campaign->id]),
        'A Holding must issue from the disbursement of its commitment\'s funding record');
});

it('refuses forged ordinals', function (): void {
    ['campaign' => $campaign, 'commitments' => [, $middle]] = PrimaryHoldingFixture::committed(['1080', '600', '480']);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    expect(PrimaryHoldingFixture::row($middle->id, $closing)['ordinals'])->toBe('[{"first":1081,"last":1680}]');
    foreach ([
        'a superset' => '[{"first":1081,"last":1681}]',
        'a subset' => '[{"first":1081,"last":1679}]',
        'shifted down by one' => '[{"first":1080,"last":1679}]',
        'shifted up by one' => '[{"first":1082,"last":1681}]',
        'another commitment\'s ordinals' => '[{"first":1,"last":1080}]',
        'the same count from another commitment' => '[{"first":1,"last":600}]',
        'an extra range' => '[{"first":1081,"last":1680},{"first":1681,"last":1681}]',
        'the same set split into two ranges' => '[{"first":1081,"last":1300},{"first":1301,"last":1680}]',
        'the same set in descending order' => '[{"first":1301,"last":1680},{"first":1081,"last":1300}]',
        'ordinals as strings' => '[{"first":"1081","last":"1680"}]',
        'an extra key' => '[{"first":1081,"last":1680,"units":600}]',
    ] as $ordinals) {
        holdingRefused(fn () => PrimaryHoldingFixture::insert($middle->id, $closing, ['ordinals' => $ordinals]), 'A Holding must keep exactly its reservation\'s claimed ordinals');
    }
    PrimaryHoldingFixture::insert($middle->id, $closing);
});

it('keeps a multi-range reservation\'s exact sorted ranges without merging or reordering them', function (): void {
    // #175 allocates the lowest free ordinals and never recycles, so a real checkout cannot produce two ranges. This one
    // is retained through #175's own factories (synthetic payloads, real hold and commit postings) and a raw funding
    // record and member that pass #175's insert triggers; its deferred full-funding check is deliberately never run.
    $root = PrimaryReservationRecord::factory()->withInitialVersion()->create(['units' => 5, 'principal' => '25000', 'ordinal_ranges' => '{[1,3),[5,8)}']);
    $confirmed = PrimaryReservationVersion::factory()->confirmed()->withCashMovement()->create(['primary_reservation_id' => $root->id]);
    $commitment = PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $confirmed->id]);
    $campaign = BusinessCampaign::query()->whereKey($root->business_campaign_id)->sole();
    $entries = DB::table('ledger_entries')->where('source_type', 'primary_reservation')->where('source_id', $root->id)->get()->keyBy('kind');
    $funding = PrimaryHoldingFixture::id();
    DB::table('primary_campaign_fundings')->insert(['id' => $funding, 'business_campaign_id' => $campaign->id, 'business_id' => $campaign->business_id,
        'exposure_reservation_id' => $campaign->exposure_reservation_id, 'publication_sha256' => $campaign->sha256, 'principal' => $campaign->principal,
        'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()]);
    DB::table('primary_funding_commitments')->insert(['funding_id' => $funding, 'commitment_id' => $commitment->id, 'reservation_id' => $root->id,
        'hold_entry_id' => $entries['primary_hold']->id, 'commit_entry_id' => $entries['primary_commit']->id,
        'wallet_id' => $entries['primary_hold']->wallet_id, 'origin_operation_id' => $root->origin_operation_id]);
    $row = ['id' => PrimaryHoldingFixture::id(), 'business_campaign_id' => $campaign->id, 'commitment_id' => $commitment->id, 'primary_reservation_id' => $root->id,
        'party_id' => $root->party_id, 'units' => 5, 'principal' => '25000', 'ordinals' => '[{"first":1,"last":2},{"first":5,"last":7}]',
        'rights' => '{"synthetic":true}', 'terms' => '{"synthetic":true}', 'reservation_sha256' => $root->sha256, 'confirmation_version_id' => $confirmed->id,
        'confirmation_revision' => $confirmed->revision, 'confirmation_sha256' => $confirmed->sha256,
        'disbursement_closing_id' => PrimaryHoldingFixture::issuedClosing($campaign), 'schedule' => '[{"index":1}]', 'issued_at' => '2027-01-31T09:00:00Z',
        'disbursement_effective_at' => '2027-01-31T08:00:00Z', 'effective_date' => '2027-01-31', 'receipt_id' => PrimaryHoldingFixture::id(),
        'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()];
    foreach ([
        'the ranges in descending order' => '[{"first":5,"last":7},{"first":1,"last":2}]',
        'the gap filled by one merged range' => '[{"first":1,"last":7}]',
        'five contiguous units instead' => '[{"first":1,"last":5}]',
        'only the first range' => '[{"first":1,"last":2}]',
        'only the second range' => '[{"first":5,"last":7}]',
        'the second range split' => '[{"first":1,"last":2},{"first":5,"last":6},{"first":7,"last":7}]',
        'the second range moved into the gap' => '[{"first":1,"last":2},{"first":3,"last":5}]',
    ] as $ordinals) {
        holdingRefused(fn () => DB::table('primary_holdings')->insert([...$row, 'ordinals' => $ordinals]), 'A Holding must keep exactly its reservation\'s claimed ordinals');
    }
    DB::table('primary_holdings')->insert($row);
    expect(json_decode((string) DB::table('primary_holdings')->value('ordinals'), true))->toEqual([['first' => 1, 'last' => 2], ['first' => 5, 'last' => 7]])
        ->and(DB::table('primary_ordinal_claims')->where('primary_reservation_id', $root->id)->orderBy('ordinal')->pluck('ordinal')->all())->toBe([1, 2, 5, 6, 7]);
});

it('refuses a Holding pinned to another reservation or to a revision its commitment did not confirm', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $requoted]] = PrimaryHoldingFixture::committed(['1080', '1080'], [1]);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    ['root' => $otherRoot, 'confirmed' => $otherConfirmed] = holdingSource($first);
    ['revisions' => [$held, $requote, $confirmed]] = holdingSource($requoted);
    expect([$held->state, $requote->state, $confirmed->state, $confirmed->revision])->toBe(['held', 'held', 'confirmed', 3]);
    $message = 'A Holding must pin its reservation and the revision its commitment confirmed';
    foreach ([
        ['primary_reservation_id' => $otherRoot->id],
        ['reservation_sha256' => $otherRoot->sha256],
        ['reservation_sha256' => str_repeat('0', 64)],
        ['confirmation_version_id' => $requote->id],
        ['confirmation_version_id' => $otherConfirmed->id],
        ['confirmation_revision' => 2],
        ['confirmation_revision' => 4],
        ['confirmation_sha256' => $requote->sha256],
        ['confirmation_sha256' => $otherConfirmed->sha256],
        ['confirmation_version_id' => $requote->id, 'confirmation_revision' => $requote->revision, 'confirmation_sha256' => $requote->sha256, 'terms' => json_encode($requote->payload['terms'])],
        ['confirmation_version_id' => $held->id, 'confirmation_revision' => $held->revision, 'confirmation_sha256' => $held->sha256, 'terms' => json_encode($held->payload['terms'])],
        ['primary_reservation_id' => $otherRoot->id, 'reservation_sha256' => $otherRoot->sha256, 'rights' => json_encode($otherRoot->payload['rights'])],
    ] as $forged) {
        holdingRefused(fn () => PrimaryHoldingFixture::insert($requoted->id, $closing, $forged), $message);
    }
    PrimaryHoldingFixture::insert($requoted->id, $closing);
});

it('cannot read rights or terms in PostgreSQL: forged copies with true pins pass the schema and fail the verifier', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $requoted, $last]] = PrimaryHoldingFixture::committed(['1080', '600', '480'], [1]);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $stale = holdingSource($requoted)['revisions'][0]->payload['terms'];
    $rights = holdingSource($last)['root']->payload['rights'];
    $rights['instalments'][0]['return']['amount'] = (string) ((int) $rights['instalments'][0]['return']['amount'] + 1);
    $honest = PrimaryHoldingFixture::insert($first->id, $closing);
    $staleTerms = PrimaryHoldingFixture::insert($requoted->id, $closing, ['terms' => json_encode($stale)]);
    $forgedRights = PrimaryHoldingFixture::insert($last->id, $closing, ['rights' => json_encode($rights)]);
    expect($stale['disclosure_version'])->toBe('synthetic-disclosure-1');
    app(HoldingSource::class)->verify($honest);
    expect(fn () => app(HoldingSource::class)->verify($staleTerms))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_MISMATCH')
        ->and(fn () => app(HoldingSource::class)->verify($forgedRights))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_MISMATCH');
});

it('gives the adapter a commitment\'s verified facts and refuses what is not funded evidence', function (): void {
    ['commitments' => [$first]] = PrimaryHoldingFixture::committed();
    ['commitments' => [$unfunded]] = PrimaryHoldingFixture::committed(['1080'], fund: false);
    ['root' => $root, 'confirmed' => $confirmed] = holdingSource($first);
    expect(app(HoldingSource::class)->facts($first->id))->toBe(['business_campaign_id' => $root->business_campaign_id, 'commitment_id' => $first->id,
        'primary_reservation_id' => $root->id, 'party_id' => $root->party_id, 'units' => 1080, 'principal' => '5400000',
        'ordinals' => [['first' => 1, 'last' => 1080]], 'rights' => $root->payload['rights'], 'terms' => $confirmed->payload['terms'],
        'reservation_sha256' => $root->sha256, 'confirmation_version_id' => $confirmed->id, 'confirmation_revision' => 2, 'confirmation_sha256' => $confirmed->sha256])
        ->and(fn () => app(HoldingSource::class)->facts(PrimaryHoldingFixture::id()))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_UNAVAILABLE')
        ->and(fn () => app(HoldingSource::class)->facts($root->id))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_UNAVAILABLE')
        ->and(fn () => app(HoldingSource::class)->facts($unfunded->id))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_UNAVAILABLE')
        ->and(fn () => app(HoldingSource::class)->verify(PrimaryHoldingFixture::id()))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_NOT_FOUND');
});

it('replays the acknowledged disclosure and refuses retained evidence that no longer reproduces it', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first]] = PrimaryHoldingFixture::committed();
    ['root' => $root, 'confirmed' => $confirmed] = holdingSource($first);
    $funding = (string) DB::table('primary_campaign_fundings')->where('business_campaign_id', $campaign->id)->value('id');
    $source = app(HoldingSource::class);
    $holding = PrimaryHoldingFixture::insert($first->id, PrimaryHoldingFixture::issuedClosing($campaign));
    $source->verify($holding);
    // Each forgery keeps every stored digest and the funding record consistent, so only the replay can notice it.
    $purchase = fn (Closure $change): Closure => function (array $payload) use ($change, $first): array {
        $payload['commitments'] = array_map(fn (array $purchase): array => $purchase['commitment_id'] === $first->id ? $change($purchase) : $purchase, $payload['commitments']);

        return $payload;
    };
    $raisedReturn = function (array $holder): array {
        $holder['rights']['instalments'][0]['return']['amount'] = (string) ((int) $holder['rights']['instalments'][0]['return']['amount'] + 1);

        return $holder;
    };
    $terms = fn (Closure $change): Closure => function (array $holder) use ($change): array {
        $holder['terms'] = $change($holder['terms']);

        return $holder;
    };
    $digest = function (array $holder): array {
        $holder['disclosure_sha256'] = str_repeat('a', 64);

        return $holder;
    };
    $split = function (array $holder): array {
        $holder['ordinals'] = [['first' => '1', 'last' => '500'], ['first' => '501', 'last' => '1080']];

        return $holder;
    };
    $cheaperFee = fn (array $retained): array => [...$retained, 'payout_fee' => ['currency' => 'RWF', 'amount' => '1']];
    $newTier = fn (array $retained): array => [...$retained, 'earnings_fee' => [...$retained['earnings_fee'], 'tier' => 'gold', 'rate_bps' => 550]];
    $newPolicy = fn (array $retained): array => [...$retained, 'policy_version' => 'a-later-policy'];
    foreach ([
        'rights the retained schedule does not allocate' => [$raisedReturn, null, $raisedReturn],
        'a payout fee the rights do not produce' => [null, $terms($cheaperFee), $terms($cheaperFee)],
        'a fee tier the Investor never acknowledged' => [null, $terms($newTier), $terms($newTier)],
        'a later policy version than the held quote' => [null, $terms($newPolicy), $terms($newPolicy)],
        'a disclosure digest that is not the terms\' digest' => [null, $digest, $digest],
        'ordinals that are not the exact normalized ranges' => [$split, null, $split],
        'a reservation without its campaign schedule' => [fn (array $payload): array => array_diff_key($payload, ['campaign_payments' => true]), null, null],
    ] as $case => [$inRoot, $inRevision, $inPurchase]) {
        DB::beginTransaction();
        if ($inRoot !== null) {
            holdingForge('primary_reservations', $root->id, $inRoot);
        }
        if ($inRevision !== null) {
            holdingForge('primary_reservation_versions', $confirmed->id, $inRevision);
        }
        if ($inPurchase !== null) {
            holdingForge('primary_campaign_fundings', $funding, $purchase($inPurchase));
        }
        expect(app(CampaignFundingEvidence::class)->find($campaign->id))->toBeArray($case)
            ->and(fn () => $source->facts($first->id))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED')
            ->and(fn () => $source->verify($holding))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED');
        DB::rollBack();
    }
    // Evidence the funding record itself no longer matches is refused by #175's verifier before any replay.
    DB::beginTransaction();
    holdingForge('primary_reservations', $root->id, $raisedReturn);
    expect(fn () => $source->facts($first->id))->toThrow(RuntimeException::class, 'PRIMARY_FUNDING_INTEGRITY_FAILED');
    DB::rollBack();
    $source->verify($holding);
});

it('authenticates every consumed earlier revision\'s digest, ancestry and recorded instant before replaying it', function (): void {
    ['campaign' => $campaign, 'commitments' => [$requoted]] = PrimaryHoldingFixture::committed(['1080', '1080'], [0]);
    ['revisions' => [$held, $requote, $confirmed]] = holdingSource($requoted);
    $source = app(HoldingSource::class);
    expect([$held->state, $requote->state, $confirmed->state])->toBe(['held', 'held', 'confirmed'])
        ->and($source->facts($requoted->id)['confirmation_revision'])->toBe(3);
    // Inside each rolled-back case the immutability trigger is lifted once; nothing in the application can do this.
    $column = fn (string $id, array $values): int => DB::table('primary_reservation_versions')->where('id', $id)->update($values);
    $rewrite = function (string $id, Closure $change) use ($column): string {
        $payload = $change(json_decode(Crypt::decryptString((string) DB::table('primary_reservation_versions')->where('id', $id)->value('payload')), true, 512, JSON_THROW_ON_ERROR));
        $sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
        $column($id, ['payload' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)), 'sha256' => $sha256]);

        return $sha256;
    };
    foreach ([
        'the held revision\'s digest replaced' => fn () => $column($held->id, ['sha256' => str_repeat('0', 64)]),
        'the requoted revision\'s digest replaced' => fn () => $column($requote->id, ['sha256' => str_repeat('0', 64)]),
        'the held revision\'s payload rewritten under a matching digest' => fn () => $rewrite($held->id, fn (array $payload): array => [...$payload, 'operation_id' => (string) Str::uuid()]),
        'the requoted revision detached from its parent' => fn () => $column($requote->id, ['previous_sha256' => str_repeat('0', 64)]),
        'the requoted revision recorded as an earlier terminal state' => fn () => $column($requote->id, ['state' => 'expired']),
        'a requoted instant the chain re-signs but the revision did not record' => function () use ($requote, $confirmed, $column, $rewrite): void {
            $parent = $rewrite($requote->id, fn (array $payload): array => [...$payload, 'recorded_at' => '2099-01-01T00:00:00.000000Z']);
            $rewrite($confirmed->id, fn (array $payload): array => [...$payload, 'previous_sha256' => $parent]);
            $column($confirmed->id, ['previous_sha256' => $parent]);
        },
    ] as $case => $corrupt) {
        DB::beginTransaction();
        DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER primary_reservation_versions_immutable');
        $corrupt();
        expect(app(CampaignFundingEvidence::class)->find($campaign->id))->toBeArray($case)
            ->and(fn () => $source->facts($requoted->id))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED');
        DB::rollBack();
    }
    expect($source->facts($requoted->id)['confirmation_sha256'])->toBe($confirmed->sha256);
});

it('refuses funding evidence that disagrees with the authenticated replay even past the funding verifier', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first]] = PrimaryHoldingFixture::committed();
    $retained = app(CampaignFundingEvidence::class)->find($campaign->id);
    // Every retained revision authenticates and replays; only the final comparison can see the funding purchase disagree.
    $forge = fn (Closure $change): CampaignFundingEvidence => new readonly class([...$retained, 'commitments' => array_map(fn (array $purchase): array => $purchase['commitment_id'] === $first->id ? $change($purchase) : $purchase, $retained['commitments'])]) implements CampaignFundingEvidence
    {
        /** @param array<string, mixed> $evidence */
        public function __construct(private array $evidence) {}

        /** @return array<string, mixed> */
        public function find(string $campaignId): array
        {
            return $this->evidence;
        }
    };
    foreach ([
        'terms' => fn (array $purchase): array => [...$purchase, 'terms' => [...$purchase['terms'], 'payout_fee' => ['currency' => 'RWF', 'amount' => '1']]],
        'ordinals' => fn (array $purchase): array => [...$purchase, 'ordinals' => [['first' => '1', 'last' => '500'], ['first' => '501', 'last' => '1080']]],
    ] as $case => $change) {
        app()->instance(CampaignFundingEvidence::class, $forge($change));
        expect(fn () => app(HoldingSource::class)->facts($first->id))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED');
    }
    app()->forgetInstance(CampaignFundingEvidence::class);
    expect(app(HoldingSource::class)->facts($first->id)['commitment_id'])->toBe($first->id);
});

it('refuses a commitment that is unknown, only held, or outside a funding record', function (): void {
    ['campaign' => $campaign, 'commitments' => [$unfunded]] = PrimaryHoldingFixture::committed(['1080'], fund: false);
    $held = PrimaryReservationFixture::reserve($campaign, PrimaryReservationFixture::investor(), '600')['data']['reservation_id'];
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    ['root' => $root, 'confirmed' => $confirmed] = holdingSource($unfunded);
    $row = ['id' => PrimaryHoldingFixture::id(), 'business_campaign_id' => $campaign->id, 'commitment_id' => $unfunded->id, 'primary_reservation_id' => $root->id,
        'party_id' => $root->party_id, 'units' => 1080, 'principal' => '5400000', 'ordinals' => '[{"first":1,"last":1080}]',
        'rights' => json_encode($root->payload['rights']), 'terms' => json_encode($confirmed->payload['terms']), 'reservation_sha256' => $root->sha256,
        'confirmation_version_id' => $confirmed->id, 'confirmation_revision' => $confirmed->revision, 'confirmation_sha256' => $confirmed->sha256,
        'disbursement_closing_id' => $closing, 'schedule' => '[{"index":1}]', 'issued_at' => '2027-01-31T09:00:00Z', 'disbursement_effective_at' => '2027-01-31T08:00:00Z',
        'effective_date' => '2027-01-31', 'receipt_id' => PrimaryHoldingFixture::id(), 'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()];
    $message = 'A Holding must name a commitment retained in a funding record';
    holdingRefused(fn () => DB::table('primary_holdings')->insert($row), $message);
    holdingRefused(fn () => DB::table('primary_holdings')->insert([...$row, 'commitment_id' => PrimaryHoldingFixture::id()]), $message);
    holdingRefused(fn () => DB::table('primary_holdings')->insert([...$row, 'commitment_id' => $held]), $message);
    holdingRefused(fn () => DB::table('primary_holdings')->insert([...$row, 'commitment_id' => $confirmed->id]), $message);

    // The declared foreign keys refuse the same rows on their own, with the trigger out of the way.
    DB::statement('ALTER TABLE primary_holdings DISABLE TRIGGER primary_holdings_protected');
    holdingRefused(fn () => DB::table('primary_holdings')->insert([...$row, 'commitment_id' => $held]), 'primary_holding_commitment', '23503');
    holdingRefused(fn () => DB::table('primary_holdings')->insert($row), 'primary_holding_funding_member', '23503');
    DB::statement('ALTER TABLE primary_holdings ENABLE TRIGGER primary_holdings_protected');
    holdingRefused(fn () => DB::table('primary_holdings')->insert($row), $message);
});

it('declares restricting foreign keys to the retained commitment, funding member, reservation and revision', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $second]] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $other = holdingSource($second);
    expect(DB::table('pg_constraint')->whereRaw("conrelid = 'primary_holdings'::regclass AND contype = 'f'")->orderBy('conname')
        ->selectRaw('conname, pg_get_constraintdef(oid) AS definition')->pluck('definition', 'conname')->all())->toBe([
            'primary_holding_commitment' => 'FOREIGN KEY (commitment_id) REFERENCES primary_commitments(id) ON DELETE RESTRICT',
            'primary_holding_confirmation' => 'FOREIGN KEY (confirmation_version_id, primary_reservation_id) REFERENCES primary_reservation_versions(id, primary_reservation_id) ON DELETE RESTRICT',
            'primary_holding_funding_member' => 'FOREIGN KEY (commitment_id) REFERENCES primary_funding_commitments(commitment_id) ON DELETE RESTRICT',
            'primary_holding_reservation' => 'FOREIGN KEY (primary_reservation_id) REFERENCES primary_reservations(id) ON DELETE RESTRICT',
            'primary_holdings_disbursement_closing_id_foreign' => 'FOREIGN KEY (disbursement_closing_id) REFERENCES disbursement_closings(id) ON DELETE RESTRICT',
        ]);
    DB::statement('ALTER TABLE primary_holdings DISABLE TRIGGER primary_holdings_protected');
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['primary_reservation_id' => PrimaryHoldingFixture::id()]), 'primary_holding_reservation', '23503');
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['confirmation_version_id' => $other['confirmed']->id]), 'primary_holding_confirmation', '23503');
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing, ['units' => 1]), 'primary_holding_facts');
});

it('refuses a Holding for a commitment whose principal was refunded', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $second]] = PrimaryHoldingFixture::committed();
    $root = holdingSource($first)['root'];
    // #175 refuses this refund today. The gate is lifted here only to stand in for the authoritative failed-closing refund S3-C still owns.
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER primary_funding_refund_gate');
    $wallets = app(WalletPostings::class);
    $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal), new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    PrimaryHoldingFixture::flushDeferredChecks();
    DB::statement('ALTER TABLE ledger_entries ENABLE TRIGGER primary_funding_refund_gate');
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing), 'A refunded commitment cannot become a Holding');
    PrimaryHoldingFixture::insert($second->id, $closing);
});

it('rolls the binding back to the proposed table exactly and refuses either direction over Holdings', function (): void {
    $binding = require database_path('migrations/2026_09_30_084737_bind_primary_holdings_to_retained_commitments.php');
    // 114217 sits on top of the binding; it has its own round trip in PrimaryHoldingIssueEvidenceTest.
    (require database_path('migrations/2026_09_30_114217_require_issue_evidence_for_primary_holdings.php'))->down();
    $proposed = require database_path('migrations/2026_09_29_100100_create_primary_holdings_table.php');
    $shape = fn (): array => [DB::scalar("SELECT pg_get_functiondef('protect_primary_holding()'::regprocedure)"),
        DB::select("SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conrelid = 'primary_holdings'::regclass ORDER BY conname"),
        DB::select("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'primary_holdings' ORDER BY ordinal_position"),
        DB::select("SELECT tgname, pg_get_triggerdef(oid) AS definition FROM pg_trigger WHERE tgrelid = 'primary_holdings'::regclass AND NOT tgisinternal ORDER BY tgname")];
    $bound = $shape();
    $binding->down();
    $restored = $shape();
    $proposed->down();
    $proposed->up();
    expect($shape())->toEqual($restored)->and($restored)->not->toEqual($bound)
        ->and($restored[0])->not->toContain('primary_commitments')->and($bound[0])->toContain('primary_funding_commitments');

    // A Holding written against the unbound proposal carries no source pins, so the binding refuses to install over it.
    ['campaign' => $campaign, 'commitments' => [$first]] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $unpinned = array_diff_key(PrimaryHoldingFixture::row($first->id, $closing),
        array_flip(['primary_reservation_id', 'reservation_sha256', 'confirmation_version_id', 'confirmation_revision', 'confirmation_sha256']));
    DB::transaction(function () use ($binding, $unpinned): void {
        DB::table('primary_holdings')->insert([...$unpinned, 'commitment_id' => PrimaryHoldingFixture::id()]);
        // The statement text repeats the guard's message, so the SQLSTATE and the raised error line are what prove the guard fired.
        $refusal = null;
        try {
            $binding->up();
        } catch (QueryException $exception) {
            $refusal = $exception;
        }
        expect($refusal?->getCode())->toBe('23514')
            ->and($refusal?->getMessage())->toContain('ERROR:  Existing Holdings require verified source pins through a forward migration');
        DB::rollBack();
        DB::beginTransaction();
    });
    $binding->up();
    expect($shape())->toEqual($bound);
    PrimaryHoldingFixture::insert($first->id, $closing);
    expect(fn () => $binding->down())->toThrow(QueryException::class, 'ERROR:  Issued Holdings require a forward migration; rollback is refused')
        ->and($shape())->toEqual($bound);
});

/**
 * The SQL a read issues, with fixture setup excluded.
 *
 * @template T
 *
 * @param  Closure(): T  $read
 * @return array{0: T, 1: list<string>}
 */
function holdingQueries(Closure $read): array
{
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });
    $result = $read();

    // The copy keeps only this read's SQL; the listener's later appends go to the original array.
    return [$result, $queries];
}

it('gives a campaign\'s verified facts keyed by commitment, each equal to that commitment\'s own facts, without locks or writes', function (): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed(['720', '720', '720'], [0, 2]);
    $ledger = DB::table('ledger_entries')->orderBy('id')->get()->toJson();
    $funding = app(CampaignFundingEvidence::class)->find($campaign->id);
    [$facts, $queries] = holdingQueries(fn (): array => app(HoldingSource::class)->campaignFacts($campaign->id));
    $ids = array_map(fn (PrimaryCommitment $commitment): string => $commitment->id, $commitments);
    sort($ids, SORT_STRING);
    expect(array_keys($facts))->toBe($ids)
        ->and(array_map(fn (string $id): int => $facts[$id]['confirmation_revision'], $ids))->toEqualCanonicalizing([3, 2, 3]);
    foreach ($commitments as $commitment) {
        expect($facts[$commitment->id])->toBe(app(HoldingSource::class)->facts($commitment->id));
    }
    foreach ($queries as $query) {
        expect($query)->toStartWith('select')->not->toContain('for update', 'for share', 'for no key update', 'for key share');
    }
    expect(DB::table('ledger_entries')->orderBy('id')->get()->toJson())->toBe($ledger)
        ->and(app(CampaignFundingEvidence::class)->find($campaign->id))->toBe($funding);
});

it('reads a campaign\'s facts in a linear number of queries, so a per-commitment replay could not pass', function (int $members): void {
    // The campaign's 2,160 units split evenly between that many buyers, two of whom requote.
    $units = array_map(fn (): string => (string) intdiv(2160, $members), range(1, $members));
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed($units, [0, 1]);
    [$facts, $queries] = holdingQueries(fn (): array => app(HoldingSource::class)->campaignFacts($campaign->id));
    // Funding row and bindings, seven reads per purchase including original cash receipts, then three batched reads.
    $ceiling = 6 + 7 * $members;
    expect($facts)->toHaveCount($members)->and(count($queries))->toBeLessThanOrEqual($ceiling);
    // Replaying commitment by commitment re-verifies the whole funding for each one, which this ceiling refuses.
    [, $perCommitment] = holdingQueries(fn (): array => array_map(fn (PrimaryCommitment $commitment): array => app(HoldingSource::class)->facts($commitment->id), $commitments));
    expect(count($perCommitment))->toBeGreaterThan($ceiling);
})->with([
    'three funded members' => [3],
    'twelve funded members' => [12],
]);

it('refuses a campaign whose funding is missing, duplicated, names an unknown commitment or no longer replays', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $second]] = PrimaryHoldingFixture::committed(['1080', '1080'], [1]);
    ['campaign' => $unfunded] = PrimaryHoldingFixture::committed(['1080'], fund: false);
    $source = app(HoldingSource::class);
    expect(fn () => $source->campaignFacts($unfunded->id))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_UNAVAILABLE')
        ->and(fn () => $source->campaignFacts(PrimaryHoldingFixture::id()))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_UNAVAILABLE');

    $retained = app(CampaignFundingEvidence::class)->find($campaign->id);
    $evidence = fn (array $commitments): CampaignFundingEvidence => new readonly class([...$retained, 'commitments' => $commitments]) implements CampaignFundingEvidence
    {
        /** @param array<string, mixed> $evidence */
        public function __construct(private array $evidence) {}

        /** @return array<string, mixed> */
        public function find(string $campaignId): array
        {
            return $this->evidence;
        }
    };
    [$one, $two] = $retained['commitments'];
    foreach ([
        'a purchase listed twice' => [[$one, $one, $two], 'PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED'],
        'a purchase naming no retained commitment' => [[$one, [...$two, 'commitment_id' => PrimaryHoldingFixture::id()]], 'PRIMARY_HOLDING_SOURCE_UNAVAILABLE'],
    ] as $case => [$commitments, $refusal]) {
        app()->instance(CampaignFundingEvidence::class, $evidence($commitments));
        expect(fn () => app(HoldingSource::class)->campaignFacts($campaign->id))->toThrow(RuntimeException::class, $refusal);
    }
    app()->forgetInstance(CampaignFundingEvidence::class);

    ['revisions' => [, $requote]] = holdingSource($second);
    DB::beginTransaction();
    DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER primary_reservation_versions_immutable');
    DB::table('primary_reservation_versions')->where('id', $requote->id)->update(['previous_sha256' => str_repeat('0', 64)]);
    expect(app(CampaignFundingEvidence::class)->find($campaign->id))->toBeArray()
        ->and(fn () => $source->campaignFacts($campaign->id))->toThrow(RuntimeException::class, 'PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED');
    DB::rollBack();
    expect(array_keys($source->campaignFacts($campaign->id)))->toEqualCanonicalizing([$first->id, $second->id]);
});
