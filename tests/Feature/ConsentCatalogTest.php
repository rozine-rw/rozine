<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\RecordConsentRelease;
use App\Application\Identity\WithCurrentConsent;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\ConsentRelease;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\ConsentFixture;

it('has no implied approved terms before a consent release is recorded', function (): void {
    expect(app(WithCurrentConsent::class)->handle(fn (?array $release): ?array => $release))->toBeNull();
});

it('records exact reviewed documents once and returns the immutable current release', function (): void {
    $staff = ConsentFixture::staff();
    $request = (string) Str::uuid();
    $result = ConsentFixture::record($staff, 0, $request);
    expect($result['code'])->toBe('CONSENT_RELEASE_RECORDED')
        ->and($result['revision'])->toBe(1)
        ->and($result['data']['release']['synthetic'])->toBeTrue()
        ->and(ConsentFixture::record($staff, 0, $request))->toBe($result);
    $release = app(WithCurrentConsent::class)->handle(fn (?array $release): ?array => $release);
    expect($release['id'])->toBe($result['data']['release']['id'])
        ->and($release['documents'][1]['body'])->toBe('Synthetic terms. Test use only.')
        ->and($release['documents'][1]['sha256'])->toBe(hash('sha256', 'Synthetic terms. Test use only.'));
    $this->assertDatabaseCount('consent_releases', 1);

    expect(fn () => ConsentFixture::record($staff, 1, $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    app(ConfigureStaffAccess::class)->handle($staff->id, false, 'Staff removed.', (string) Str::uuid());
    expect(fn () => ConsentFixture::record($staff, 0, $request))->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED');
});

it('restricts document recording to explicitly authorized compliance staff', function (string $role, bool $allowed): void {
    $staff = ConsentFixture::staff($role);
    if ($allowed) {
        expect(ConsentFixture::record($staff)['code'])->toBe('CONSENT_RELEASE_RECORDED');
    } else {
        expect(fn () => ConsentFixture::record($staff))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
        $this->assertDatabaseCount('consent_releases', 0);
    }
})->with([['compliance', true], ['superadmin', true], ['analyst', false], ['approver', false], ['treasury', false]]);

it('records stale revision denials and never silently replaces a current release', function (): void {
    $staff = ConsentFixture::staff();
    ConsentFixture::record($staff);
    $request = (string) Str::uuid();
    $denial = ConsentFixture::record($staff, 0, $request);
    expect($denial['status'])->toBe('rejected')->and($denial['code'])->toBe('VERSION_CONFLICT')->and($denial['revision'])->toBe(1)
        ->and(ConsentFixture::record($staff, 0, $request))->toBe($denial);
    $this->assertDatabaseCount('consent_releases', 1);
});

it('retains history and does not fall back to old documents after withdrawal', function (): void {
    $staff = ConsentFixture::staff();
    ConsentFixture::record($staff);
    $action = app(RecordConsentRelease::class);
    $result = $action->handle($staff->id, 1, 'withdrawn', [], [], true, 'fixture:withdrawal', 'Withdraw inaccurate fixture.', (string) Str::uuid());
    expect($result['code'])->toBe('CONSENT_RELEASE_RECORDED')
        ->and(app(WithCurrentConsent::class)->handle(fn (?array $release): ?array => $release))->toBeNull();
    expect(ConsentRelease::query()->orderBy('revision')->firstOrFail()->documents)->not->toBeEmpty();
    expect(ConsentFixture::record($staff, 2)['revision'])->toBe(3);
    $this->assertDatabaseCount('consent_releases', 3);
});

it('requires a new version when legal text summaries disclosure text or fixture provenance changes', function (string $case): void {
    $staff = ConsentFixture::staff();
    ConsentFixture::record($staff);
    $documents = ConsentFixture::documents();
    $disclosures = ConsentFixture::disclosures();
    $synthetic = true;
    switch ($case) {
        case 'body': $documents[0]['body'] .= ' Changed.';
            break;
        case 'summary': $documents[0]['summary'] = [['heading' => 'Changed summary', 'body' => 'New clause.']];
            break;
        case 'disclosure': $disclosures[0]['text'] .= ' Changed.';
            break;
        case 'provenance': $synthetic = false;
            break;
    }
    $result = app(RecordConsentRelease::class)->handle($staff->id, 1, 'active', $documents, $disclosures, $synthetic,
        'fixture:updated', 'Updated test documents.', (string) Str::uuid());
    expect($result['code'])->toBe('DOCUMENT_VERSION_CONFLICT')->and($result['status'])->toBe('rejected');
    $this->assertDatabaseCount('consent_releases', 1);
})->with(['body', 'summary', 'disclosure', 'provenance']);

it('publishes new versions without changing the previous text or hashes', function (): void {
    $staff = ConsentFixture::staff();
    ConsentFixture::record($staff);
    $original = ConsentRelease::query()->firstOrFail()->toArray();
    $documents = ConsentFixture::documents('synthetic-2');
    $documents[0]['body'] .= ' Revised.';
    $result = app(RecordConsentRelease::class)->handle($staff->id, 1, 'active', $documents, ConsentFixture::disclosures('synthetic-2'), true,
        'fixture:updated', 'Updated test documents.', (string) Str::uuid());
    expect($result['revision'])->toBe(2)
        ->and(ConsentRelease::query()->orderBy('revision')->firstOrFail()->toArray())->toBe($original)
        ->and(app(WithCurrentConsent::class)->handle(fn (?array $release): ?array => $release)['documents'][1]['version'])->toBe('synthetic-2');
});

it('blocks synthetic documents outside permitted fixture environments on read and write', function (): void {
    $staff = ConsentFixture::staff();
    ConsentFixture::record($staff);
    $this->app->instance('env', 'production');
    expect(app(WithCurrentConsent::class)->handle(fn (?array $release): ?array => $release))->toBeNull()
        ->and(fn () => ConsentFixture::record($staff, 1))->toThrow(CommandRejection::class, 'SYNTHETIC_CONSENT_DENIED');
    $this->assertDatabaseCount('consent_releases', 1);
});

it('can read an actual externally approved release without enabling fixture access', function (): void {
    ConsentRelease::factory()->create(['synthetic' => false]);
    $this->app->instance('env', 'production');
    expect(app(WithCurrentConsent::class)->handle(fn (?array $release): ?array => $release)['synthetic'])->toBeFalse();
});

it('rejects malformed release metadata before recording any outcome', function (string $case): void {
    $staff = ConsentFixture::staff();
    $revision = 0;
    $status = 'active';
    $reference = 'fixture:approval';
    $reason = 'Test release.';
    $documents = ConsentFixture::documents();
    switch ($case) {
        case 'negative revision': $revision = -1;
            break;
        case 'bad status': $status = 'approved';
            break;
        case 'empty reference': $reference = '';
            break;
        case 'long reference': $reference = str_repeat('x', 256);
            break;
        case 'empty reason': $reason = ' ';
            break;
        case 'long reason': $reason = str_repeat('x', 2001);
            break;
        case 'withdrawal documents': $status = 'withdrawn';
            break;
    }
    expect(fn () => app(RecordConsentRelease::class)->handle($staff->id, $revision, $status, $documents, ConsentFixture::disclosures(), true,
        $reference, $reason, (string) Str::uuid()))->toThrow(CommandRejection::class, 'CONSENT_RELEASE_INVALID');
    $this->assertDatabaseCount('consent_releases', 0);
})->with(['negative revision', 'bad status', 'empty reference', 'long reference', 'empty reason', 'long reason', 'withdrawal documents']);

it('protects consent history from database updates and deletes', function (string $operation): void {
    $release = ConsentRelease::factory()->create();
    expect(fn () => DB::transaction(fn (): mixed => $operation === 'update'
        ? $release->forceFill(['approval_reference' => 'changed'])->save()
        : $release->delete()))->toThrow(QueryException::class, 'Consent releases are immutable');
})->with(['update', 'delete']);

it('rolls back a protected consent operation on unexpected failure', function (): void {
    $staff = ConsentFixture::staff();
    ConsentFixture::record($staff);
    expect(fn () => app(WithCurrentConsent::class)->handle(function () use ($staff): never {
        $staff->forceFill(['name' => 'Must roll back'])->save();
        throw new RuntimeException('Fail protected operation.');
    }))->toThrow(RuntimeException::class, 'Fail protected operation.');
    expect($staff->refresh()->name)->not->toBe('Must roll back');
});
