<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditReport;
use App\Application\Auditor\StartAuditReport;
use App\Application\Business\WithAuditApplicationBinding;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\AuditEngagementAcceptance;
use App\Models\AuditReport;
use App\Models\StatementVerification;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditorFixture;
use Tests\Support\BusinessQuoteFixture;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25T10:00:00Z'));
});

/** @return array<string, mixed> */
function engagementSourceScene(): array
{
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $user = $fixture['audit']['partners'][0]['user'];
    $assignment = $fixture['assignment']->refresh();
    $application = $fixture['application']->refresh();
    $receipt = app(StartAuditReport::class)->handle($user->id, 1, $assignment->id, $assignment->revision,
        $application->id, $application->revision, (string) Str::uuid());
    $report = AuditReport::query()->whereKey($receipt['data']['audit_id'])->firstOrFail();
    $verification = StatementVerification::query()->where('assignment_id', $assignment->id)->firstOrFail();

    return app(WithAuditApplicationBinding::class)->handle($user->id, 1, $assignment->id, $application->id,
        fn (array $accepted, array $binding): array => [...$fixture, 'user' => $user, 'report' => $report,
            'verification' => $verification, 'accepted' => $accepted, 'binding' => $binding]);
}

/** Reproduce the preceding schema around retained rows without deleting or changing their originals. */
function legacyEngagementSourceSchema(): void
{
    DB::unprepared(<<<'SQL'
        DROP TRIGGER audit_reports_engagement_immutable ON audit_reports;
        DROP TRIGGER z_audit_reports_engagement_binding ON audit_reports;
        DROP TRIGGER statement_verifications_engagement_binding ON statement_verifications;
        DROP TRIGGER audit_engagement_acceptance_current ON audit_engagement_acceptances;
        DROP TRIGGER audit_engagement_release_catalog ON audit_engagement_releases;
        DROP FUNCTION protect_audit_report_engagement_pin();
        DROP FUNCTION validate_audit_engagement_source_pin();
        DROP FUNCTION validate_current_audit_engagement_acceptance();
        DROP FUNCTION serialize_audit_engagement_release();
        SQL);
    foreach (['audit_reports', 'statement_verifications'] as $table) {
        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            $blueprint->dropForeign($table.'_engagement_party');
            $blueprint->dropColumn('engagement_acceptance_id');
        });
    }
    Schema::table('audit_engagement_acceptances', function (Blueprint $table): void {
        $table->dropUnique('audit_engagement_acceptance_party_key');
    });
}

it('pins actual report starts and source verifications to the retained author acceptance', function (): void {
    $scene = engagementSourceScene();
    $acceptance = $scene['accepted']['engagement']['id'];
    expect($scene['report']->engagement_acceptance_id)->toBe($acceptance)
        ->and($scene['report']->binding['engagement']['id'])->toBe($acceptance)
        ->and($scene['verification']->engagement_acceptance_id)->toBe($acceptance)
        ->and($scene['verification']->payload['assignment']['engagement']['id'])->toBe($acceptance);
});

it('rejects a fabricated missing or foreign acceptance at each source insert boundary', function (string $target, string $case): void {
    $scene = engagementSourceScene();
    $pin = match ($case) {
        'foreign' => AuditEngagementAcceptance::factory()->create()->id,
        'missing' => null,
        default => (string) Str::ulid(),
    };
    expect(fn () => DB::transaction(function () use ($scene, $target, $pin): void {
        if ($target === 'report') {
            AuditReport::factory()->forBinding($scene['accepted'], $scene['binding'])
                ->create(['amends_id' => $scene['report']->id, 'engagement_acceptance_id' => $pin]);
        } else {
            DB::table('statement_verifications')->insert([...$scene['verification']->getRawOriginal(), 'id' => (string) Str::ulid(),
                'revision' => 2, 'amends_id' => $scene['verification']->id, 'engagement_acceptance_id' => $pin]);
        }
    }))->toThrow(QueryException::class, 'Audit source must bind the author engagement acceptance');
    $this->assertDatabaseCount('audit_reports', 1);
    $this->assertDatabaseCount('statement_verifications', 1);
})->with(['report', 'verification'])->with(['fabricated', 'missing', 'foreign']);

it('refuses new source writes under superseded or withdrawn terms while preserving existing originals', function (string $target, string $status): void {
    $scene = engagementSourceScene();
    $report = $scene['report']->getRawOriginal();
    $verification = $scene['verification']->getRawOriginal();
    AuditEngagementFixture::release($scene['audit']['staff'], 1, $status);
    expect(fn () => DB::transaction(function () use ($scene, $target): void {
        if ($target === 'report') {
            AuditReport::factory()->forBinding($scene['accepted'], $scene['binding'])->create(['amends_id' => $scene['report']->id]);
        } else {
            DB::table('statement_verifications')->insert([...$scene['verification']->getRawOriginal(), 'id' => (string) Str::ulid(),
                'revision' => 2, 'amends_id' => $scene['verification']->id]);
        }
    }))->toThrow(QueryException::class, 'Audit source must bind current active engagement terms');
    expect($scene['report']->refresh()->getRawOriginal())->toBe($report)
        ->and($scene['verification']->refresh()->getRawOriginal())->toBe($verification);
})->with(['report', 'verification'])->with(['active', 'withdrawn']);

it('refuses acceptance insertion for withdrawn or superseded releases at the database boundary', function (string $status): void {
    $actor = AuditorFixture::make();
    $old = AuditEngagementFixture::release($actor['staff']);
    $latest = AuditEngagementFixture::release($actor['staff'], 1, $status);
    $release = $status === 'withdrawn' ? $latest : $old;
    expect(fn () => DB::transaction(fn (): AuditEngagementAcceptance => AuditEngagementAcceptance::factory()
        ->create(['audit_engagement_release_id' => $release->id])))->toThrow(QueryException::class, 'Audit engagement acceptance requires the current active release');
    $this->assertDatabaseCount('audit_engagement_acceptances', 0);
})->with(['active', 'withdrawn']);

it('keeps the original report pin after reacceptance and rejects a rewritten relational pin', function (): void {
    $scene = engagementSourceScene();
    $report = $scene['report'];
    $oldPin = $report->engagement_acceptance_id;
    $release = AuditEngagementFixture::release($scene['audit']['staff'], 1);
    $accepted = AuditEngagementFixture::accept($scene['user'], $release);
    expect(fn () => DB::transaction(fn (): int => DB::table('audit_reports')->where('id', $report->id)
        ->update(['engagement_acceptance_id' => $accepted['data']['acceptance']['id'], 'revision' => 2])))
        ->toThrow(QueryException::class, 'Audit report source binding is immutable');
    $receipt = app(StartAuditReport::class)->handle($scene['user']->id, 1, $scene['assignment']->id, $scene['accepted']['revision'],
        $scene['application']->id, $scene['binding']['application']['revision'], (string) Str::uuid());
    expect($receipt['code'])->toBe('AUDIT_REPORT_RESUMED')->and($receipt['data']['audit_id'])->toBe($report->id)
        ->and($report->refresh()->engagement_acceptance_id)->toBe($oldPin)
        ->and(app(GetAuditReport::class)->handle($scene['user']->id, 1, $report->id)['revision'])->toBe(1);
});

it('backfills verified historical pins after withdrawal without rewriting versions payloads timestamps or revisions', function (): void {
    $scene = engagementSourceScene();
    $report = $scene['report']->getRawOriginal();
    $verification = $scene['verification']->getRawOriginal();
    $versions = DB::table('audit_report_versions')->get()->all();
    AuditEngagementFixture::release($scene['audit']['staff'], 1, 'withdrawn');
    legacyEngagementSourceSchema();
    $migration = require database_path('migrations/2026_09_25_082804_enforce_audit_engagement_source_pins.php');
    $migration->up();
    expect($scene['report']->refresh()->getRawOriginal())->toEqualCanonicalizing($report)
        ->and($scene['verification']->refresh()->getRawOriginal())->toEqualCanonicalizing($verification)
        ->and(DB::table('audit_report_versions')->get()->all())->toEqual($versions);
    expect(fn () => DB::transaction(fn (): int => DB::table('statement_verifications')->where('id', $scene['verification']->id)->update(['sha256' => str_repeat('a', 64)])))
        ->toThrow(QueryException::class, 'Statement verifications are immutable');
});

it('rolls back the complete forward migration when retained source evidence cannot prove its acceptance pin', function (string $failure): void {
    $scene = engagementSourceScene();
    legacyEngagementSourceSchema();
    $verification = $scene['verification'];
    $payload = $verification->payload;
    if ($failure === 'missing') {
        unset($payload['assignment']['engagement']);
    } elseif ($failure === 'unknown') {
        $payload['assignment']['engagement']['id'] = (string) Str::ulid();
    } elseif ($failure === 'receipt') {
        $payload['assignment']['engagement']['sha256'] = str_repeat('a', 64);
    }
    $body = $failure === 'json' ? '{invalid' : json_encode($payload, JSON_THROW_ON_ERROR);
    DB::statement('ALTER TABLE statement_verifications DISABLE TRIGGER statement_verifications_immutable');
    DB::table('statement_verifications')->where('id', $verification->id)->update([
        'payload' => $failure === 'decrypt' ? 'not-encrypted' : Crypt::encryptString($body),
        'sha256' => $failure === 'hash' ? str_repeat('b', 64) : hash('sha256', app(CanonicalJson::class)->encode($payload)),
    ]);
    DB::statement('ALTER TABLE statement_verifications ENABLE TRIGGER statement_verifications_immutable');
    $report = DB::table('audit_reports')->first();
    $before = DB::table('statement_verifications')->first();
    $migration = require database_path('migrations/2026_09_25_082804_enforce_audit_engagement_source_pins.php');
    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'Existing audit engagement source pins require verified historical evidence.')
        ->and(Schema::hasColumn('audit_reports', 'engagement_acceptance_id'))->toBeFalse()
        ->and(Schema::hasColumn('statement_verifications', 'engagement_acceptance_id'))->toBeFalse()
        ->and(DB::table('audit_reports')->first())->toEqual($report)
        ->and(DB::table('statement_verifications')->first())->toEqual($before);
    expect(fn () => DB::transaction(fn (): int => DB::table('statement_verifications')->where('id', $verification->id)->delete()))
        ->toThrow(QueryException::class, 'Statement verifications are immutable');
})->with(['decrypt', 'json', 'hash', 'missing', 'unknown', 'receipt']);

it('allows rollback only before report or source history exists and retains the acceptance catalog', function (): void {
    $actor = AuditorFixture::make();
    AuditEngagementFixture::ready($actor['staff'], $actor['user']);
    $before = AuditEngagementAcceptance::query()->firstOrFail()->getRawOriginal();
    $migration = require database_path('migrations/2026_09_25_082804_enforce_audit_engagement_source_pins.php');
    $migration->down();
    expect(Schema::hasColumn('audit_reports', 'engagement_acceptance_id'))->toBeFalse();
    $migration->up();
    expect(AuditEngagementAcceptance::query()->firstOrFail()->getRawOriginal())->toBe($before);
    StatementVerification::factory()->create();
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing audit engagement source pins require a forward migration.')
        ->and(Schema::hasColumn('statement_verifications', 'engagement_acceptance_id'))->toBeTrue();
});
