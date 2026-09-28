<?php

declare(strict_types=1);

use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('allows only one terminal revision when two independent PostgreSQL transactions race', function (): void {
    $this->freezeSecond();
    $reservation = DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create());
    $pids = [];
    DB::disconnect();
    for ($index = 0; $index < 2; $index++) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork a Primary contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                DB::transaction(fn () => PrimaryReservationVersion::factory()->create([
                    'primary_reservation_id' => $reservation->id, 'revision' => 2, 'state' => 'released',
                ]));
                exit(0);
            } catch (QueryException $exception) {
                exit($exception->getCode() === '23514' ? 2 : 3);
            } catch (Throwable) {
                exit(4);
            }
        }
        $pids[] = $pid;
    }
    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $statuses[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
    }
    sort($statuses);
    expect($statuses)->toBe([0, 2])
        ->and(PrimaryReservationVersion::query()->orderBy('revision')->pluck('state')->all())->toBe(['held', 'released']);
});

it('holds Business campaign and reservation locks until the outer transaction ends', function (): void {
    $this->freezeSecond();
    $reservation = DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create());
    $campaign = DB::table('business_campaigns')->where('id', $reservation->business_campaign_id)->first();
    config(['database.connections.primary_schema_contender' => config('database.connections.pgsql')]);
    $connection = DB::connection('primary_schema_contender');
    $connection->statement("SET lock_timeout = '200ms'");
    $targets = ['business_profiles' => $campaign->business_id, 'business_campaigns' => $campaign->id, 'primary_reservations' => $reservation->id];
    try {
        DB::beginTransaction();
        DB::transaction(fn () => PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $reservation->id]));
        foreach ($targets as $table => $id) {
            expect(fn () => $connection->transaction(fn () => $connection->table($table)->where('id', $id)->lockForUpdate()->first()))
                ->toThrow(QueryException::class, 'lock timeout');
        }
        DB::rollBack();
        foreach ($targets as $table => $id) {
            expect($connection->transaction(fn () => $connection->table($table)->where('id', $id)->lockForUpdate()->first())?->id)->toBe($id);
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('primary_schema_contender');
    }
});
