<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\FindAuditCosignOperation;
use App\Models\AuditPublicationEvent;
use App\Models\AuditReport;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\AuditReportSignature;
use App\Models\AuditSigningKeyRevocation;
use App\Models\AuditStepUpProof;
use App\Models\CommandOperation;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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

it('serializes amendment and co-sign commands so only a pre-amendment signature can publish the parent', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $amendRequest = (string) Str::uuid();
    $signRequest = (string) Str::uuid();
    expect(auditSigningContenders([
        function () use ($fixture, $amendRequest): void {
            expect(app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id,
                $fixture['report']->revision + 1, $amendRequest)['code'])->toBe('AUDIT_AMENDMENT_CREATED');
        },
        function () use ($fixture, $signRequest): void {
            expect(Fixture::cosign($fixture, overrides: ['requestId' => $signRequest])['code'])->toBeIn(['REPORT_PUBLISHED', 'AUDIT_REPORT_AMENDED']);
        },
    ]))->toBe([0, 0]);
    $receipt = app(FindAuditCosignOperation::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $signRequest);
    $published = $receipt['code'] === 'REPORT_PUBLISHED';
    expect(AuditReport::query()->where('amends_id', $fixture['report']->id)->count())->toBe(1)
        ->and(AuditReportPublication::query()->firstOrFail()->status)->toBe($published ? 'published' : 'pending')
        ->and(AuditReportSignature::query()->count())->toBe($published ? 1 : 0);
});

it('holds the same parent lock against direct competing amendment or signature inserts', function (bool $amendFirst): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $parent = $fixture['report']->fresh();
    $publication = AuditReportPublication::query()->firstOrFail();
    $default = DB::getDefaultConnection();
    $event = 'eloquent.creating: '.($amendFirst ? AuditReport::class : AuditReportSignature::class);
    Event::listen($event, function () use ($amendFirst, $fixture, $parent, $publication, $default): void {
        config(['database.connections.audit_lineage_contender' => config('database.connections.pgsql')]);
        DB::connection('audit_lineage_contender')->statement("SET lock_timeout = '500ms'");
        DB::setDefaultConnection('audit_lineage_contender');
        try {
            if ($amendFirst) {
                expect(fn () => AuditReportSignature::factory()->forPublication($publication, $fixture['audit']['authority']['users'][0])->create())
                    ->toThrow(QueryException::class, 'lock timeout');
            } else {
                $child = $parent->replicate();
                $child->forceFill(['amends_id' => $parent->id, 'revision' => 1, 'status' => 'draft', 'step' => 'review',
                    'draft' => ['note' => '', 'completed_steps' => [], 'fields' => []]]);
                expect(fn () => $child->save())->toThrow(QueryException::class, 'lock timeout');
            }
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('audit_lineage_contender');
        }
    });
    try {
        if ($amendFirst) {
            expect(app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $parent->id, $parent->revision, (string) Str::uuid())['code'])
                ->toBe('AUDIT_AMENDMENT_CREATED');
        } else {
            expect(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
        }
    } finally {
        Event::forget($event);
    }
    if ($amendFirst) {
        expect(Fixture::cosign($fixture)['code'])->toBe('AUDIT_REPORT_AMENDED')->and(AuditReportSignature::query()->count())->toBe(0);
    } else {
        expect(app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $parent->id, $parent->revision, (string) Str::uuid())['code'])
            ->toBe('AUDIT_AMENDMENT_CREATED')->and($publication->fresh()->status)->toBe('published');
    }
})->with([false, true]);

it('serializes monthly signers so only one independently attributed signature publishes', function (): void {
    $fixture = Fixture::ready(2, 'monthly');
    Fixture::seal($fixture);
    expect(auditSigningContenders(array_map(fn (int $signer): Closure => function () use ($fixture, $signer): void {
        expect(Fixture::cosign($fixture, $signer)['code'])->toBeIn(['REPORT_PUBLISHED', 'VERSION_CONFLICT']);
    }, [0, 1])))->toBe([0, 0])->and(AuditReportSignature::query()->count())->toBe(1)
        ->and(AuditReportPublication::query()->firstOrFail()->published_reason)->toBe('signed');
});

it('serializes a monthly dispute with the due worker without publishing a disputed report', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $due = AuditReportPublication::query()->firstOrFail()->due_at;
    $input = ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => 1,
        'report_revision' => $fixture['report']->revision + 1, 'mandate_version' => 1, 'digest' => $fixture['fields']['digest'],
        'supporting_text' => 'Please review the retained supporting receipt.'];
    expect(auditSigningContenders([
        function () use ($fixture, $input, $due): void {
            CarbonImmutable::setTestNow($due->subSecond());
            Carbon::setTestNow($due->subSecond());
            $result = app(AuditReportPublicationStore::class)->dispute(
                $fixture['audit']['authority']['users'][0]->id, $fixture['audit']['business'], $fixture['report']->id, $input, []);
            expect($result['code'])->toBeIn(['REPORT_DISPUTED', 'VERSION_CONFLICT']);
        },
        function () use ($due): void {
            CarbonImmutable::setTestNow($due);
            Carbon::setTestNow($due);
            app(AuditReportPublicationStore::class)->advanceDue();
        },
    ]))->toBe([0, 0]);
    $publication = AuditReportPublication::query()->firstOrFail();
    expect($publication->status)->toBeIn(['published', 'disputed'])->and($publication->revision)->toBe(2)
        ->and(AuditReportSignature::query()->count())->toBe(0)
        ->and($publication->review !== null)->toBe($publication->status === 'disputed');
});

it('holds current signing key authority through automatic monthly publication', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $this->travel(25)->hours();
    $default = DB::getDefaultConnection();
    $event = 'eloquent.creating: '.AuditPublicationEvent::class;
    Event::listen($event, function ($event) use ($default, $fixture): void {
        if ($event->command !== 'report.auto_approve') {
            return;
        }
        config(['database.connections.monthly_key_contender' => config('database.connections.pgsql')]);
        DB::connection('monthly_key_contender')->statement("SET lock_timeout = '500ms'");
        DB::setDefaultConnection('monthly_key_contender');
        try {
            expect(fn () => AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]))->toThrow(QueryException::class, 'lock timeout');
        } finally {
            DB::setDefaultConnection($default);
            DB::purge('monthly_key_contender');
        }
    });
    try {
        expect(app(AuditReportPublicationStore::class)->advanceDue()['published'])->toBe(1);
    } finally {
        Event::forget($event);
    }
});
