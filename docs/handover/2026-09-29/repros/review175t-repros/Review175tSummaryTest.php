<?php

declare(strict_types=1);

/*
 * Review probes for PR #175 range ec64f0ea..c87b6e08 (CampaignReservationSummary). Copy into tests/Feature/.
 * PROBE = should already hold. OBSERVE = pins current behaviour the review reports as a design note.
 */

use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessCampaign;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->summary = app(CampaignReservationSummary::class);
});

function r175tRoot(BusinessCampaign $campaign, int $units, ?string $partyId = null): PrimaryReservationRecord
{
    return PrimaryReservationRecord::factory()->withInitialVersion()->create([
        'business_campaign_id' => $campaign->id, 'units' => $units, 'principal' => (string) ($units * 5000),
        ...($partyId === null ? [] : ['party_id' => $partyId]),
    ]);
}

function r175tVersion(PrimaryReservationRecord $root, int $revision, string $state): PrimaryReservationVersion
{
    $version = PrimaryReservationVersion::factory()->withCashMovement()->create([
        'primary_reservation_id' => $root->id, 'revision' => $revision, 'state' => $state,
        'created_at' => $state === 'expired' ? $root->expires_at : now(),
    ]);
    if ($state === 'confirmed') {
        PrimaryCommitment::factory()->create(['primary_reservation_version_id' => $version->id]);
    }

    return $version;
}

it('PROBE latest version is chosen by revision when every version shares one created_at', function (): void {
    $campaign = BusinessCampaign::factory()->create();
    $confirmed = r175tRoot($campaign, 2);
    r175tVersion($confirmed, 2, 'held');
    r175tVersion($confirmed, 3, 'confirmed');
    $released = r175tRoot($campaign, 3);
    r175tVersion($released, 2, 'held');
    r175tVersion($released, 3, 'held');
    r175tVersion($released, 4, 'released');
    expect(PrimaryReservationVersion::query()->distinct()->count('created_at'))->toBe(1)
        ->and($this->summary->read($campaign->id, now()->toDateTimeImmutable()))->toMatchArray([
            'committed_principal' => '10000', 'committed_units' => '2', 'investors' => 1,
            'held_units' => '0', 'returned_principal' => '15000', 'returned_units' => '3', 'occupied_units' => '5']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE investors counts distinct committed Parties only, across many reservations and states of one Party', function (): void {
    $campaign = BusinessCampaign::factory()->create();
    $a = Party::factory()->create()->id;
    $b = Party::factory()->create()->id;
    $c = Party::factory()->create()->id;
    foreach ([1, 2, 3] as $units) {
        r175tVersion(r175tRoot($campaign, $units, $a), 2, 'confirmed');
    }
    r175tRoot($campaign, 4, $a);
    r175tVersion(r175tRoot($campaign, 5, $a), 2, 'released');
    r175tRoot($campaign, 6, $b);
    r175tVersion(r175tRoot($campaign, 7, $c), 2, 'released');
    $other = BusinessCampaign::factory()->create();
    r175tVersion(r175tRoot($other, 1, $b), 2, 'confirmed');
    expect($this->summary->read($campaign->id, now()->toDateTimeImmutable()))->toMatchArray([
        'committed_principal' => '30000', 'committed_units' => '6', 'investors' => 1,
        'held_units' => '10', 'returned_units' => '12', 'occupied_units' => '28']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE overdue boundary is half-open in every timezone at microsecond precision', function (): void {
    $campaign = BusinessCampaign::factory()->create();
    $held = r175tRoot($campaign, 3);
    $deadline = $held->expires_at->toDateTimeImmutable();
    $zone = new DateTimeZone('Pacific/Kiritimati');
    $before = $this->summary->read($campaign->id, $deadline->modify('-1 microsecond')->setTimezone($zone));
    $at = $this->summary->read($campaign->id, $deadline->setTimezone($zone));
    expect([$before['held_units'], $before['expired_hold_units']])->toBe(['3', '0'])
        ->and([$at['held_units'], $at['expired_hold_units'], $at['expired_hold_principal']])->toBe(['0', '3', '15000']);
});

it('PROBE overdue classification keeps sub-second precision of the instant and the deadline', function (): void {
    $this->travelTo(now()->startOfSecond()->addMicroseconds(500000));
    $campaign = BusinessCampaign::factory()->create();
    $held = PrimaryReservationRecord::factory()->withInitialVersion()->create([
        'business_campaign_id' => $campaign->id, 'units' => 2, 'principal' => '10000', 'created_at' => now()]);
    $deadline = $held->expires_at->toDateTimeImmutable();
    expect($deadline->format('u'))->toBe('500000')
        ->and($this->summary->read($campaign->id, $deadline->modify('+200000 microseconds'))['expired_hold_units'])->toBe('2')
        ->and($this->summary->read($campaign->id, $deadline->modify('-200000 microseconds'))['held_units'])->toBe('2');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE exact strings at the campaign maximum and one database statement per read', function (): void {
    $campaign = BusinessCampaign::factory()->create(['principal' => '100000000']);
    r175tVersion(r175tRoot($campaign, 10000), 2, 'confirmed');
    r175tVersion(r175tRoot($campaign, 10000), 2, 'confirmed');
    $statements = [];
    DB::listen(function (QueryExecuted $query) use (&$statements): void {
        $statements[] = $query->sql;
    });
    $read = $this->summary->read($campaign->id, now()->toDateTimeImmutable());
    expect($read['committed_principal'])->toBe('100000000')->and($read['committed_units'])->toBe('20000')
        ->and($read['occupied_units'])->toBe('20000')->and($read['investors'])->toBe(2)
        ->and(count($statements))->toBe(1);
    $types = DB::selectOne('SELECT pg_typeof(SUM(principal))::text AS p, pg_typeof(SUM(units))::text AS u FROM primary_reservations');
    expect([$types->p, $types->u])->toBe(['numeric', 'bigint']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('OBSERVE a refunded commitment is still reported as committed principal and as an investor', function (): void {
    $campaign = BusinessCampaign::factory()->create();
    $root = r175tRoot($campaign, 2);
    r175tVersion($root, 2, 'confirmed');
    $wallets = app(WalletPostings::class);
    try {
        $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
        $refunded = true;
    } catch (Throwable $exception) {
        $refunded = $exception::class.': '.$exception->getMessage();
    }
    $read = $this->summary->read($campaign->id, now()->toDateTimeImmutable());
    fwrite(STDERR, 'refund posted: '.json_encode($refunded).'; refunds='.LedgerEntry::query()->where('kind', 'primary_refund')->count()
        .'; summary committed='.$read['committed_principal'].' investors='.$read['investors']."\n");
    expect($refunded)->toBeTrue()->and($read['committed_principal'])->toBe('10000')->and($read['investors'])->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE real checkout paths: confirm, release, actor expiry and a live hold agree with the summary', function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    $investors = [PrimaryReservationFixture::investor(), PrimaryReservationFixture::investor(), PrimaryReservationFixture::investor()];
    $reserve = function (array $investor, string $units) use ($checkout, $campaign): PrimaryReservationRecord {
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $version = fn (PrimaryReservationRecord $root): PrimaryReservationVersion => PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->first();
    $confirm = fn (array $investor, PrimaryReservationRecord $root): array => $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
        $version($root)->payload['terms']['disclosure_version'], $version($root)->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $a1 = $reserve($investors[0], '3');
    $a2 = $reserve($investors[0], '2');
    $b1 = $reserve($investors[1], '4');
    $c1 = $reserve($investors[2], '1');
    expect($confirm($investors[0], $a1)['code'])->toBe('RESERVATION_CONFIRMED')
        ->and($confirm($investors[0], $a2)['code'])->toBe('RESERVATION_CONFIRMED')
        ->and($checkout->release($investors[1]['user']->id, 1, $campaign->id, $b1->id, 1, (string) Str::uuid())['code'])->toBe('RESERVATION_RELEASED');
    $this->travel(301)->seconds();
    $late = $reserve($investors[1], '5');
    $expired = $confirm($investors[2], $c1);
    expect($expired['code'])->toBe('RESERVATION_EXPIRED')
        ->and($this->summary->read($campaign->id, now()->toDateTimeImmutable()))->toBe([
            'committed_principal' => '25000', 'committed_units' => '5', 'investors' => 1,
            'held_principal' => '25000', 'held_units' => '5', 'expired_hold_principal' => '0', 'expired_hold_units' => '0',
            'returned_principal' => '25000', 'returned_units' => '5', 'occupied_units' => '15'])
        ->and($late->units)->toBe(5);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE EXPLAIN on a large campaign uses the campaign, revision and commitment indexes', function (): void {
    $campaign = BusinessCampaign::factory()->create(['principal' => '100000000']);
    $other = BusinessCampaign::factory()->create(['principal' => '100000000']);
    $party = Party::factory()->create()->id;
    foreach (['primary_reservations', 'primary_reservation_versions', 'primary_commitments'] as $table) {
        DB::statement("ALTER TABLE {$table} DISABLE TRIGGER USER");
    }
    $insert = function (BusinessCampaign $target, string $prefix, int $count) use ($party): void {
        DB::statement("INSERT INTO primary_reservations (id, business_campaign_id, publication_sha256, party_id, origin_operation_id, units, principal, payload, sha256, created_at, expires_at, ordinal_ranges)
            SELECT '{$prefix}' || lpad(i::text, 25, '0'), ?, ?, ?, '{$prefix}o' || lpad(i::text, 24, '0'), 1, 5000, '{}', repeat('0', 64), now(), now() + interval '300 seconds',
                ('{[' || i || ',' || (i + 1) || ')}')::int8multirange
            FROM generate_series(1, ?) AS i", [$target->id, $target->sha256, $party, $count]);
        DB::statement("INSERT INTO primary_reservation_versions (id, primary_reservation_id, revision, state, operation_id, payload, sha256, previous_sha256, created_at)
            SELECT '{$prefix}v' || r || lpad(i::text, 23, '0'), '{$prefix}' || lpad(i::text, 25, '0'), r,
                CASE WHEN r = 1 THEN 'held' WHEN i % 3 = 0 THEN 'confirmed' WHEN i % 3 = 1 THEN 'released' ELSE 'held' END,
                '{$prefix}p' || r || lpad(i::text, 23, '0'), '{}', repeat('0', 64), NULL, now()
            FROM generate_series(1, ?) AS i CROSS JOIN generate_series(1, 2) AS r", [$count]);
        DB::statement("INSERT INTO primary_commitments (id, primary_reservation_id, primary_reservation_version_id, operation_id, confirmed_at, created_at)
            SELECT '{$prefix}c' || lpad(i::text, 24, '0'), '{$prefix}' || lpad(i::text, 25, '0'), '{$prefix}v2' || lpad(i::text, 23, '0'),
                '{$prefix}q' || lpad(i::text, 24, '0'), now(), now()
            FROM generate_series(3, ?, 3) AS i", [$count]);
    };
    $insert($campaign, 'a', 15000);
    foreach (range(0, 9) as $n) {
        $insert(BusinessCampaign::factory()->create(['principal' => '100000000']), chr(98 + $n), 15000);
    }
    $small = BusinessCampaign::factory()->create(['principal' => '100000000']);
    $insert($small, 's', 90);
    DB::statement('ANALYZE primary_reservations');
    DB::statement('ANALYZE primary_reservation_versions');
    DB::statement('ANALYZE primary_commitments');
    foreach (['large' => [$campaign, 15000], 'small' => [$small, 90]] as $label => [$target, $count]) {
        $captured = null;
        DB::listen(function (QueryExecuted $query) use (&$captured): void {
            $captured ??= $query;
        });
        $started = hrtime(true);
        $read = $this->summary->read($target->id, now()->toDateTimeImmutable());
        $elapsed = (hrtime(true) - $started) / 1e6;
        $plan = collect(DB::select('EXPLAIN (ANALYZE, BUFFERS) '.$captured->sql, $captured->bindings))->pluck('QUERY PLAN')->implode("\n");
        fwrite(STDERR, "[{$label}] summary read over {$count} roots / ".($count * 2)." versions among 165090 roots: {$elapsed} ms\n".json_encode($read)."\n{$plan}\n\n");
        expect($read)->toMatchArray(['committed_units' => (string) intdiv($count, 3), 'occupied_units' => (string) $count, 'investors' => 1]);
    }
})->skip(fn (): bool => DB::getDriverName() !== 'pgsql');
