<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditSigningKeyRevocation;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessCampaign;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($this->fixture);
    AuditSealingFixture::cosign($this->fixture);
    $this->store = app(BusinessCampaignStore::class);
});

it('records revision and reason refusals and never writes a release', function (int $revision, string $reason, string $code): void {
    $request = (string) Str::uuid();
    $result = $this->store->release($this->fixture['audit']['staff']->id, $this->fixture['application']->id, $revision, $reason, $request);
    expect($result['code'])->toBe($code)->and(BusinessApplicationRelease::query()->count())->toBe(0)
        ->and($this->store->findRelease($this->fixture['audit']['staff']->id, $request))->toBe($result);
})->with([[1, 'Reviewed.', 'VERSION_CONFLICT'], [0, '', 'VALIDATION_FAILED'], [0, "Hidden\u{2028}reason", 'VALIDATION_FAILED'], [0, str_repeat('x', 1001), 'VALIDATION_FAILED']]);

it('requires current dedicated staff authority even to replay an old release', function (): void {
    $staff = $this->fixture['audit']['staff'];
    $request = (string) Str::uuid();
    $this->store->release($staff->id, $this->fixture['application']->id, 0, 'Reviewed.', $request);
    app(ConfigureStaffAccess::class)->handle($staff->id, false, 'Withdraw staff authority.', (string) Str::uuid());
    expect(fn () => $this->store->release($staff->id, $this->fixture['application']->id, 0, 'Reviewed.', $request))->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED')
        ->and(fn () => $this->store->findRelease($staff->id, $request))->toThrow(IdentityViolation::class, 'STAFF_ACCESS_REQUIRED');
});

it('withdraws release readiness on signing-key revocation without deleting the acceptance', function (): void {
    AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $this->fixture['key']->id]);
    $result = $this->store->release($this->fixture['audit']['staff']->id, $this->fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    expect($result['code'])->toBe('REPORT_NOT_CURRENT')->and(BusinessApplicationRelease::query()->count())->toBe(0);
});

it('never releases an application twice or accepts changed input under an old request', function (): void {
    $staff = $this->fixture['audit']['staff']->id;
    $id = $this->fixture['application']->id;
    $request = (string) Str::uuid();
    $this->store->release($staff, $id, 0, 'Reviewed.', $request);
    expect($this->store->release($staff, $id, 1, 'Reviewed.', (string) Str::uuid())['code'])->toBe('APPLICATION_ALREADY_RELEASED')
        ->and(fn () => $this->store->release($staff, $id, 0, 'Changed.', $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('protects released evidence and publication from mutation deletion and occupied rollback', function (string $operation): void {
    $this->store->release($this->fixture['audit']['staff']->id, $this->fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    $this->store->publish($this->fixture['audit']['authority']['users'][0]->id, 1, $this->fixture['audit']['business'],
        $this->fixture['application']->id, $this->fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    expect(fn () => DB::transaction(function () use ($operation): void {
        match ($operation) {
            'release_update' => BusinessApplicationRelease::query()->sole()->forceFill(['sha256' => str_repeat('0', 64)])->save(),
            'release_delete' => BusinessApplicationRelease::query()->sole()->delete(),
            'campaign_update' => BusinessCampaign::query()->sole()->forceFill(['principal' => '3000000'])->save(),
            'campaign_delete' => BusinessCampaign::query()->sole()->delete(),
            'rollback' => (require database_path('migrations/2026_09_27_054238_create_business_application_releases_and_campaigns.php'))->down(),
            default => throw new LogicException('Unknown mutation fixture.'),
        };
    }))->toThrow(QueryException::class);
})->with(['release_update', 'release_delete', 'campaign_update', 'campaign_delete', 'rollback']);

it('uses a precise persisted publication second even when the request includes microseconds', function (): void {
    $this->travelTo(now()->setMicrosecond(999999));
    $this->store->release($this->fixture['audit']['staff']->id, $this->fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    $result = $this->store->publish($this->fixture['audit']['authority']['users'][0]->id, 1, $this->fixture['audit']['business'],
        $this->fixture['application']->id, $this->fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $page = $this->store->campaign($this->fixture['audit']['authority']['users'][0]->id, 1, $this->fixture['audit']['business'], $result['data']['campaign_id']);
    expect($page['progress']['clock']['starts_at'])->toBe($result['data']['receipt']['recorded_at']);
});
