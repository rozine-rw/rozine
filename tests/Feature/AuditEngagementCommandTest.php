<?php

declare(strict_types=1);

use App\Application\Auditor\GetAuditEngagementTerms;
use App\Application\Identity\ConfigureStaffAccess;
use App\Models\AuditEngagementRelease;
use App\Models\StaffAccount;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditorFixture;

beforeEach(function (): void {
    $this->termsFile = tempnam(sys_get_temp_dir(), 'rozine-terms-');
    $this->termsInput = ['status' => 'active', 'version' => 'approved-v1', 'documents' => AuditEngagementFixture::documents(),
        'synthetic' => false, 'approval_reference' => 'approved:retained-source', 'reason' => 'Record reviewed engagement terms.'];
    file_put_contents($this->termsFile, json_encode($this->termsInput, JSON_THROW_ON_ERROR));
});

afterEach(function (): void {
    unlink($this->termsFile);
});

it('publishes and withdraws non-synthetic terms outside isolated environments with explicit retry pins', function (string $environment): void {
    $actor = AuditorFixture::make();
    $this->app->instance('env', $environment);
    $parameters = ['actor' => $actor['staff']->id, 'file' => $this->termsFile, '--expected-revision' => '0', '--request-id' => (string) Str::uuid()];
    expect(Artisan::call('auditor:engagement-terms', $parameters))->toBe(0);
    $release = AuditEngagementRelease::query()->firstOrFail();
    $output = Artisan::output();
    expect($output)->toContain('AUDIT_ENGAGEMENT_TERMS_RECORDED', 'revision=1', 'sha256='.$release->sha256)
        ->not->toContain($this->termsInput['documents']['master_services']['body'], $this->termsInput['approval_reference'])
        ->and($release->synthetic)->toBeFalse()
        ->and($release->actor_user_id)->toBe($actor['staff']->id)
        ->and(app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1)['release']['id'])->toBe($release->id)
        ->and(Artisan::call('auditor:engagement-terms', $parameters))->toBe(0);
    $this->assertDatabaseCount('audit_engagement_releases', 1);
    file_put_contents($this->termsFile, json_encode([...$this->termsInput, 'status' => 'withdrawn', 'version' => null, 'documents' => []], JSON_THROW_ON_ERROR));
    expect(Artisan::call('auditor:engagement-terms', [...$parameters, '--expected-revision' => 1, '--request-id' => (string) Str::uuid()]))->toBe(0)
        ->and(app(GetAuditEngagementTerms::class)->handle($actor['user']->id, 1)['release'])->toBeNull();
    $this->assertDatabaseCount('audit_engagement_releases', 2);
})->with(['uat', 'production']);

it('preserves domain authority and isolation checks for console publication', function (string $case): void {
    $actor = AuditorFixture::make();
    $staff = $actor['staff'];
    if ($case === 'mfa') {
        $staff->forceFill(['two_factor_confirmed_at' => null])->save();
    } elseif ($case === 'revoked') {
        StaffAccount::query()->where('user_id', $staff->id)->update(['enabled' => false]);
    } elseif ($case === 'wrong_role') {
        app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Assign only analyst authority.', (string) Str::uuid(), ['analyst']);
    } elseif ($case === 'synthetic') {
        file_put_contents($this->termsFile, json_encode([...$this->termsInput, 'synthetic' => true], JSON_THROW_ON_ERROR));
        $this->app->instance('env', 'production');
    }
    expect(Artisan::call('auditor:engagement-terms', ['actor' => $case === 'auditor' ? $actor['user']->id : $staff->id,
        'file' => $this->termsFile, '--expected-revision' => 0, '--request-id' => (string) Str::uuid()]))->toBe(1);
    $this->assertDatabaseCount('audit_engagement_releases', 0);
})->with(['auditor', 'mfa', 'revoked', 'wrong_role', 'synthetic']);

it('returns failure for recorded stale or malformed releases and a reused UUID with changed content', function (): void {
    $actor = AuditorFixture::make();
    $parameters = ['actor' => $actor['staff']->id, 'file' => $this->termsFile, '--expected-revision' => 1, '--request-id' => (string) Str::uuid()];
    expect(Artisan::call('auditor:engagement-terms', $parameters))->toBe(1)
        ->and(Artisan::output())->toContain('VERSION_CONFLICT (revision: 0)');
    $parameters = [...$parameters, '--expected-revision' => 0, '--request-id' => (string) Str::uuid()];
    expect(Artisan::call('auditor:engagement-terms', $parameters))->toBe(0);
    file_put_contents($this->termsFile, json_encode([...$this->termsInput, 'reason' => 'A changed permitted body.'], JSON_THROW_ON_ERROR));
    expect(Artisan::call('auditor:engagement-terms', $parameters))->toBe(1)
        ->and(Artisan::output())->toContain('IDEMPOTENCY_CONFLICT');
    file_put_contents($this->termsFile, json_encode([...$this->termsInput, 'version' => 'approved-v2', 'documents' => []], JSON_THROW_ON_ERROR));
    expect(Artisan::call('auditor:engagement-terms', [...$parameters, '--expected-revision' => 1, '--request-id' => (string) Str::uuid()]))->toBe(1)
        ->and(Artisan::output())->toContain('AUDIT_ENGAGEMENT_INPUT_INVALID');
    $this->assertDatabaseCount('audit_engagement_releases', 1);
});

it('requires explicit valid actor revision UUID and JSON provenance fields', function (string $field, mixed $value): void {
    $parameters = ['actor' => 1, 'file' => $this->termsFile, '--expected-revision' => 0, '--request-id' => (string) Str::uuid()];
    if (str_starts_with($field, 'payload.')) {
        file_put_contents($this->termsFile, json_encode([...$this->termsInput, substr($field, 8) => $value], JSON_THROW_ON_ERROR));
    } else {
        $parameters[$field] = $value;
    }
    expect(Artisan::call('auditor:engagement-terms', $parameters))->toBe(1)
        ->and(Artisan::output())->toContain('AUDIT_ENGAGEMENT_INPUT_INVALID');
    $this->assertDatabaseCount('audit_engagement_releases', 0);
})->with([['actor', '1invalid'], ['--expected-revision', null], ['--expected-revision', -1], ['--request-id', null],
    ['--request-id', 'not-a-uuid'], ['payload.synthetic', 'false'], ['payload.status', 'invented'], ['payload.approval_reference', ''], ['payload.unexpected', 'not accepted']]);

it('refuses missing non-file oversized malformed or non-object release inputs', function (string $case): void {
    $path = $this->termsFile;
    if ($case === 'missing') {
        $path .= '-missing';
    } elseif ($case === 'directory') {
        $path = sys_get_temp_dir();
    } else {
        file_put_contents($path, match ($case) {
            'large' => str_repeat(' ', 5242881),
            'malformed' => '{not-json',
            default => '"not an object"',
        });
    }
    expect(Artisan::call('auditor:engagement-terms', ['actor' => 1, 'file' => $path, '--expected-revision' => 0, '--request-id' => (string) Str::uuid()]))->toBe(1)
        ->and(Artisan::output())->toContain($case === 'scalar' ? 'AUDIT_ENGAGEMENT_INPUT_INVALID' : 'AUDIT_ENGAGEMENT_FILE_INVALID');
    $this->assertDatabaseCount('audit_engagement_releases', 0);
})->with(['missing', 'directory', 'large', 'malformed', 'scalar']);

it('refuses a release file that disappears before it can be read', function (): void {
    $this->app->instance(Filesystem::class, new class extends Filesystem
    {
        public function get(mixed $path, mixed $lock = false): never
        {
            throw new FileNotFoundException;
        }
    });
    expect(Artisan::call('auditor:engagement-terms', ['actor' => 1, 'file' => $this->termsFile, '--expected-revision' => 0, '--request-id' => (string) Str::uuid()]))->toBe(1)
        ->and(Artisan::output())->toContain('AUDIT_ENGAGEMENT_FILE_INVALID');
    $this->assertDatabaseCount('audit_engagement_releases', 0);
});
