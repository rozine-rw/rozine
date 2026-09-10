<?php

use App\Application\Pulse\RegisterPulseBusiness;
use App\Domain\Pulse\PulseSector;
use App\Models\PulseSignup;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
 * Waitlist numbering under real concurrency.
 *
 * These assertions exist because the previous allocation read `count() + 1`
 * outside any lock, which hands the same number to two people who sign up at
 * the same moment. SQLite serialises every writer and so cannot tell a correct
 * implementation from that one; only PostgreSQL can, which is why D-73 makes it
 * the authoritative environment for this evidence and why this suite is
 * registered in phpunit.pgsql.xml alone.
 */

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
});

/**
 * @return array<string, mixed>
 */
function registerBusiness(string $contact): array
{
    return app(RegisterPulseBusiness::class)->handle(
        [
            'name' => 'Concurrent Trading '.$contact,
            'contact_method' => 'phone',
            'contact' => $contact,
            'province' => 'Kigali',
            'district' => 'Gasabo',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'pest',
        ],
        annualRevenue: 48_000_000,
        annualCosts: 30_000_000,
        sector: PulseSector::RetailAndTrade,
        registeredYear: 2019,
        termMonths: 12,
        listed: true,
        currentYear: 2026,
    );
}

it('serialises the counter claim so a second writer waits instead of reading the same value', function (): void {
    DB::table('signup_counters')->insertOrIgnore(['name' => 'queue_number:business', 'value' => 0]);

    // A second, genuinely separate connection: same server, own PDO handle,
    // own transaction. Registered here rather than in config/database.php
    // because nothing outside this test has any use for it.
    config(['database.connections.pgsql_contender' => config('database.connections.pgsql')]);

    $holder = DB::connection();
    $contender = DB::connection('pgsql_contender');

    $holder->beginTransaction();
    $holder->table('signup_counters')->where('name', 'queue_number:business')->lockForUpdate()->first();

    // A contender that could read the row would read the pre-claim value and
    // hand out a duplicate. Bounding the wait turns "blocks forever" into an
    // assertable outcome instead of a hung suite.
    $contender->statement("SET lock_timeout = '750ms'");

    $blocked = false;

    try {
        $contender->beginTransaction();
        $contender->table('signup_counters')->where('name', 'queue_number:business')->lockForUpdate()->first();
        $contender->rollBack();
    } catch (QueryException $exception) {
        $blocked = str_contains($exception->getMessage(), 'lock timeout')
            || str_contains($exception->getMessage(), 'canceling statement');
        $contender->rollBack();
    }

    $holder->rollBack();

    expect($blocked)->toBeTrue('the counter row was readable mid-claim, so two signups can take the same number');
});

it('issues a distinct number to every signup that arrives in parallel', function (): void {
    $writers = 8;
    $pids = [];

    for ($writer = 0; $writer < $writers; $writer++) {
        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->fail('could not fork a writer');
        }

        if ($pid === 0) {
            // The child inherits the parent's socket; sharing it would corrupt
            // both sides of the conversation.
            DB::purge();

            try {
                registerBusiness('+25078'.str_pad((string) (1_000_000 + $writer), 7, '0', STR_PAD_LEFT));
                exit(0);
            } catch (Throwable) {
                exit(1);
            }
        }

        $pids[] = $pid;
    }

    $failures = 0;

    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $failures += pcntl_wexitstatus($status) === 0 ? 0 : 1;
    }

    expect($failures)->toBe(0, 'a parallel signup failed outright');

    $signups = PulseSignup::query()->businesses()->get();

    expect($signups)->toHaveCount($writers)
        ->and($signups->pluck('queue_number')->unique())->toHaveCount($writers)
        ->and($signups->pluck('loan_number')->unique())->toHaveCount($writers);
});

it('refuses to store a duplicate queue number even when the counter is bypassed', function (): void {
    registerBusiness('+250780000001');

    $issued = PulseSignup::query()->businesses()->sole();

    expect(fn () => DB::table('pulse_signups')->insert([
        'type' => 'business',
        'name' => 'Bypasses the counter',
        'contact_method' => 'phone',
        'contact' => '+250780000002',
        'province' => 'Kigali',
        'district' => 'Gasabo',
        'queue_number' => $issued->queue_number,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('never reissues a number after a signup is deleted', function (): void {
    registerBusiness('+250780000003');
    $second = registerBusiness('+250780000004');

    PulseSignup::query()->businesses()->firstOrFail()->delete();

    $third = registerBusiness('+250780000005');

    expect($third['queue_number'])->not->toBe($second['queue_number'])
        ->and((int) preg_replace('/\D/', '', $third['queue_number']))
        ->toBeGreaterThan((int) preg_replace('/\D/', '', $second['queue_number']));
});
