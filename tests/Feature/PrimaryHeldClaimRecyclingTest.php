<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->hold = function (string $units, ?array $investor = null): PrimaryReservationRecord {
        $investor ??= $this->investor;
        $result = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units,
            (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($result['code'])->toBe('RESERVATION_HELD');

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->release = function (PrimaryReservationRecord $root, ?array $investor = null): void {
        $investor ??= $this->investor;
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
        expect($this->checkout->release($investor['user']->id, 1, $this->campaign->id, $root->id, $version->revision,
            (string) Str::uuid())['code'])->toBe('RESERVATION_RELEASED');
    };
    $this->summary = fn (): array => app(CampaignReservationSummary::class)->read($this->campaign->id, now()->toDateTimeImmutable());
});

it('partially and repeatedly reuses whole returned roots preserving holes and original claim evidence', function (): void {
    $a = ($this->hold)('4');
    $original = DB::table('primary_ordinal_claims')->orderBy('ordinal')->get()->toJson();
    ($this->release)($a);
    expect(($this->summary)()['occupied_units'])->toBe('4');
    $b = ($this->hold)('2');
    expect($b->payload['ordinals'])->toBe([['first' => '1', 'last' => '2']])
        ->and(($this->summary)())->toMatchArray(['occupied_units' => '2', 'returned_units' => '4']);
    ($this->release)($b);
    $c = ($this->hold)('4');
    expect($c->payload['ordinals'])->toBe([['first' => '1', 'last' => '4']])
        ->and(DB::table('primary_ordinal_claims')->orderBy('ordinal')->get()->toJson())->toBe($original)
        ->and(DB::table('primary_held_claim_generations')->orderBy('ordinal')->orderBy('generation')->get()
            ->map(fn (object $g): array => [$g->ordinal, $g->generation, $g->previous_reservation_id, $g->primary_reservation_id])->all())
        ->toBe([[1, 1, $a->id, $b->id], [1, 2, $b->id, $c->id], [2, 1, $a->id, $b->id], [2, 2, $b->id, $c->id],
            [3, 1, $a->id, $c->id], [4, 1, $a->id, $c->id]]);
    ($this->release)($c);
    $d = ($this->hold)('3');
    expect($d->payload['ordinals'])->toBe([['first' => '1', 'last' => '3']])
        ->and(($this->summary)())->toMatchArray(['occupied_units' => '3', 'returned_units' => '10', 'returned_principal' => '50000']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('uses the same retired roots for Party cap capacity funding and readonly occupancy without losing historical returns', function (): void {
    $a = ($this->hold)('1080');
    ($this->release)($a);
    $b = ($this->hold)('1080');
    expect($b->payload['ordinals'])->toBe($a->payload['ordinals']);
    ($this->release)($b);
    $c = ($this->hold)('1080');
    ($this->release)($c);
    $d = ($this->hold)('1080');
    ($this->release)($d);
    $e = ($this->hold)('1080');
    ($this->release)($e);
    $returns = app(PrimaryReservations::class)->lockReturnedCampaign($this->campaign->id);
    expect($returns->returns)->toHaveCount(5)->and($returns->releasedPrincipal)->toBe('27000000');
    expect(($this->summary)())->toMatchArray(['returned_units' => '5400', 'occupied_units' => '1080']);
    $first = ($this->hold)('1080');
    $other = PrimaryReservationFixture::investor();
    $second = ($this->hold)('1080', $other);
    foreach ([[$first, $this->investor], [$second, $other]] as [$root, $investor]) {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        expect($this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(),
            PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    }
    $candidate = app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id);
    expect(array_column($candidate->purchases, 'reservation_id'))->toBe(collect([$first->id, $second->id])->sort()->values()->all());
    $funding = app(PrimaryFunding::class)->lock($this->campaign->id, fn (string $id): array => ['campaign_id' => $id,
        'publication_sha256' => $this->campaign->sha256, ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'],
            ['status' => 'passed', 'evidence' => ['synthetic' => 'Persistence test only, no current authority.']])]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect($funding['commitments'])->toHaveCount(2)->and(($this->summary)()['occupied_units'])->toBe('2160');
    $forged = (array) DB::table('primary_held_claim_releases')->where('primary_reservation_id', $a->id)->sole();
    $forged['primary_reservation_id'] = $first->id;
    $forged['root_sha256'] = $first->sha256;
    $confirmedVersion = PrimaryReservationVersion::query()->where('primary_reservation_id', $first->id)->orderByDesc('revision')->firstOrFail();
    $forged['version_id'] = $confirmedVersion->id;
    $forged['version_sha256'] = $confirmedVersion->sha256;
    expect(fn () => DB::transaction(fn () => DB::table('primary_held_claim_releases')->insert($forged)))
        ->toThrow(QueryException::class, 'Funded claim retirement is prohibited')
        ->and(DB::table('primary_held_claim_releases')->where('primary_reservation_id', $first->id)->exists())->toBeFalse();
});

it('keeps all historical original returns in cancellation after repeated reuse', function (): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    $b = ($this->hold)('2');
    ($this->release)($b);
    $c = ($this->hold)('4');
    ($this->release)($c);
    $result = app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, null, (string) Str::uuid());
    expect($result['code'])->toBe('CAMPAIGN_CANCELLED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(DB::table('primary_campaign_closure_returns')->count())->toBe(3)
        ->and(app(PrimaryReservations::class)->lockReturnedCampaign($this->campaign->id)->releasedPrincipal)->toBe('50000');
});

it('never recycles overdue held or confirmed refunded roots', function (bool $confirmed): void {
    $a = ($this->hold)('4');
    if ($confirmed) {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $a->id)->sole();
        $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $a->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        app(PrimaryReservations::class)->refund($this->campaign->id, $a->id, $a->party_id, 2);
    } else {
        $this->travelTo($a->expires_at);
    }
    $b = ($this->hold)('2');
    expect($b->payload['ordinals'])->toBe([['first' => '5', 'last' => '6']])
        ->and(DB::table('primary_held_claim_releases')->count())->toBe(0)->and(($this->summary)()['occupied_units'])->toBe('6');
})->with([false, true]);

it('authenticates full replay original cash and binding on every retired summary and new allocation', function (string $damage): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    ($this->hold)('2');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    if ($damage === 'cash') {
        DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
        $entry = LedgerEntry::query()->where('source_id', $a->id)->where('kind', 'primary_release')->sole();
        $entry->forceFill(['sha256' => str_repeat('0', 64)])->save();
    } elseif ($damage === 'ancestry') {
        DB::statement('ALTER TABLE primary_held_claim_generations DISABLE TRIGGER USER');
        DB::table('primary_held_claim_generations')->where('ordinal', 1)->update(['generation' => 2]);
    } elseif ($damage === 'binding') {
        DB::statement('ALTER TABLE primary_held_claim_releases DISABLE TRIGGER USER');
        DB::table('primary_held_claim_releases')->update(['sha256' => str_repeat('0', 64)]);
    } else {
        DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER USER');
        $v = PrimaryReservationVersion::query()->where('primary_reservation_id', $a->id)->orderBy('revision')->firstOrFail();
        $v->forceFill(['sha256' => str_repeat('0', 64)])->save();
    }
    $before = PrimaryReservationRecord::query()->count();
    expect(fn () => DB::transaction(fn () => ($this->summary)()))->toThrow(match ($damage) {
        'cash' => WalletViolation::class, 'ancestry' => QueryException::class, default => RuntimeException::class,
    });
    expect(fn () => ($this->hold)('1'))->toThrow(match ($damage) {
        'cash' => WalletViolation::class, 'ancestry' => QueryException::class, default => RuntimeException::class,
    })
        ->and(PrimaryReservationRecord::query()->count())->toBe($before);
})->with(['cash', 'ancestry', 'binding', 'root replay']);

it('rolls retirement and generations cash and feed back on cash admission feed failure or outer rollback', function (string $failure): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    $before = [PrimaryReservationRecord::query()->count(), LedgerEntry::query()->count(), DB::table('change_feed')->count()];
    if ($failure === 'feed') {
        $feed = $this->createMock(ChangeFeed::class);
        $feed->method('record')->willThrowException(new RuntimeException('synthetic-feed-failure'));
        $this->app->instance(ChangeFeed::class, $feed);
        $this->checkout = app(PrimaryCheckout::class);
    }
    if ($failure === 'cash') {
        $investor = PrimaryReservationFixture::investor('5000');
        $result = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, '2', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($result['status'])->toBe('rejected');
    } else {
        try {
            DB::transaction(function () use ($failure): void {
                $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '2', (string) Str::uuid(),
                    $failure === 'admission' ? fn () => throw new CommandRejection('POLICY_INPUT_REQUIRED') : PrimaryReservationFixture::terms(...));
                if ($failure === 'admission') {
                    expect($result['status'])->toBe('rejected');
                } elseif ($failure === 'rollback') {
                    throw new RuntimeException('synthetic-outer-rollback');
                }
            });
        } catch (RuntimeException $exception) {
            expect($exception->getMessage())->toBe($failure === 'feed' ? 'synthetic-feed-failure' : 'synthetic-outer-rollback');
        }
    }
    expect(DB::table('primary_held_claim_releases')->count())->toBe(0)
        ->and(DB::table('primary_held_claim_generations')->count())->toBe(0)
        ->and(PrimaryReservationRecord::query()->count())->toBe($before[0]);
    if ($failure !== 'cash') {
        expect([LedgerEntry::query()->count(), DB::table('change_feed')->count()])->toBe(array_slice($before, 1));
    }
})->with(['cash', 'admission', 'feed', 'rollback']);

it('refuses generation mutation and downgrade after authenticated retirement evidence', function (): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    ($this->hold)('2');
    foreach (['primary_held_claim_releases', 'primary_held_claim_generations'] as $table) {
        expect(fn () => DB::transaction(fn () => DB::table($table)->delete()))->toThrow(QueryException::class, 'immutable');
    }
    $migration = require database_path('migrations/2026_10_03_083345_create_primary_held_claim_generations.php');
    expect(fn () => DB::transaction(fn () => $migration->down()))->toThrow(QueryException::class, 'forward migration')
        ->and(Schema::hasTable('primary_held_claim_generations'))->toBeTrue();
});

it('recycles an authenticated held expiry but keeps all historical returns in later expiry closure', function (): void {
    $a = ($this->hold)('4');
    $this->travelTo($a->expires_at);
    expect(app(PrimaryReservations::class)->expireDue(1))->toBe(1);
    $b = ($this->hold)('2');
    expect($b->payload['ordinals'])->toBe([['first' => '1', 'last' => '2']]);
    $this->travelTo($this->campaign->expires_at);
    expect(app(BusinessCampaignStore::class)->expireDue(1))->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(DB::table('primary_campaign_closure_returns')->count())->toBe(2)
        ->and(app(PrimaryReservations::class)->lockReturnedCampaign($this->campaign->id)->releasedPrincipal)->toBe('30000');
});

it('checks original full root return rather than successor principal and rejects invalid retirement inputs', function (string $damage): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    $b = ($this->hold)('2');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    if ($damage === 'issued cash kind') {
        DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
        expect(fn () => DB::transaction(fn () => LedgerEntry::query()->where('source_id', $a->id)
            ->where('kind', 'primary_release')->sole()->forceFill(['kind' => 'primary_issue'])->save()))
            ->toThrow(QueryException::class, 'ledger_entry_source')
            ->and(($this->summary)()['occupied_units'])->toBe('2');

        return;
    }
    if (in_array($damage, ['cash kinds', 'committed cash kind'], true)) {
        DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
        LedgerEntry::query()->where('source_id', $a->id)->where('kind', 'primary_release')->sole()->forceFill(['kind' => match ($damage) {
            'committed cash kind' => 'primary_commit', default => 'primary_refund',
        }])->save();
    } elseif ($damage === 'missing return') {
        DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
        DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER USER');
        $entry = LedgerEntry::query()->where('source_id', $a->id)->where('kind', 'primary_release')->sole();
        DB::table('ledger_lines')->where('entry_id', $entry->id)->delete();
        // Keep the bound entry: absence of original full credit is still an integrity refusal.
    } elseif ($damage === 'wallet owner') {
        $other = PrimaryReservationFixture::investor();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        $otherWallet = DB::table('investor_wallets')->where('party_id', $other['party']->id)->value('id');
        DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
        LedgerEntry::query()->where('source_id', $a->id)->where('kind', 'primary_hold')->sole()->forceFill(['wallet_id' => $otherWallet])->save();
    } elseif ($damage === 'binding column') {
        DB::statement('ALTER TABLE primary_held_claim_releases DISABLE TRIGGER USER');
        DB::table('primary_held_claim_releases')->update(['root_sha256' => str_repeat('0', 64)]);
    } else {
        DB::statement('ALTER TABLE primary_reservation_versions DISABLE TRIGGER USER');
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $a->id)->orderByDesc('revision')->firstOrFail();
        $payload = $version->payload;
        $payload['state'] = 'held';
        $version->forceFill(['state' => 'held', 'payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    }
    expect(fn () => DB::transaction(fn () => ($this->summary)()))->toThrow(match ($damage) {
        'binding column', 'still held' => RuntimeException::class, default => WalletViolation::class,
    });
})->with(['cash kinds', 'committed cash kind', 'issued cash kind', 'missing return', 'wallet owner', 'binding column', 'still held']);

it('validates bound retained purchase facts while remaining SELECT only', function (string $damage): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    ($this->hold)('2');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER USER');
    $payload = $a->payload;
    if ($damage === 'quote') {
        $payload['campaign_units'] = 2;
    } elseif ($damage === 'payment') {
        $payload['campaign_payments'][0] = 1;
    } elseif ($damage === 'ordinal list') {
        $payload['ordinals'] = null;
    } elseif ($damage === 'ordinal endpoint') {
        $payload['ordinals'][0]['first'] = 1;
    } else {
        $payload['campaign_payments'][0] = '1';
    }
    $a->forceFill(['payload' => $payload])->save();
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    expect(fn () => app(CampaignReservationSummary::class)->read($this->campaign->id, now()->toDateTimeImmutable()))
        ->toThrow(RuntimeException::class);
    foreach ($queries as $sql) {
        expect(strtolower($sql))->not->toMatch('/for update|insert |update |delete /');
    }
})->with(['quote', 'payment', 'retained schedule', 'ordinal list', 'ordinal endpoint']);

it('audits install without rewriting history and reverses only before recycling evidence', function (): void {
    $a = ($this->hold)('4');
    $roots = PrimaryReservationRecord::query()->get()->toJson();
    $claims = DB::table('primary_ordinal_claims')->get()->toJson();
    $migration = require database_path('migrations/2026_10_03_083345_create_primary_held_claim_generations.php');
    $migration->down();
    expect(Schema::hasTable('primary_held_claim_generations'))->toBeFalse();
    $migration->up();
    expect(PrimaryReservationRecord::query()->get()->toJson())->toBe($roots)
        ->and(DB::table('primary_ordinal_claims')->get()->toJson())->toBe($claims);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER USER');
    $a->forceFill(['sha256' => str_repeat('0', 64)])->save();
    $migration->down();
    expect(fn () => DB::transaction(fn () => $migration->up()))->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED')
        ->and(Schema::hasTable('primary_held_claim_generations'))->toBeFalse();
});

it('refuses native generation forks old root cycles out of range successors and incomplete retirement cash', function (string $damage): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    $b = ($this->hold)('2');
    $c = ($this->hold)('2');
    if ($damage === 'cash binding') {
        $binding = (array) DB::table('primary_held_claim_releases')->sole();
        $binding['primary_reservation_id'] = $b->id;
        $binding['root_sha256'] = $b->sha256;
        $v = PrimaryReservationVersion::query()->where('primary_reservation_id', $b->id)->sole();
        $binding['version_id'] = $v->id;
        $binding['version_sha256'] = $v->sha256;
        expect(fn () => DB::transaction(fn () => DB::table('primary_held_claim_releases')->insert($binding)))
            ->toThrow(QueryException::class, 'full original terminal held cash return');
    } else {
        $successor = $damage === 'old root cycle' ? $a->id : $c->id;
        expect(fn () => DB::transaction(fn () => DB::table('primary_held_claim_generations')->insert([
            'business_campaign_id' => $this->campaign->id, 'ordinal' => 1, 'generation' => $damage === 'fork' ? 1 : 2,
            'previous_reservation_id' => $a->id, 'primary_reservation_id' => $successor,
        ])))->toThrow(QueryException::class, 'exact previous root');
    }
})->with(['fork', 'old root cycle', 'out of range', 'cash binding']);

it('refuses native overlapping or over capacity live roots despite returned gross history', function (bool $capacity): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    $b = ($this->hold)('2');
    $row = (array) DB::table('primary_reservations')->where('id', $b->id)->sole();
    $row['id'] = strtolower((string) Str::ulid());
    if ($capacity) {
        $row['principal'] = '10800000';
        $row['units'] = 2160;
        $row['ordinal_ranges'] = '{[1,2161)}';
    }
    expect(fn () => DB::transaction(fn () => DB::table('primary_reservations')->insert($row)))
        ->toThrow(QueryException::class, $capacity ? 'campaign capacity' : 'cannot overlap');
})->with([false, true]);

it('audits original terminal cash cryptography before install without creating retirement', function (bool $corrupt): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    $migration = require database_path('migrations/2026_10_03_083345_create_primary_held_claim_generations.php');
    $migration->down();
    if ($corrupt) {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
        LedgerEntry::query()->where('kind', 'primary_release')->update(['sha256' => str_repeat('0', 64)]);
        expect(fn () => DB::transaction(fn () => $migration->up()))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
            ->and(Schema::hasTable('primary_held_claim_releases'))->toBeFalse();
    } else {
        $migration->up();
        expect(DB::table('primary_held_claim_releases')->count())->toBe(0)
            ->and(DB::table('primary_ordinal_claims')->count())->toBe(4);
    }
})->with([false, true]);

it('keeps readonly held cash inspection source scoped without wallet creation or locking', function (): void {
    $a = ($this->hold)('4');
    ($this->release)($a);
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    $cash = app(PrimaryReturnedCash::class);
    $amount = WalletMoney::of($a->principal);
    $binding = $cash->inspectHeldReturn($a->party_id, $amount,
        new PostingSource('primary_reservation', $a->id, $a->origin_operation_id));
    expect($binding['return_entry_id'])->toBe(LedgerEntry::query()->where('kind', 'primary_release')->sole()->id);
    expect(fn () => $cash->inspectHeldReturn($a->party_id, $amount,
        new PostingSource('primary_commitment', $a->id, $a->origin_operation_id)))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_SOURCE_INVALID');
    foreach ($queries as $sql) {
        expect(strtolower($sql))->not->toMatch('/for update|insert |update |delete /');
    }
});
