<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\PrimaryHolding;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(function (): void {
    $this->officer = directoryStaff(['compliance']);
});

/** @param  list<string>  $roles */
function directoryStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Investor directory assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/** A person's own identity submission, driven to `submitted` through the participant commands. */
function directorySubmitted(User $user): InvestorVerification
{
    $context = $user->context_revision;
    $png = "\x89PNG\r\n\x1a\nsynthetic identity image";
    $revision = 0;
    $step = function (array $result) use (&$revision): void {
        expect($result['status'])->toBe('completed');
        $revision++;
    };
    $step(app(SaveInvestorVerification::class)->handle($user->id, $context, $revision, 'personal', ['date_of_birth' => '1/5/1990'], (string) Str::uuid()));
    $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'id_front', 'front.png', $png, (string) Str::uuid()));
    $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'id_back', 'back.png', $png, (string) Str::uuid()));
    $step(app(SaveInvestorVerification::class)->handle($user->id, $context, $revision, 'document', ['id_type' => 'national_id', 'id_number' => '1 1990 8 0012345 6 78'], (string) Str::uuid()));
    $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'selfie', 'selfie.png', $png, (string) Str::uuid()));
    $step(app(SubmitInvestorVerification::class)->handle($user->id, $context, $revision, (string) Str::uuid()));

    return InvestorVerification::query()->where('party_id', $user->party_id)->sole();
}

test('Compliance and superadmin see the designed Investor directory; other staff and guests do not', function (): void {
    $this->actingAs($this->officer)->get(route('staff.investors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/parties')->where('kind', 'investor')->where('viewer.role', 'compliance')
            ->where('nav.investors.url', '/admin/investors')->where('party', null));
    $this->actingAs(directoryStaff(['superadmin']))->get(route('staff.investors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'superadmin'));
    $this->actingAs(directoryStaff(['approver']))->get(route('staff.investors.index'))->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', 'STAFF_PERMISSION_REQUIRED'));
    auth()->logout();
    $this->get(route('staff.investors.index'))->assertRedirect(route('login'));
});

test('an empty platform shows the design with zero figures and no rows', function (): void {
    $this->actingAs($this->officer)->get(route('staff.investors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats', [['key' => 'total_investors', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'kyc_verified', 'value' => ['kind' => 'percent', 'value' => '0']],
                ['key' => 'awaiting_kyc', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'total_aum', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => '0']]]])
            ->where('chips.0', ['key' => 'all', 'count' => 0, 'link' => ['url' => '/admin/investors', 'method' => 'get'], 'active' => true])
            ->where('filters.0.key', 'sort')->where('filters.0.value', 'portfolio')
            ->where('shown', 0)->where('total', 0)->where('directory', ['kind' => 'investor', 'rows' => []]));
});

test('rows carry real KYC states, ledger money and issued holdings, and the chips count them', function (): void {
    // One Investor makes two purchases and another takes the rest of the raise; every note issues.
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed(['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing);
        PrimaryHoldingFixture::issue($commitment->id, $closing);
    }
    PrimaryHoldingFixture::flushDeferredChecks();
    $holding = PrimaryHolding::query()->where('commitment_id', $commitments[0]->id)->sole();
    $principal = (string) PrimaryHolding::query()->where('party_id', $holding->party_id)->sum('principal');
    $holder = User::query()->where('party_id', $holding->party_id)->sole();

    $waiting = User::factory()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]);
    $case = directorySubmitted($waiting);
    $member = User::factory()->create(['name' => 'Bosco Habimana', 'party_id' => Party::factory()]);
    RoleMembership::factory()->create(['party_id' => $member->party_id, 'role' => 'investor']);
    InvestorAccountRestriction::factory()->create(['party_id' => $member->party_id, 'effective_at' => now()->subDay(), 'expires_at' => null]);
    $rejected = User::factory()->create(['name' => 'Claude Niyonzima', 'party_id' => Party::factory()]);
    directorySubmitted($rejected)->forceFill(['status' => 'rejected'])->save();

    $aum = (string) PrimaryHolding::query()->sum('principal');
    $this->actingAs($this->officer)->get(route('staff.investors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.0.value.value', 5)->where('stats.1.value.value', '40')->where('stats.2.value.value', 1)
            ->where('stats.3.value.value.amount', $aum)
            ->where('chips', fn (Collection $chips): bool => $chips->pluck('count', 'key')->all()
                === ['all' => 5, 'verified' => 2, 'pending' => 2, 'kyc_overdue' => 0, 'frozen' => 0, 'restricted' => 1])
            ->where('shown', 5)->where('total', 5)
            ->where('directory.rows', function (Collection $rows) use ($holder, $principal): bool {
                $held = $rows->firstWhere('id', $holder->party_id);

                return $rows->take(2)->pluck('kyc')->all() === ['verified', 'verified']
                    && $rows->slice(2)->pluck('name')->all() === ['Aline Uwase', 'Bosco Habimana', 'Claude Niyonzima']
                    && $rows->pluck('kyc', 'name')->only(['Aline Uwase', 'Bosco Habimana', 'Claude Niyonzima'])->all()
                        === ['Aline Uwase' => 'pending', 'Bosco Habimana' => 'pending', 'Claude Niyonzima' => 'rejected']
                    && $rows->firstWhere('name', 'Bosco Habimana')['restricted'] === true
                    && $held['name'] === $holder->name && $held['country'] === '' && $held['portfolio'] === ['currency' => 'RWF', 'amount' => $principal]
                    && is_string($held['wallet']['amount']) && $held['holdings'] === 2 && $held['businesses'] === 1
                    && $held['frozen'] === false && $held['restricted'] === false
                    && $held['link'] === ['url' => '/admin/investors?investor='.$holder->party_id, 'method' => 'get'];
            }));

    $this->actingAs($this->officer)->get(route('staff.investors.index', ['chip' => 'pending', 'sort' => 'name', 'q' => 'aline']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('search', 'aline')->where('filters.0.value', 'name')
            ->where('chips.2', ['key' => 'pending', 'count' => 1, 'link' => ['url' => '/admin/investors?q=aline&sort=name&chip=pending', 'method' => 'get'], 'active' => true])
            ->where('shown', 1)->where('total', 1)->where('directory.rows.0.name', 'Aline Uwase')
            ->where('directory.rows.0.link.url', '/admin/investors?q=aline&sort=name&chip=pending&investor='.$waiting->party_id));
    foreach (['verified' => 2, 'restricted' => 1, 'kyc_overdue' => 0, 'frozen' => 0] as $chip => $count) {
        $this->actingAs($this->officer)->get(route('staff.investors.index', ['chip' => $chip]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('total', $count));
    }

    $this->actingAs($this->officer)->get(route('staff.investors.index', ['investor' => $holder->party_id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.kind', 'investor')->where('party.health', 'active')
            // The 360 counts both holdings but lists none until the S3-C adapter exposes them.
            ->where('party.stats.2', ['key' => 'holdings', 'value' => ['kind' => 'count', 'value' => 2]])->where('party.list', null)
            ->where('party.verification', null)->where('party.history', []));
    expect($case->status)->toBe('submitted');
});

test("a person's 360 carries their identity submission, its documents and history, and a case link opens it", function (): void {
    $person = User::factory()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]);
    $case = directorySubmitted($person);
    InvestorAccountRestriction::factory()->create(['party_id' => $person->party_id, 'effective_at' => now()->subHour(), 'expires_at' => now()->addDay()]);

    $assert = fn (Assert $page) => $page->where('party.id', $person->party_id)->where('party.name', 'Aline Uwase')->where('party.subtitle', $person->email)
        ->where('party.health', 'kyc_pending')->where('party.kyc', ['state' => 'pending', 'due_on' => null])
        ->where('party.restricted_since', fn ($since): bool => is_string($since))->where('party.list', null)
        ->where('party.history.0.action', ['code' => 'verification.submit', 'label' => 'Submitted for review', 'tone' => 'blue'])
        ->where('party.history.0.actor', 'Aline Uwase')->where('party.history.5.action.label', 'Saved a step')
        ->where('party.verification.id', $case->id)->where('party.verification.status', 'submitted')->has('party.verification.documents', 3)
        ->where('party.verification.documents.0.view.url', fn (string $url): bool => str_ends_with($url, '?disposition=inline'))
        ->where('party.verification.actions.approve.url', '/admin/investor-verifications/'.$case->id.'/approve')
        ->where('party.verification.links.close.url', '/admin/investors')->where('party.links.close.url', '/admin/investors');

    $this->actingAs($this->officer)->get(route('staff.investors.index', ['investor' => $person->party_id]))->assertOk()->assertInertia($assert);
    $this->actingAs($this->officer)->get(route('staff.investors.index', ['verification' => $case->id]))->assertOk()->assertInertia($assert);
    $this->actingAs($this->officer)->get(route('staff.investors.index', ['investor' => strtolower((string) Str::ulid())]))->assertNotFound();
    $this->actingAs($this->officer)->get(route('staff.investors.index', ['verification' => strtolower((string) Str::ulid())]))->assertNotFound();
    $this->actingAs($this->officer)->get(route('staff.investors.index', ['chip' => 'everyone']))->assertSessionHasErrors('chip');
});

test('a decision taken from the directory returns to that person there; one from the queue still returns to the queue', function (): void {
    $person = User::factory()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]);
    $case = directorySubmitted($person);
    $decision = fn (array $extra = []): array => ['request_id' => (string) Str::uuid(), 'expected_revision' => $case->fresh()?->revision,
        'reason' => 'The documents do not match the account holder.', ...$extra];

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', $case->id), $decision(['return_to' => 'directory']))
        ->assertRedirect(route('staff.investors.index', ['verification' => $case->id]));
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', $case->id), $decision(['return_to' => 'elsewhere']))
        ->assertSessionHasErrors('return_to');
});

test('the directory answers over the API with the read ability and links API routes', function (): void {
    $person = User::factory()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]);
    directorySubmitted($person);

    Sanctum::actingAs($this->officer, ['staff:investors:read']);
    $this->getJson('/api/v1/staff/investors?investor='.$person->party_id)->assertOk()
        ->assertJsonPath('data.contract_version', 'staff-investor-directory-v1')
        ->assertJsonPath('data.nav.investors.url', '/api/v1/staff/investors')
        ->assertJsonPath('data.directory.rows.0.link.url', '/api/v1/staff/investors?investor='.$person->party_id)
        ->assertJsonPath('data.party.verification.documents.0.view.url', fn (string $url): bool => str_starts_with($url, '/api/v1/staff/investor-verifications/'));
    Sanctum::actingAs($this->officer, ['staff:investors:verify']);
    $this->getJson('/api/v1/staff/investors')->assertForbidden();
});
