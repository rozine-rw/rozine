<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\HoldingSource;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryHolding;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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

beforeEach(fn () => $this->freezeSecond());

it('accepts one immutable Holding per commitment that equals its retained facts', function (): void {
    ['campaign' => $campaign, 'commitments' => [$first, $requoted, $last]] = PrimaryHoldingFixture::committed(['1080', '600', '480'], [1]);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $holdings = [PrimaryHoldingFixture::insert($first->id, $closing)];
    holdingRefused(fn () => PrimaryHoldingFixture::insert($first->id, $closing), 'primary_holdings_commitment_id_unique', '23505');
    $holdings = [...$holdings, PrimaryHoldingFixture::insert($requoted->id, $closing),
        PrimaryHoldingFixture::insert($last->id, $closing, ['payload' => encrypt(json_encode(['source' => 'synthetic']), false)])];
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
