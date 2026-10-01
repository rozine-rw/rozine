<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessConnections;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\DB;
use Tests\Support\BusinessAuthorityFixture;

it('refuses a declaration read without an outer transaction before taking a lock', function (): void {
    expect(DB::transactionLevel())->toBe(0)
        ->and(fn () => app(BusinessConnections::class)->lockDeclared('missing'))
        ->toThrow(LogicException::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('serializes current declaration reads with real mandate revocation in either order', function (bool $readerFirst): void {
    $this->freezeSecond();
    $fixture = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($fixture);
    $id = BusinessProfile::query()->sole()->id;
    $fixture['terms']['status'] = 'revoked';
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create connection source barrier.');
    }
    foreach ($channels as $channel) {
        stream_set_timeout($channel, 10);
    }
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork connection source contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        DB::statement("SET lock_timeout = '6s'");
        fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
        if (fgets($channels[1]) !== "go\n") {
            exit(3);
        }
        try {
            $outcome = $readerFirst ? BusinessAuthorityFixture::configure($fixture, 1)['code']
                : DB::transaction(fn (): string => app(BusinessConnections::class)->lockDeclared($id)['mandate_id']);
        } catch (CommandRejection $exception) {
            $outcome = $exception->reason;
        } catch (Throwable $exception) {
            $outcome = $exception::class;
        }
        fwrite($channels[1], $outcome."\n");
        fclose($channels[1]);
        exit(0);
    }
    fclose($channels[1]);
    $status = 0;
    $blocked = false;
    $query = '';
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        DB::beginTransaction();
        if ($readerFirst) {
            expect(app(BusinessConnections::class)->lockDeclared($id)['mandate_version'])->toBe(1);
        } else {
            expect(BusinessAuthorityFixture::configure($fixture, 1)['code'])->toBe('BUSINESS_AUTHORITY_RECORDED');
        }
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 4_000_000_000;
        while (hrtime(true) < $deadline && ! $blocked) {
            $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
            if (! $blocked) {
                usleep(10_000);
            }
        }
        $query = DB::selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$backend])->query;
        DB::commit();
        $outcome = trim((string) fgets($channels[0]));
        expect($blocked)->toBeTrue()
            ->and($query)->toContain('"business_profiles"')->toContain('for update')
            ->and($outcome)->toBe($readerFirst ? 'BUSINESS_AUTHORITY_RECORDED' : 'MANDATE_REQUIRED');
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        $deadline = hrtime(true) + 8_000_000_000;
        while (pcntl_waitpid($pid, $status, WNOHANG) === 0 && hrtime(true) < $deadline) {
            usleep(10_000);
        }
        if (pcntl_waitpid($pid, $status, WNOHANG) === 0) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
    }
    expect(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0);
    expect(fn () => DB::transaction(fn (): array => app(BusinessConnections::class)->lockDeclared($id)))
        ->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
})->with(['read first' => [true], 'revocation first' => [false]]);
