<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\PolicySettings;
use App\Application\Primary\GetInvestorCheckout;
use App\Domain\Operations\PolicyChange;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\StaffFixture;

beforeEach(function (): void {
    $this->withoutVite();
    twoInvestorRaiseCap(10);
});

/** @param  list<string>  $roles */
function policyStaff(array $roles, string $name = 'Staff Member'): User
{
    $user = User::factory()->withTwoFactor()->create(['name' => $name]);
    StaffFixture::provision($user, $roles, 'Policy change assignment.');

    return $user;
}

/** @return array<string, string> */
function proposal(string $key, string $value, string $reason = 'Robert and Erastus agreed this on 26 Nov.'): array
{
    return ['request_id' => (string) Str::uuid(), 'key' => $key, 'value' => $value, 'reason' => $reason];
}

/** @return array<string, string> */
function decision(string $reason = 'Agreed.'): array
{
    return ['request_id' => (string) Str::uuid(), 'reason' => $reason];
}

test('the Policies page shows the changeable settings in force, and only founders and super-admins may propose', function (): void {
    $this->actingAs(policyStaff(['analyst']))->get('/admin/policies')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/section')
            ->where('policy_changes.settings.0', fn (Collection $setting): bool => $setting->except('updated_at')->all() === ['key' => 'investor_cap_percent',
                'unit' => 'percent', 'value' => 10, 'min' => 1, 'max' => 100, 'revision' => 1, 'pending' => null])
            ->where('policy_changes.settings.1.value', 25)->where('policy_changes.settings.2.value', 30000000)->where('policy_changes.settings.3.value', 25)
            ->where('policy_changes.propose', null)->where('policy_changes.can_approve', false)->where('policy_changes.approvals_needed', 2)
            ->where('policy_changes.history', []));
    $this->actingAs(policyStaff(['founder']))->get('/admin/policies')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('policy_changes.propose', ['url' => '/admin/policies/changes', 'method' => 'post'])
            ->where('policy_changes.can_approve', true));
    $this->actingAs(policyStaff(['superadmin']))->get('/admin/policies')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('policy_changes.propose', ['url' => '/admin/policies/changes', 'method' => 'post'])
            ->where('policy_changes.can_approve', false));

    $this->actingAs(policyStaff(['compliance']))->post('/admin/policies/changes', proposal('investor_cap_percent', '12'))->assertSessionHasErrors('form');
    expect(DB::table('policy_changes')->count())->toBe(0);
});

test('a change takes effect only once two different founders approve it, each separately', function (): void {
    $erastus = policyStaff(['superadmin', 'founder'], 'Erastus Kirui');
    $robert = policyStaff(['founder'], 'Robert Mugisha');
    $settings = app(PolicySettings::class);

    $this->actingAs($erastus)->post('/admin/policies/changes', proposal('investor_cap_percent', '12'))
        ->assertRedirect('/admin/policies')->assertSessionHasNoErrors();
    $change = DB::table('policy_changes')->sole();
    expect($change->status)->toBe('pending')->and((int) $change->from_value)->toBe(10)->and((int) $change->to_value)->toBe(12)
        ->and($settings->value('investor_cap_percent'))->toBe(10);

    $this->actingAs($erastus)->post("/admin/policies/changes/{$change->id}/approve", decision())->assertSessionHasNoErrors();
    expect($settings->value('investor_cap_percent'))->toBe(10)
        ->and(DB::table('policy_changes')->value('status'))->toBe('pending');
    $this->actingAs($erastus)->post("/admin/policies/changes/{$change->id}/approve", decision())->assertSessionHasErrors('form');
    $this->actingAs($erastus)->get('/admin/policies')->assertInertia(fn (Assert $page) => $page
        ->where('policy_changes.settings.0.pending.viewer_decided', true)->where('policy_changes.settings.0.pending.approve', null)
        ->where('policy_changes.settings.0.pending.decisions.0.by', 'Erastus Kirui'));
    $this->actingAs($robert)->get('/admin/policies')->assertInertia(fn (Assert $page) => $page
        ->where('policy_changes.settings.0.pending.approve', ['url' => "/admin/policies/changes/{$change->id}/approve", 'method' => 'post']));

    $this->actingAs($robert)->post("/admin/policies/changes/{$change->id}/approve", decision())->assertSessionHasNoErrors();
    expect($settings->value('investor_cap_percent'))->toBe(12)
        ->and(DB::table('policy_changes')->value('status'))->toBe('applied')
        ->and(DB::table('policy_settings')->where('key', 'investor_cap_percent')->value('revision'))->toBe(2);
    $this->actingAs($robert)->get('/admin/policies')->assertInertia(fn (Assert $page) => $page
        ->where('policy_changes.settings.0.value', 12)->where('policy_changes.settings.0.pending', null)
        ->where('policy_changes.history.0.status', 'applied')->has('policy_changes.history.0.decisions', 2)
        ->where('rules', fn (Collection $rules): bool => $rules->firstWhere('key', 'investor_cap_percent')['value'] === '12%'));

    expect(fn () => DB::table('policy_change_decisions')->delete())->toThrow(Exception::class, 'POLICY_DECISION_APPEND_ONLY');
});

test('one founder refusing closes the change, and the value in force stays', function (): void {
    $founder = policyStaff(['founder']);
    $this->actingAs($founder)->post('/admin/policies/changes', proposal('institution_cap_percent', '30'))->assertSessionHasNoErrors();
    $change = DB::table('policy_changes')->sole();

    $this->actingAs(policyStaff(['superadmin']))->post("/admin/policies/changes/{$change->id}/reject", decision('No.'))->assertSessionHasErrors('form');
    $this->actingAs(policyStaff(['founder']))->post("/admin/policies/changes/{$change->id}/reject", decision('Leave room for retail.'))->assertSessionHasNoErrors();

    expect(DB::table('policy_changes')->value('status'))->toBe('rejected')
        ->and(app(PolicySettings::class)->value('institution_cap_percent'))->toBe(25);
    $this->actingAs($founder)->post("/admin/policies/changes/{$change->id}/approve", decision())->assertSessionHasErrors('form');
});

test('a proposal is refused outside its range, at the value in force, without a reason, or while another waits', function (): void {
    $founder = policyStaff(['founder']);

    $this->actingAs($founder)->post('/admin/policies/changes', proposal('institution_reminder_day', '31'))->assertSessionHasErrors('value');
    $this->actingAs($founder)->post('/admin/policies/changes', proposal('investor_cap_percent', '10'))->assertSessionHasErrors('value');
    $this->actingAs($founder)->post('/admin/policies/changes', proposal('unknown_rule', '10'))->assertSessionHasErrors('key');
    $this->actingAs($founder)->post('/admin/policies/changes', proposal('investor_cap_percent', '15', ' '))->assertSessionHasErrors('reason');
    $this->actingAs($founder)->post('/admin/policies/changes', proposal('institution_min_commitment', '25,000,000'))->assertSessionHasNoErrors();
    $this->actingAs($founder)->post('/admin/policies/changes', proposal('institution_min_commitment', '40000000'))->assertSessionHasErrors('form');

    expect(DB::table('policy_changes')->sole()->to_value)->toBe(25000000);
});

test('an institution is held to the institution cap and an individual to the individual cap', function (): void {
    $settings = app(PolicySettings::class);
    $person = User::factory()->create(['party_id' => Party::factory()]);

    expect($settings->holdingCapPercent((string) $person->party_id))->toBe(10);
    DB::table('policy_settings')->where('key', 'investor_cap_percent')->update(['value' => 15]);
    expect($settings->holdingCapPercent((string) $person->party_id))->toBe(15)
        ->and(PolicyChange::capUnits('2160', 10))->toBe('216')
        ->and(PolicyChange::capUnits('2160', 25))->toBe('540');
});

test('checkout offers an individual 10% and an institution 25% of a raise, less what each already holds', function (): void {
    $deal = ['lifecycle' => 'live', 'units' => ['available' => '2000', 'total' => '2160']];

    expect(GetInvestorCheckout::capacity($deal, '0', true, 10))->toMatchArray(['max_units' => '216', 'binding' => 'raise_cap'])
        ->and(GetInvestorCheckout::capacity($deal, '16', true, 10)['max_units'])->toBe('200')
        ->and(GetInvestorCheckout::capacity($deal, '0', true, 25)['max_units'])->toBe('540')
        ->and(GetInvestorCheckout::capacity($deal, '600', true, 25)['max_units'])->toBe('0');
});
