<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\AuditReportSignature;
use App\Models\AuditSigningKeyRevocation;
use App\Models\AuditStepUpProof;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture as Fixture;

/**
 * @param  list<Closure(): void>  $operations
 * @return list<int>
 */
function auditSigningContenders(array $operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork an audit signing contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                $operation();
                exit(0);
            } catch (Throwable) {
                exit(1);
            }
        }
        $pids[] = $pid;
    }
    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $exitStatus = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : false;
        if ($exitStatus === false) {
            throw new RuntimeException('An audit signing contender did not exit normally.');
        }
        $statuses[] = $exitStatus;
    }

    return $statuses;
}

it('seals once across simultaneous retries and competing request IDs', function (bool $sameRequest): void {
    $fixture = Fixture::ready();
    $proof = Fixture::proof($fixture)['proof'];
    $first = (string) Str::uuid();
    $seal = fn (string $request): Closure => function () use ($fixture, $proof, $request): void {
        expect(Fixture::seal($fixture, $proof, $request)['code'])->toBeIn(['AUDIT_SEALED', 'VERSION_CONFLICT']);
    };
    expect(auditSigningContenders([$seal($first), $seal($sameRequest ? $first : (string) Str::uuid())]))->toBe([0, 0])
        ->and(AuditReportSeal::query()->count())->toBe(1)->and(AuditReportPublication::query()->count())->toBe(1)
        ->and(AuditStepUpProof::query()->whereNotNull('consumed_at')->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'audit.seal')->count())->toBe($sameRequest ? 1 : 2);
})->with([false, true]);

it('serializes distinct mandated signatures and publishes only after the losing signer refreshes its revision', function (): void {
    $fixture = Fixture::ready(2);
    Fixture::seal($fixture);
    $sign = fn (int $index): Closure => function () use ($fixture, $index): void {
        expect(Fixture::cosign($fixture, $index)['code'])->toBeIn(['REPORT_COSIGNATURE_RECORDED', 'VERSION_CONFLICT']);
    };
    expect(auditSigningContenders([$sign(0), $sign(1)]))->toBe([0, 0])->and(AuditReportSignature::query()->count())->toBe(1)
        ->and(AuditReportPublication::query()->firstOrFail()->status)->toBe('pending');
    $signed = AuditReportSignature::query()->firstOrFail()->actor_party_id;
    $missing = $fixture['audit']['authority']['users'][0]->party_id === $signed ? 1 : 0;
    expect(Fixture::cosign($fixture, $missing, 2)['code'])->toBe('REPORT_PUBLISHED')
        ->and(AuditReportSignature::query()->count())->toBe(2)->and(AuditReportPublication::query()->firstOrFail()->revision)->toBe(3);
});

it('holds signing-key validity through the actual seal or Business signature write', function (bool $cosign): void {
    $fixture = Fixture::ready();
    if ($cosign) {
        Fixture::seal($fixture);
    }
    $default = DB::getDefaultConnection();
    $event = 'eloquent.creating: '.($cosign ? AuditReportSignature::class : AuditReportSeal::class);
    Event::listen($event, function () use ($default, $fixture): void {
        config(['database.connections.seal_key_contender' => config('database.connections.pgsql')]);
        DB::connection('seal_key_contender')->statement("SET lock_timeout = '500ms'");
        DB::setDefaultConnection('seal_key_contender');
        try {
            expect(fn () => AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]))
                ->toThrow(QueryException::class, 'lock timeout');
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('seal_key_contender');
        }
    });
    try {
        $result = $cosign ? Fixture::cosign($fixture) : Fixture::seal($fixture);
        expect($result['code'])->toBe($cosign ? 'REPORT_PUBLISHED' : 'AUDIT_SEALED');
    } finally {
        Event::forget($event);
    }
    expect(AuditSigningKeyRevocation::query()->count())->toBe(0);
    AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]);
    $seal = AuditReportSeal::query()->firstOrFail();
    expect(app(AuditReportCryptography::class)->verify($seal->audit_signing_key_id, $seal->jws, $seal->payload))->toBeFalse();
})->with([false, true]);
