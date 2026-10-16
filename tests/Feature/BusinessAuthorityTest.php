<?php

declare(strict_types=1);

use App\Application\Business\ConfigureBusinessAuthority;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Identity\AuthorizeEntityRole;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\WithVerifiedParties;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessAuthorityFixture;

it('records a sole trader or actual company mandate without imposing two directors', function (string $kind, int $count): void {
    $fixture = BusinessAuthorityFixture::make($kind, $count);
    $request = (string) Str::uuid();
    $result = BusinessAuthorityFixture::configure($fixture, requestId: $request);
    $business = BusinessProfile::query()->firstOrFail();
    expect($result['code'])->toBe('BUSINESS_AUTHORITY_RECORDED')->and($business->entity_party_id)->toBe($fixture['entity'])
        ->and($business->revision)->toBe(1)->and($business->mandate_version)->toBe(1)
        ->and(BusinessMandate::query()->firstOrFail()->terms['required_signatories'])->toHaveCount($count);
    $fixture['terms']['people'] = array_reverse($fixture['terms']['people']);
    $fixture['terms']['required_signatories'] = array_reverse($fixture['terms']['required_signatories']);
    expect(BusinessAuthorityFixture::configure($fixture, requestId: $request))->toBe($result);
    foreach ($fixture['users'] as $user) {
        expect(app(WithBusinessAuthority::class)->handle($user->id, 1, $business->id, 'application.sign', 1,
            fn (array $business, array $identity): string => $identity['party']['id'] ?? ''))->toBe($user->party_id);
    }
    $this->assertDatabaseCount('business_profiles', 1);
    $this->assertDatabaseCount('business_mandates', 1);
})->with(['sole trader' => ['person', 1], 'one company signatory' => ['organization', 1], 'two company signatories' => ['organization', 2]]);

it('binds company authority to the verified registry entity and every declared person', function (): void {
    $fixture = BusinessAuthorityFixture::make('organization', 2);
    $fixture['profile']['company_code'] = 'OTHER-COMPANY';
    expect(fn () => BusinessAuthorityFixture::configure($fixture))->toThrow(IdentityViolation::class, 'ORGANIZATION_REFERENCE_MISMATCH');
    $fixture['profile']['company_code'] = 'COMPANY-001';
    $fixture['people'][1]->forceFill(['verified_at' => null])->save();
    expect(fn () => BusinessAuthorityFixture::configure($fixture))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    $this->assertDatabaseCount('business_profiles', 0);
});

it('requires current staff authority including on a replay', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    $key = (string) Str::uuid();
    BusinessAuthorityFixture::configure($fixture, requestId: $key);
    app(ConfigureStaffAccess::class)->handle($fixture['staff']->id, true, 'Read only now.', (string) Str::uuid(), ['analyst']);
    expect(fn () => BusinessAuthorityFixture::configure($fixture, requestId: $key))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
});

it('appends authority changes and rejects stale revisions without overwriting history', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->firstOrFail();
    $fixture['profile']['name'] = 'Updated verified name';
    expect(BusinessAuthorityFixture::configure($fixture)['code'])->toBe('VERSION_CONFLICT');
    expect(BusinessAuthorityFixture::configure($fixture, 1)['code'])->toBe('BUSINESS_AUTHORITY_RECORDED');
    expect($business->refresh()->revision)->toBe(2)->and($business->mandate_version)->toBe(2)
        ->and(BusinessMandate::query()->where('version', 1)->firstOrFail()->profile['name'])->toBe('Synthetic business')
        ->and(BusinessMandate::query()->where('version', 2)->exists())->toBeTrue();
    expect(fn () => app(WithBusinessAuthority::class)->handle($fixture['users'][0]->id, 1, $business->id, 'application.sign', 1, fn (): bool => true))
        ->toThrow(CommandRejection::class, 'MANDATE_STALE');
});

it('revokes authority even if a previously required person has lost verification', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->firstOrFail();
    $fixture['people'][0]->forceFill(['verified_at' => null])->save();
    $fixture['terms']['status'] = 'revoked';
    expect(BusinessAuthorityFixture::configure($fixture, 1)['code'])->toBe('BUSINESS_AUTHORITY_RECORDED');
    expect(fn () => app(WithBusinessAuthority::class)->handle($fixture['users'][0]->id, 1, $business->id, 'application.sign', 2, fn (): bool => true))
        ->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
    $this->assertDatabaseCount('business_mandates', 2);
});

it('denies incomplete entity authority, expired mandates, stale context and invisible businesses', function (): void {
    $fixture = BusinessAuthorityFixture::make('organization', 2);
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->firstOrFail();
    $action = app(WithBusinessAuthority::class);
    expect(fn () => $action->handle($fixture['users'][0]->id, 0, $business->id, 'application.sign', 1, fn (): bool => true))
        ->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT');
    $outsider = User::factory()->for(Party::factory()->verified())->create();
    expect(fn () => $action->handle($outsider->id, 0, $business->id, 'business.view', null, fn (): bool => true))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND')
        ->and(fn () => $action->handle($fixture['users'][0]->id, 1, (string) Str::ulid(), 'business.view', null, fn (): bool => true))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND');
    $fixture['people'][1]->forceFill(['verified_at' => null])->save();
    expect(fn () => $action->handle($fixture['users'][0]->id, 1, $business->id, 'application.sign', 1, fn (): bool => true))->toThrow(IdentityViolation::class, 'PARTY_AUTHORITY_REQUIRED');
    $fixture['people'][1]->forceFill(['verified_at' => now()])->save();
    $fixture['terms']['expires_at'] = now('UTC')->addSecond()->format('Y-m-d\TH:i:s\Z');
    BusinessAuthorityFixture::configure($fixture, 1);
    $this->travel(2)->seconds();
    expect(fn () => $action->handle($fixture['users'][0]->id, 1, $business->id, 'application.sign', 2, fn (): bool => true))->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
});

it('does not infer draft authority from ownership or a named officer role', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'permissions' => ['business.view', 'application.sign', 'report.cosign']];
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->firstOrFail();
    expect(fn () => app(WithBusinessAuthority::class)->handle($fixture['users'][0]->id, 1, $business->id, 'application.create', 1, fn (): bool => true))
        ->toThrow(CommandRejection::class, 'ACTION_FORBIDDEN');
});

it('keeps verified identity and effective authority checks inside the same transaction', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->firstOrFail();
    expect(fn () => app(WithBusinessAuthority::class)->handle($fixture['users'][0]->id, 1, $business->id, 'application.create', 1, function () use ($business): never {
        $business->forceFill(['revision' => 99])->save();
        throw new RuntimeException('Roll back protected work.');
    }))->toThrow(RuntimeException::class);
    expect($business->refresh()->revision)->toBe(1);
    expect(fn () => app(AuthorizeEntityRole::class)->handle($fixture['staff']->id, 'business', 1, 'person', $fixture['entity'], [$fixture['entity']], fn (): bool => true))
        ->toThrow(IdentityViolation::class, 'MANDATE_REQUIRED');
    expect(fn () => app(WithVerifiedParties::class)->handle('person', $fixture['entity'], [$fixture['entity']], fn (): bool => true, 'RDB:COMPANY-001'))
        ->toThrow(IdentityViolation::class, 'ORGANIZATION_REFERENCE_MISMATCH');
});

it('rejects changes and deletion of mandate history at the database boundary', function (bool $deletion): void {
    $mandate = BusinessMandate::factory()->create();
    expect(fn () => DB::transaction(function () use ($mandate, $deletion): void {
        $query = DB::table('business_mandates')->where('id', $mandate->id);
        if ($deletion) {
            $query->delete();
        } else {
            $query->update(['reason' => 'Rewrite the history.']);
        }
    }))->toThrow(QueryException::class);
})->with([false, true]);

it('refuses an incomplete, contradictory or malformed mandate', function (string $case): void {
    $fixture = BusinessAuthorityFixture::make();
    switch ($case) {
        case 'empty people': $fixture['terms']['people'] = [];
            break;
        case 'empty signers': $fixture['terms']['required_signatories'] = [];
            break;
        case 'incomplete': $fixture['terms']['attested_complete'] = false;
            break;
        case 'bad status': $fixture['terms']['status'] = 'approved';
            break;
        case 'bad date': $fixture['terms']['effective_at'] = '2026-02-30T00:00:00Z';
            break;
        case 'relative date': $fixture['terms']['effective_at'] = 'yesterday';
            break;
        case 'bad expiry': $fixture['terms']['expires_at'] = 'invalid';
            break;
        case 'backwards expiry': $fixture['terms']['expires_at'] = '2020-01-01T00:00:00Z';
            break;
        case 'duplicate person': $fixture['terms']['people'][] = $fixture['terms']['people'][0];
            break;
        case 'invalid party': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'party_id' => 'not-a-party'];
            break;
        case 'blank name': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'name' => ''];
            break;
        case 'long name': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'name' => str_repeat('x', 181)];
            break;
        case 'empty roles': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'roles' => []];
            break;
        case 'unknown role': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'roles' => ['ceo']];
            break;
        case 'unknown permission': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'permissions' => ['*']];
            break;
        case 'no view': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'permissions' => ['application.sign', 'report.cosign']];
            break;
        case 'missing signatory': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'roles' => ['owner']];
            break;
        case 'no signature permission': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'permissions' => ['business.view', 'report.cosign']];
            break;
        case 'no cosign permission': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'permissions' => ['business.view', 'application.sign']];
            break;
        case 'unknown signer': $fixture['terms']['required_signatories'] = [strtolower((string) Str::ulid())];
            break;
        case 'not owner': $fixture['terms']['people'][0] = [...$fixture['terms']['people'][0], 'roles' => ['signatory']];
            break;
        case 'other owner': $fixture['entity'] = strtolower((string) Str::ulid());
            break;
    }
    expect(fn () => BusinessAuthorityFixture::configure($fixture))->toThrow(CommandRejection::class);
    $this->assertDatabaseCount('business_profiles', 0);
})->with(['empty people', 'empty signers', 'incomplete', 'bad status', 'bad date', 'relative date', 'bad expiry', 'backwards expiry', 'duplicate person', 'invalid party', 'blank name', 'long name', 'empty roles', 'unknown role', 'unknown permission', 'no view', 'missing signatory', 'no signature permission', 'no cosign permission', 'unknown signer', 'not owner', 'other owner']);

it('refuses unapproved company fields and tax-identifier-shaped inputs', function (string $case): void {
    $fixture = BusinessAuthorityFixture::make();
    switch ($case) {
        case 'unknown kind': $fixture['kind'] = 'cooperative';
            break;
        case 'tax field': $fixture['profile']['tin'] = 'must never be stored';
            break;
        case 'blank name': $fixture['profile']['name'] = ' ';
            break;
        case 'long district': $fixture['profile']['district'] = str_repeat('x', 181);
            break;
        case 'sole trader code': $fixture['profile']['company_code'] = 'CODE';
            break;
        case 'old year': $fixture['profile']['established_year'] = 1000;
            break;
        case 'future year': $fixture['profile']['established_year'] = now()->year + 1;
            break;
        case 'missing company code': $fixture['kind'] = 'organization';
            break;
        case 'tax namespace': $fixture['kind'] = 'organization';
            $fixture['profile']['company_code'] = 'TIN:12345';
            break;
    }
    expect(fn () => BusinessAuthorityFixture::configure($fixture))->toThrow(CommandRejection::class, 'BUSINESS_PROFILE_INVALID');
    $this->assertDatabaseCount('business_profiles', 0);
})->with(['unknown kind', 'tax field', 'blank name', 'long district', 'sole trader code', 'old year', 'future year', 'missing company code', 'tax namespace']);

it('requires a reason and reviewed evidence for all mandate commands', function (int $revision, string $evidence, string $reason): void {
    $fixture = BusinessAuthorityFixture::make();
    expect(fn () => app(ConfigureBusinessAuthority::class)->handle($fixture['staff']->id, 'person', $fixture['entity'], $fixture['profile'], $fixture['terms'], $revision,
        $evidence, $reason, (string) Str::uuid()))->toThrow(CommandRejection::class, 'MANDATE_EVIDENCE_REQUIRED');
})->with([[-1, 'fixture:x', 'Reviewed.'], [0, '', 'Reviewed.'], [0, str_repeat('x', 256), 'Reviewed.'], [0, 'fixture:x', ''], [0, 'fixture:x', str_repeat('x', 2001)]]);

it('cannot create an initially revoked business and never activates a future mandate early', function (): void {
    $fixture = BusinessAuthorityFixture::make();
    $fixture['terms']['status'] = 'revoked';
    expect(BusinessAuthorityFixture::configure($fixture)['code'])->toBe('MANDATE_REQUIRED');
    $fixture['terms']['status'] = 'active';
    $fixture['terms']['effective_at'] = now('UTC')->addDay()->format('Y-m-d\TH:i:s\Z');
    BusinessAuthorityFixture::configure($fixture);
    $business = BusinessProfile::query()->firstOrFail();
    expect(fn () => app(WithBusinessAuthority::class)->handle($fixture['users'][0]->id, 1, $business->id, 'application.sign', 1, fn (): bool => true))
        ->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
});
