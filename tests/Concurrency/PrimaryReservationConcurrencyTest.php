<?php

declare(strict_types=1);

use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

it('takes trigger locks in Business campaign reservation order while another transaction blocks progress', function (string $trigger, string $blockedTable): void {
    $this->freezeSecond();
    $reservation = DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create());
    $campaign = DB::table('business_campaigns')->where('id', $reservation->business_campaign_id)->first();
    $isVersion = in_array($trigger, ['primary_campaign_open', 'primary_reservation_version_valid'], true);
    $table = $isVersion ? 'primary_reservation_versions' : 'primary_reservations';
    $triggers = $isVersion ? ['primary_campaign_open', 'primary_reservation_version_valid'] : ['primary_campaign_capacity', 'primary_reservation_valid'];
    $sibling = array_values(array_diff($triggers, [$trigger]))[0];
    $values = $isVersion
        ? PrimaryReservationVersion::factory()->make(['primary_reservation_id' => $reservation->id])->getAttributes()
        : PrimaryReservationRecord::factory()->make(['business_campaign_id' => $campaign->id])->getAttributes();
    $values['id'] = strtolower((string) Str::ulid());
    $targets = ['business_profiles' => $campaign->business_id, 'business_campaigns' => $campaign->id, 'primary_reservations' => $reservation->id];
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create the trigger race barrier.');
    }
    stream_set_timeout($channels[0], 8);
    stream_set_timeout($channels[1], 8);
    DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER '.$sibling);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        DB::statement('ALTER TABLE '.$table.' ENABLE TRIGGER '.$sibling);
        throw new RuntimeException('Could not fork the trigger contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '5s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            DB::beginTransaction();
            DB::table($table)->insert($values);
            DB::rollBack();
            exit(0);
        } catch (Throwable) {
            exit(4);
        }
    }
    fclose($channels[1]);
    config(['database.connections.primary_order_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('primary_order_observer');
    $childStatus = null;
    try {
        $backend = trim((string) fgets($channels[0]));
        expect(ctype_digit($backend))->toBeTrue();
        DB::beginTransaction();
        DB::table($blockedTable)->where('id', $targets[$blockedTable])->lockForUpdate()->first();
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 3_000_000_000;
        $blocked = false;
        while (hrtime(true) < $deadline) {
            $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
            if ($blocked) {
                break;
            }
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                $childStatus = $status;
                break;
            }
            usleep(10000);
        }
        expect($blocked)->toBeTrue('The isolated trigger must acquire the blocked parent lock.');
        if ($blockedTable === 'business_profiles') {
            expect($observer->transaction(fn () => $observer->table('business_campaigns')->where('id', $campaign->id)->lock('FOR UPDATE NOWAIT')->first())?->id)
                ->toBe($campaign->id, 'The campaign must still be unlocked while waiting for Business.');
        } else {
            expect(fn () => $observer->transaction(fn () => $observer->table('business_profiles')->where('id', $campaign->business_id)->lock('FOR UPDATE NOWAIT')->first()))
                ->toThrow(QueryException::class, 'could not obtain lock');
        }
        if ($isVersion) {
            expect($observer->transaction(fn () => $observer->table('primary_reservations')->where('id', $reservation->id)->lock('FOR UPDATE NOWAIT')->first())?->id)
                ->toBe($reservation->id, 'The reservation must still be unlocked while waiting for either parent.');
        }
    } finally {
        DB::rollBack();
        if ($childStatus === null) {
            pcntl_waitpid($pid, $childStatus);
        }
        fclose($channels[0]);
        DB::purge('primary_order_observer');
        DB::statement('ALTER TABLE '.$table.' ENABLE TRIGGER '.$sibling);
    }
    expect(pcntl_wifexited($childStatus) ? pcntl_wexitstatus($childStatus) : -1)->toBe(0);
})->with(['primary_reservation_valid', 'primary_campaign_capacity', 'primary_reservation_version_valid', 'primary_campaign_open'])
    ->with(['business_profiles', 'business_campaigns']);
