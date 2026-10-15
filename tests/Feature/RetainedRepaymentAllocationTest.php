<?php

declare(strict_types=1);

use App\Application\Business\Contracts\NoteServicing;
use App\Application\Primary\GetRetainedNoteSchedule;
use App\Domain\Primary\RepaymentComponentAllocation;
use App\Domain\Primary\UnitOrdinals;
use App\Domain\Primary\UnitRights;
use App\Infrastructure\Business\UnavailableNoteServicing;
use Brick\Math\BigInteger;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryIssuanceFixture;

it('clears exactly original authenticated component rights across independent receipts without database effects', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close(units: ['1', '1079', '1080'], buyers: [0 => 'same', 1 => 'same']);
    $reader = app(GetRetainedNoteSchedule::class);
    $original = DB::transaction(fn () => $reader->find($campaign->campaignId));
    $this->travel(3)->years();
    $snapshot = DB::transaction(fn () => $reader->find($campaign->campaignId));
    expect($snapshot)->toEqual($original);
    $units = BigInteger::of($snapshot->principal)->quotient(UnitRights::UNIT_PRINCIPAL)->toInt();
    $unitRights = [];
    foreach (range(1, $units) as $ordinal) {
        $unitRights[$ordinal] = UnitRights::allocate($snapshot->principal, array_column($snapshot->instalments, 'gross'),
            UnitOrdinals::fromRanges((string) $units, [['first' => (string) $ordinal, 'last' => (string) $ordinal]]));
    }
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    $level = DB::transactionLevel();
    foreach ($snapshot->instalments as $offset => $instalment) {
        foreach (['principal' => 'principal', 'return' => 'contractualReturn'] as $component => $property) {
            $unpaid = [];
            foreach ($unitRights as $ordinal => $rights) {
                $unpaid[] = ['first' => (string) $ordinal, 'last' => (string) $ordinal, 'unpaid' => $rights->instalments[$offset][$component]];
            }
            foreach ($snapshot->holdings as $holding) {
                $originalAmount = BigInteger::zero();
                foreach ($holding->ordinals as $range) {
                    foreach (range($range['first'], $range['last']) as $ordinal) {
                        $originalAmount = $originalAmount->plus($unitRights[$ordinal]->instalments[$offset][$component]);
                    }
                }
                expect((string) $originalAmount)->toBe($holding->instalments[$offset]->{$property});
            }
            $total = BigInteger::of($instalment->{$property});
            $credited = array_fill_keys(array_column($snapshot->holdings, 'holdingId'), BigInteger::zero());
            $applied = BigInteger::zero();
            foreach (['1', (string) $total->quotient(3), (string) $total] as $receipt) {
                $plan = RepaymentComponentAllocation::allocate($receipt, $unpaid);
                expect(RepaymentComponentAllocation::allocate($receipt, array_reverse($unpaid)))->toEqual($plan)
                    ->and((string) BigInteger::of($plan->applied)->plus($plan->unapplied))->toBe($receipt);
                $applied = $applied->plus($plan->applied);
                foreach ($snapshot->holdings as $holding) {
                    foreach ($holding->ordinals as $owned) {
                        foreach ($plan->ranges as $range) {
                            $count = BigInteger::max(0, BigInteger::min($owned['last'], $range['last'])
                                ->minus(BigInteger::max($owned['first'], $range['first']))->plus(1));
                            $credited[$holding->holdingId] = $credited[$holding->holdingId]->plus($count->multipliedBy($range['paid']));
                        }
                    }
                }
                $unpaid = array_map(fn (array $range): array => ['first' => $range['first'], 'last' => $range['last'], 'unpaid' => $range['remaining']], $plan->ranges);
            }
            expect((string) $applied)->toBe((string) $total)->and(array_unique(array_column($unpaid, 'unpaid')))->toBe(['0']);
            foreach ($snapshot->holdings as $holding) {
                expect((string) $credited[$holding->holdingId])->toBe($holding->instalments[$offset]->{$property});
            }
        }
    }
    expect($queries)->toBeEmpty()->and(DB::transactionLevel())->toBe($level)
        ->and(get_class(app(NoteServicing::class)))->toBe(UnavailableNoteServicing::class)
        ->and($snapshot)->toEqual($original);
});
