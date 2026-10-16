<?php

declare(strict_types=1);

use App\Application\Business\WithAuditApplicationBinding;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessApplicationVersion;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessQuoteFixture as Fixture;

/** @param array<string, mixed> $fixture
 * @return array<string, mixed>
 */
function auditSubmittedApplicationBinding(array $fixture, ?string $applicationId = null): array
{
    return app(WithAuditApplicationBinding::class)->handle($fixture['audit']['partners'][0]['user']->id, 1,
        $fixture['assignment']->id, $applicationId ?? $fixture['application']->id,
        fn (array $assignment, array $binding): array => ['assignment' => $assignment, 'binding' => $binding]);
}

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 10:00:00', 'UTC'));
});

it('pins the submitted application version quote and mandate without changing frozen history', function (): void {
    $fixture = Fixture::ready(2);
    $acceptance = Fixture::acceptance($fixture);
    Fixture::submit($fixture, $acceptance);
    Fixture::submit($fixture, $acceptance, 1);
    $application = $fixture['application']->refresh();
    $before = $application->getRawOriginal();
    $versions = BusinessApplicationVersion::query()->where('business_application_id', $application->id)->count();
    $result = auditSubmittedApplicationBinding($fixture);
    $binding = $result['binding'];
    $version = BusinessApplicationVersion::query()->where('business_application_id', $application->id)->where('revision', $application->revision)->firstOrFail();
    $quote = BusinessApplicationQuote::query()->findOrFail($application->current_quote_id);
    $submission = BusinessApplicationSubmission::query()->findOrFail($application->current_submission_id);

    expect($result['assignment']['id'])->toBe($fixture['assignment']->id)
        ->and($binding['application']['id'])->toBe($application->id)
        ->and($binding['application']['revision'])->toBe($application->revision)
        ->and($binding['version'])->toBe(['id' => $version->id, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($version->snapshot))])
        ->and($binding['submission'])->toBe(['id' => $submission->id, 'sha256' => $submission->sha256, 'submitted_at' => $submission->payload['submitted_at']])
        ->and($binding['application'])->toBe(['id' => $application->id, 'business_id' => $application->business_id, 'revision' => $application->revision])
        ->and($binding['quote'])->toBe(['id' => $quote->id, 'revision' => $quote->revision, 'sha256' => $quote->sha256])
        ->and($binding['mandate'])->toBe(['version' => $submission->payload['agreement']['mandate_version'],
            'sha256' => hash('sha256', app(CanonicalJson::class)->encode($submission->payload['agreement']['mandate']))])
        ->and($application->refresh()->getRawOriginal())->toBe($before)
        ->and(BusinessApplicationVersion::query()->where('business_application_id', $application->id)->count())->toBe($versions);
    expect(json_encode($binding, JSON_THROW_ON_ERROR))->not->toContain('scorecard', 'credit_source_reference', 'actor_user_id', 'actor_party_id',
        'recurring_owner_draw', 'obligations', 'pricing', 'capacity', 'payload', 'terms', 'draft');
});

it('does not bind a draft or an application with only one of its required signatures', function (bool $partlySigned): void {
    $fixture = Fixture::ready(2);
    if ($partlySigned) {
        Fixture::submit($fixture, Fixture::acceptance($fixture));
    }

    expect(fn () => auditSubmittedApplicationBinding($fixture))->toThrow(CommandRejection::class, 'APPLICATION_NOT_SUBMITTED');
})->with([false, true]);

it('gives the same scoped denial for another Business application and an unknown one', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $other = BusinessApplicationFixture::make();
    foreach ([$other['application']->id, (string) Str::ulid()] as $id) {
        expect(fn () => auditSubmittedApplicationBinding($fixture, $id))->toThrow(CommandRejection::class, 'APPLICATION_NOT_FOUND');
    }
});

it('rechecks current assignment and identity authority before returning a submitted binding', function (string $withdrawal): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $partner = $fixture['audit']['partners'][0];
    auditSubmittedApplicationBinding($fixture);
    if ($withdrawal === 'conflict') {
        $receipt = AuditAssignmentFixture::respond($partner['user'], $fixture['assignment']->refresh(), 'conflict', 'A related-party tie was identified.', 'family_or_business');
        expect($receipt['code'])->toBe('CONFLICT_RECORDED');
    } else {
        RoleMembership::query()->where('party_id', $partner['party']->id)->update(['status' => 'suspended']);
    }

    expect(fn () => auditSubmittedApplicationBinding($fixture))->toThrow($withdrawal === 'conflict' ? CommandRejection::class : IdentityViolation::class);
})->with(['conflict', 'membership']);

it('keeps the historical binding when the current quotation month changes', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $before = auditSubmittedApplicationBinding($fixture)['binding'];
    $this->travelTo(now()->startOfMonth()->addMonth()->addDay());

    expect(auditSubmittedApplicationBinding($fixture)['binding'])->toBe($before);
});

it('keeps the caller effect inside the accepted authority transaction and rolls it back on failure', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $request = (string) Str::uuid();
    $userId = $fixture['audit']['partners'][0]['user']->id;

    expect(fn () => app(WithAuditApplicationBinding::class)->handle($userId, 1, $fixture['assignment']->id, $fixture['application']->id,
        function (array $assignment, array $binding) use ($request, $userId): void {
            expect(DB::transactionLevel())->toBeGreaterThan(0);
            app(OperationJournal::class)->execute('party:'.$assignment['party_id'], $userId, 'audit.binding.fixture', $request,
                'audit.assignment', $assignment['id'], ['application_id' => $binding['application']['id']], function (): void {},
                fn (): OperationResult => new OperationResult('SYNTHETIC_REPORT_BINDING', [], 1));
            throw new RuntimeException('Report creation failed.');
        }))->toThrow(RuntimeException::class, 'Report creation failed.');
    expect(CommandOperation::query()->where('request_id', $request)->exists())->toBeFalse();
});

it('refuses incomplete or inconsistent immutable application history', function (string $fault): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $model = match ($fault) {
        'missing_version' => BusinessApplication::class,
        'version_snapshot' => BusinessApplicationVersion::class,
        default => BusinessApplicationSubmission::class,
    };
    $event = 'eloquent.retrieved: '.$model;
    Event::listen($event, function (BusinessApplication|BusinessApplicationVersion|BusinessApplicationSubmission $record): void {
        if ($record instanceof BusinessApplication) {
            $record->revision++;
        } elseif ($record instanceof BusinessApplicationVersion) {
            $snapshot = $record->snapshot;
            $snapshot['draft']['title'] = 'Different application facts';
            $record->snapshot = $snapshot;
        } else {
            $payload = $record->payload;
            $payload['review']['application']['draft']['title'] = 'Different submitted facts';
            $record->payload = $payload;
            $record->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
        }
    });
    try {
        expect(fn () => auditSubmittedApplicationBinding($fixture))->toThrow(RuntimeException::class, 'APPLICATION_VERSION_INTEGRITY_FAILED');
    } finally {
        Event::forget($event);
    }
})->with(['missing_version', 'version_snapshot', 'submission_snapshot']);

it('refuses a changed historical quote even when its digest is recalculated', function (string $fault): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $event = 'eloquent.retrieved: '.BusinessApplicationQuote::class;
    Event::listen($event, function (BusinessApplicationQuote $record) use ($fault): void {
        if ($fault === 'digest') {
            $record->sha256 = str_repeat('0', 64);
        } else {
            $payload = $record->payload;
            $payload['draft']['target'] = '12500000';
            $record->payload = $payload;
            $record->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
        }
    });
    try {
        expect(fn () => auditSubmittedApplicationBinding($fixture))->toThrow(RuntimeException::class, 'APPLICATION_QUOTE_INTEGRITY_FAILED');
    } finally {
        Event::forget($event);
    }
})->with(['digest', 'changed_facts']);
