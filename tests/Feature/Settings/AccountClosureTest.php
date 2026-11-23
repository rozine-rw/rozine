<?php

declare(strict_types=1);

use App\Application\Business\WithAuditApplicationBinding;
use App\Application\Identity\CloseAccount;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Domain\Identity\SandboxNotice;
use App\Infrastructure\Identity\EloquentAccountClosures;
use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use App\Models\AuditReport;
use App\Models\BusinessCampaign;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\DepositPolicy;
use App\Models\IdentityAuditEvent;
use App\Models\Party;
use App\Models\PrimaryHolding;
use App\Models\PrimaryReservationRecord;
use App\Models\User;
use App\Models\VerifiedPersonIdentity;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditorFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\BusinessQuoteFixture;
use Tests\Support\BusinessRepaymentFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;
use Tests\Support\PrimaryReservationFixture;
use Tests\Support\StaffFixture;

/** @return list<string> */
function closureBlockers(User $user): array
{
    return array_column(app(CloseAccount::class)->standing($user->id)['blockers'], 'code');
}

/** The Investor who made a commitment, from its reservation. */
function committedInvestor(string $reservationId): User
{
    $party = PrimaryReservationRecord::query()->whereKey($reservationId)->sole()->party_id;

    return User::query()->where('party_id', $party)->sole();
}

/** A repayment naming every instalment of the note, as the servicing record reads it. */
function repayNote(BusinessCampaign $campaign): void
{
    $wallet = BusinessRepaymentFixture::fundedWallet();
    $dueDates = DB::table('disbursements')->join('disbursement_closings', 'disbursement_closings.disbursement_id', '=', 'disbursements.id')
        ->where('disbursements.business_campaign_id', $campaign->id)->whereNotNull('disbursement_closings.due_dates')->value('disbursement_closings.due_dates');
    $instalments = range(1, count(json_decode((string) $dueDates, true)));
    DB::table('business_repayments')->insert(['id' => strtolower((string) Str::ulid()), 'wallet_id' => $wallet['wallet_id'],
        'business_id' => $wallet['business_id'], 'party_id' => $wallet['party_id'], 'note_id' => strtolower($campaign->id),
        'operation_id' => CommandOperation::factory()->create()->id, 'request_id' => (string) Str::uuid(), 'option' => 'due_now', 'amount' => '50000',
        'servicing_revision' => 1, 'sha256' => str_repeat('0', 64), 'created_at' => now(),
        'payload' => Crypt::encryptString(json_encode(['instalment_indexes' => $instalments, 'recorded_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR))]);
}

test('an account with nothing open is offered closure on the profile settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/profile')
            ->where('closure.roles', [])->where('closure.blockers', []));
});

test('closing an account soft deletes it, ends its sessions and records who closed it', function () {
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => Str::random(40), 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test',
        'payload' => '', 'last_activity' => now()->timestamp]);

    $this->actingAs($user)->post(route('account-closure.store'), [
        'password' => 'password', 'confirmation' => 'delete', 'reason' => '  Moving abroad  ',
    ])->assertSessionHasNoErrors()->assertRedirect('/');

    $this->assertGuest();
    $closed = $user->fresh();
    expect($closed)->not->toBeNull()
        ->and($closed->isClosed())->toBeTrue()
        ->and($closed->closure_reason)->toBe('Moving abroad')
        ->and(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse();
    $event = IdentityAuditEvent::query()->where('action', 'account.close')->sole();
    expect($event->actor_user_id)->toBe($user->id)->and($event->reason)->toBe('Moving abroad')->and($event->after['roles'])->toBe([]);
});

test('a blank reason is stored as none, and closing twice changes nothing', function () {
    $user = User::factory()->create();

    expect(app(CloseAccount::class)->handle($user->id, '   '))->toBe([])
        ->and($user->fresh()->closure_reason)->toBeNull()
        ->and(app(CloseAccount::class)->handle($user->id, 'again'))->toBe([])
        ->and(IdentityAuditEvent::query()->where('action', 'account.close')->count())->toBe(1);
    expect(IdentityAuditEvent::query()->where('action', 'account.close')->sole()->reason)->toBe('Closed by the account holder');
});

test('a closed account cannot sign in again and is told why', function () {
    $user = User::factory()->create();
    app(CloseAccount::class)->handle($user->id, null);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'This account has been closed. Contact info@rozine.rw if you need it reopened.']);
    $this->assertGuest();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('a closed account gives back its email, so the same address can sign up again', function () {
    $user = User::factory()->create(['email' => 'aminu@example.com']);
    app(CloseAccount::class)->handle($user->id, null);

    $closed = $user->fresh();
    expect($closed->email)->toBe(sprintf(EloquentAccountClosures::RELEASED_EMAIL, $user->id))
        ->and($closed->closed_email)->toBe('aminu@example.com')
        ->and(IdentityAuditEvent::query()->where('action', 'account.close')->sole()->after['email_released'])->toBeTrue();

    $this->post(route('register.store'), [
        'name' => 'Aminu Again', 'sandbox_notice_version' => SandboxNotice::VERSION, 'sandbox_notice_sha256' => SandboxNotice::sha256(), ...legalConsentFields(),
        'email' => 'aminu@example.com', 'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    $fresh = User::query()->where('email', 'aminu@example.com')->sole();
    expect($fresh->id)->not->toBe($user->id)->and($fresh->isClosed())->toBeFalse();
    $this->assertAuthenticatedAs($fresh);

    Auth::logout();
    $this->post(route('login.store'), ['email' => 'aminu@example.com', 'password' => 'new-password'])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($fresh);
    Auth::logout();
    $this->post(route('login.store'), ['email' => 'aminu@example.com', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
});

test('a closed account releases the verified ID, so the same person can be verified on a new account', function () {
    $officer = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($officer, ['compliance'], 'Compliance review assignment.');
    $verify = fn (User $person): array => app(IdentityAccessStore::class)->verifyInvestor($officer->id, $person->id, 'rw-nid:1199080012345678',
        'investor-verification:case@1', 'Matches.', (string) Str::uuid(), fn (): array => [], grantsInvestorAccess: false);

    $first = User::factory()->create(['party_id' => Party::factory()]);
    $verify($first);
    $firstParty = $first->fresh()->party_id;
    app(CloseAccount::class)->handle($first->id, null);

    expect(VerifiedPersonIdentity::query()->count())->toBe(0)
        ->and(DB::table('released_person_identities')->where('party_id', $firstParty)->where('closed_user_id', $first->id)->count())->toBe(1)
        ->and(IdentityAuditEvent::query()->where('action', 'account.close')->sole()->after['identity_released'])->toBeTrue();
    expect(fn () => DB::transaction(fn () => DB::table('released_person_identities')->delete()))->toThrow(QueryException::class, 'Released person identities are immutable');

    $second = User::factory()->create(['party_id' => Party::factory()]);
    expect($verify($second)['code'])->toBe('INVESTOR_VERIFIED');
    expect(VerifiedPersonIdentity::query()->sole()->party_id)->toBe($second->fresh()->party_id)
        ->and($second->fresh()->party_id)->not->toBe($firstParty);
});

test('an ID shared with another open account stays verified when one of them closes', function () {
    $party = Party::factory()->create();
    VerifiedPersonIdentity::factory()->create(['identity_digest' => hash('sha256', 'rw-nid:1'), 'party_id' => $party->id]);
    $closing = User::factory()->create(['party_id' => $party->id]);
    User::factory()->create(['party_id' => $party->id]);

    app(CloseAccount::class)->handle($closing->id, null);

    expect(VerifiedPersonIdentity::query()->sole()->party_id)->toBe($party->id)
        ->and(DB::table('released_person_identities')->count())->toBe(0);
});

test('an open account still signs in through the closure-aware check', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
});

test('a closed account is never found by the users provider, so its old sessions end', function () {
    $user = User::factory()->create(['closed_at' => now()]);

    expect(Auth::guard('web')->getProvider()->retrieveById($user->id))->toBeNull()
        ->and(Auth::guard('web')->getProvider()->retrieveById(User::factory()->create()->id))->not->toBeNull();
});

test('closing needs the right password and DELETE typed out', function (array $input, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user)->from(route('profile.edit'))->post(route('account-closure.store'), $input)
        ->assertSessionHasErrors($field)->assertRedirect(route('profile.edit'));

    expect($user->fresh()->isClosed())->toBeFalse();
})->with([
    'wrong password' => [['password' => 'wrong-password', 'confirmation' => 'DELETE'], 'password'],
    'no confirmation' => [['password' => 'password', 'confirmation' => 'close'], 'confirmation'],
]);

test('a staff account cannot close itself', function () {
    $staff = User::factory()->create();
    StaffFixture::provision($staff, ['superadmin'], 'Account closure test');

    expect(app(CloseAccount::class)->standing($staff->id))->toBe(['roles' => ['staff'],
        'blockers' => [['code' => 'STAFF_ACCOUNT', 'role' => 'staff', 'business_id' => null]]]);

    $this->actingAs($staff)->from(route('profile.edit'))->post(route('account-closure.store'), ['password' => 'password', 'confirmation' => 'DELETE'])
        ->assertSessionHasErrors('confirmation');
    expect($staff->fresh()->isClosed())->toBeFalse();
});

test('an Investor with money in the wallet is sent to withdraw it first', function () {
    $investor = InvestorWalletFixture::funded('50000');

    expect(closureBlockers($investor['user']))->toBe(['INVESTOR_WALLET_BALANCE']);
    $this->actingAs($investor['user'])->get(route('profile.edit'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('closure.roles', ['investor'])
            ->where('closure.blockers.0.link', ['url' => route('investor.wallet', [], false), 'method' => 'get']));
});

test('an Investor with a deposit still on its way cannot close yet', function () {
    $investor = InvestorWalletFixture::ready();
    InvestorWalletFixture::deposit($investor);

    expect(closureBlockers($investor['user']))->toBe(['INVESTOR_MONEY_MOVING']);
});

test('an Investor whose money is committed to an open raise cannot close yet', function () {
    $fixture = PrimaryHoldingFixture::committed(['1080'], fund: false);

    expect(closureBlockers(committedInvestor($fixture['commitments'][0]->primary_reservation_id)))->toContain('INVESTOR_COMMITMENTS');
});

test('an Investor holding a note still being repaid cannot close until it is repaid', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-10 10:00:00', 'Africa/Kigali'));
    PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $holding = PrimaryHolding::query()->orderBy('id')->firstOrFail();
    $investor = User::query()->where('party_id', $holding->party_id)->sole();
    $campaign = BusinessCampaign::query()->findOrFail($holding->business_campaign_id);
    $business = User::query()->findOrFail($campaign->actor_user_id);

    expect(closureBlockers($investor))->toContain('INVESTOR_HOLDINGS')
        ->and(closureBlockers($business))->toContain('BUSINESS_REPAYMENTS_OWED')
        ->and(closureBlockers($business))->not->toContain('BUSINESS_LIVE_RAISE');

    repayNote($campaign);

    expect(closureBlockers($investor))->not->toContain('INVESTOR_HOLDINGS')
        ->and(closureBlockers($business))->not->toContain('BUSINESS_REPAYMENTS_OWED');
});

test('an Investor with an open market listing cannot close until it ends', function () {
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-10 10:00:00', 'Africa/Kigali'));
    PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $holding = PrimaryHolding::query()->orderBy('id')->firstOrFail();
    $seller = User::query()->where('party_id', $holding->party_id)->sole();
    $listed = app(EloquentSecondaryMarket::class)->list($seller->id, 1, $holding->id, '10', '5000', (string) Str::uuid());
    PrimaryHoldingFixture::flushDeferredChecks();

    expect($listed['status'])->toBe('completed')
        ->and(closureBlockers($seller))->toContain('INVESTOR_MARKET_ORDERS', 'INVESTOR_HOLDINGS');
    $this->actingAs($seller)->get(route('profile.edit'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('closure.blockers', fn (Collection $blockers): bool => $blockers
            ->contains(fn (array $blocker): bool => $blocker['code'] === 'INVESTOR_MARKET_ORDERS'
                && $blocker['link']['url'] === route('investor.market', [], false))));
});

test('a Business with a live raise cannot close', function () {
    $campaign = PrimaryReservationFixture::campaign();
    $business = User::query()->findOrFail($campaign->actor_user_id);

    expect(closureBlockers($business))->toContain('BUSINESS_LIVE_RAISE');
    $this->actingAs($business)->get(route('profile.edit'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('closure.blockers.0.link.url', route('business.show', ['business' => $campaign->business_id], false)));
});

test('a Business with an application under review cannot close', function () {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));

    expect(closureBlockers($fixture['audit']['authority']['users'][0]))->toContain('BUSINESS_APPLICATION_IN_REVIEW');
});

test('a Business with money in its wallet cannot close', function () {
    $wallet = BusinessRepaymentFixture::fundedWallet();
    $user = User::query()->where('party_id', $wallet['party_id'])->sole();

    expect(closureBlockers($user))->toBe(['BUSINESS_WALLET_BALANCE']);
});

test('a Business with a deposit still on its way cannot close yet', function () {
    $authority = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    DepositPolicy::factory()->create(['fee' => '0', 'minimum' => '1000', 'maximum' => '1000000']);
    $method = BusinessFundingMethod::factory()->create(['business_id' => $business->id]);
    app(RecordBusinessDeposit::class)->handle($authority['users'][0]->id, 1, $business->id, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '5000'], $method->id);

    expect(closureBlockers($authority['users'][0]))->toBe(['BUSINESS_MONEY_MOVING']);
});

test('an Audit Partner with an open audit cannot close', function () {
    $partner = AuditorFixture::make();
    AuditAssignmentFixture::engagement($partner['party']->id);

    expect(closureBlockers($partner['user']))->toBe(['AUDITOR_OPEN_AUDITS']);
    $this->actingAs($partner['user'])->get(route('profile.edit'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('closure.roles', ['auditor'])
            ->where('closure.blockers.0.link.url', route('auditor.jobs.index', [], false)));
});

test('an Audit Partner with an unfinished report cannot close', function () {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $user = $fixture['audit']['partners'][0]['user'];
    app(WithAuditApplicationBinding::class)->handle($user->id, 1, $fixture['assignment']->id, $fixture['application']->id,
        fn (array $assignment, array $binding): AuditReport => AuditReport::factory()->forBinding($assignment, $binding)->create());

    expect(closureBlockers($user))->toContain('AUDITOR_REPORTS_PENDING');
});
