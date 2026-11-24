<?php

declare(strict_types=1);

use App\Models\AuditorProfile;
use App\Models\AuditorProfileVersion;
use App\Models\Party;
use App\Models\RoleApplication;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>  $state
 * @param  array<string, mixed>  $overrides
 */
function backfillProfile(array $state, array $overrides = []): AuditorProfile
{
    $profile = AuditorProfile::factory()->create();
    $profile->forceFill(['state' => [...Arr::except($profile->state, ['districts', 'max_jobs_per_week', 'address']), ...$state, ...$overrides]])->save();

    return $profile;
}

/** @param array<string, mixed> $answers */
function backfillApplication(string $partyId, string $role, string $status, array $answers): RoleApplication
{
    $application = new RoleApplication;
    $application->forceFill(['party_id' => $partyId, 'role' => $role, 'revision' => 1, 'status' => $status,
        'submitted_at' => now(), 'state' => ['role' => $role, 'step' => 'review', 'answers' => $answers, 'documents' => [], 'note' => null]])->save();

    return $application;
}

it('gives partners already on file districts, a weekly limit and an address, and switches a verified partner who never paused on', function (): void {
    $verified = ['certificate_id' => strtolower((string) Str::ulid()), 'standing' => ['status' => 'active', 'licence' => 'SYNTHETIC-CPA',
        'expires_on' => '2027-12-31', 'checked_at' => '2026-11-01T08:00:00Z', 'check_reference' => 'synthetic']];
    $address = ['province' => 'Kigali City', 'district' => 'Gasabo', 'sector' => 'Kacyiru', 'cell' => 'Kamatamu', 'street' => 'KG 7 Ave'];
    $applied = backfillProfile($verified);
    backfillApplication($applied->party_id, 'auditor', 'approved', ['address' => $address, 'coverage' => ['districts' => ['Rubavu', 'Gasabo']]]);
    $early = backfillProfile($verified);
    $paused = backfillProfile($verified);
    (new AuditorProfileVersion)->forceFill(['auditor_profile_id' => $paused->id, 'revision' => 1, 'snapshot' => [], 'actor_user_id' => 1,
        'command' => 'availability.update', 'policy_version' => 'synthetic'])->save();
    $pending = backfillProfile([]);
    $current = backfillProfile($verified, ['districts' => ['Huye'], 'max_jobs_per_week' => 2, 'address' => null]);
    $business = backfillApplication(Party::factory()->verified()->create()->id, 'business', 'submitted', ['company' => ['legal_name' => 'Kivu Harvest Ltd',
        'district' => 'Rubavu', 'registered_address' => 'Plot 12, Gisenyi']]);
    $decided = backfillApplication(Party::factory()->verified()->create()->id, 'business', 'approved', ['company' => ['district' => 'Rubavu', 'registered_address' => 'Old']]);

    (require database_path('migrations/2026_11_24_120352_backfill_auditor_districts_and_registered_addresses.php'))->up();

    $all = array_merge(...array_values((array) config('rwanda.districts')));
    expect($applied->refresh()->state)->toMatchArray(['districts' => ['Gasabo', 'Rubavu'], 'max_jobs_per_week' => 4, 'address' => $address, 'accepting' => true])
        ->and($early->refresh()->state)->toMatchArray(['districts' => $all, 'max_jobs_per_week' => 4, 'address' => null, 'accepting' => true])
        ->and($paused->refresh()->state)->toMatchArray(['districts' => $all, 'accepting' => false])
        ->and($pending->refresh()->state)->toMatchArray(['districts' => $all, 'accepting' => false])
        ->and($current->refresh()->state)->toMatchArray(['districts' => ['Huye'], 'max_jobs_per_week' => 2])
        ->and($business->refresh()->state['answers'])->toBe(['company' => ['legal_name' => 'Kivu Harvest Ltd', 'district' => 'Rubavu',
            'province' => 'Western', 'sector' => '', 'cell' => '', 'street' => 'Plot 12, Gisenyi']])
        ->and($decided->refresh()->state['answers'])->toBe(['company' => ['district' => 'Rubavu', 'registered_address' => 'Old']]);
    expect(count($all))->toBe(30);
});

it('reshapes the address kept when a Business application was sent back, so an untouched address reads unchanged (QA #771)', function (): void {
    $old = ['legal_name' => 'QA Walk Five Foods Ltd', 'district' => 'Kicukiro', 'registered_address' => 'KK 15 Rd, Kicukiro'];
    $sentBack = backfillApplication(Party::factory()->verified()->create()->id, 'business', 'submitted', ['company' => $old]);
    $sentBack->forceFill(['state' => [...$sentBack->state, 'revision' => ['note' => 'Fix the TIN.', 'items' => [], 'requested_at' => '2026-11-24T09:00:00Z',
        'before' => ['answers' => ['company' => $old], 'documents' => []]]]])->save();
    $plain = backfillApplication(Party::factory()->verified()->create()->id, 'business', 'submitted', ['company' => $old]);

    (require database_path('migrations/2026_11_24_120352_backfill_auditor_districts_and_registered_addresses.php'))->up();
    (require database_path('migrations/2026_11_24_214317_reshape_sent_back_business_address_snapshots.php'))->up();

    $reshaped = ['legal_name' => 'QA Walk Five Foods Ltd', 'district' => 'Kicukiro', 'province' => 'Kigali City', 'sector' => '', 'cell' => '',
        'street' => 'KK 15 Rd, Kicukiro'];
    expect($sentBack->refresh()->state)->toMatchArray(['answers' => ['company' => $reshaped],
        'revision' => ['note' => 'Fix the TIN.', 'items' => [], 'requested_at' => '2026-11-24T09:00:00Z', 'before' => ['answers' => ['company' => $reshaped], 'documents' => []]]])
        ->and($plain->refresh()->state)->not->toHaveKey('revision');
});
