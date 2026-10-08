<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\ReviewInvestorVerifications;
use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Domain\Identity\IdentityViolation;
use App\Models\CommandOperation;
use App\Models\IdentityAuditEvent;
use App\Models\InvestorVerification;
use App\Models\InvestorVerificationDocument;
use App\Models\InvestorVerificationVersion;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use App\Models\VerifiedPersonIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 6)->startOfDay());
    $this->officer = reviewStaff(['compliance']);
    $this->person = User::factory()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]);
});

/** @param  list<string>  $roles */
function reviewStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Compliance review assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/** Drives a person's own submission to `submitted` through the participant commands. */
function reviewSubmitted(User $user, string $type = 'national_id', string $number = '1 1990 8 0012345 6 78'): InvestorVerification
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
    if ($type !== 'passport') {
        $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'id_back', 'back.png', $png, (string) Str::uuid()));
    }
    $step(app(SaveInvestorVerification::class)->handle($user->id, $context, $revision, 'document', ['id_type' => $type, 'id_number' => $number], (string) Str::uuid()));
    $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'selfie', 'selfie.png', $png, (string) Str::uuid()));
    $step(app(SubmitInvestorVerification::class)->handle($user->id, $context, $revision, (string) Str::uuid()));

    return InvestorVerification::query()->where('party_id', $user->party_id)->sole();
}

/** @return array<string, mixed> */
function reviewDecision(InvestorVerification $case, string $reason = 'Document and selfie match the account holder.', ?string $requestId = null): array
{
    return ['request_id' => $requestId ?? (string) Str::uuid(), 'expected_revision' => $case->revision, 'reason' => $reason];
}

test('Compliance and superadmin hold investors.verify; other staff and participants do not see the queue', function (): void {
    reviewSubmitted($this->person);
    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/investor-verifications')->where('viewer.role', 'compliance'));
    $this->actingAs(reviewStaff(['superadmin']))->get(route('staff.investor-verifications.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'superadmin'));
    $this->actingAs(reviewStaff(['approver']))->get(route('staff.investor-verifications.index'))->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', 'STAFF_PERMISSION_REQUIRED'));
    $this->actingAs($this->person)->get(route('staff.investor-verifications.index'))->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->where('code', 'STAFF_ACCESS_REQUIRED'));
});

test('the queue lists submitted cases oldest first and pages by cursor', function (): void {
    $first = reviewSubmitted($this->person);
    $this->travel(5)->minutes();
    $second = reviewSubmitted(User::factory()->create(['name' => 'Jean Habimana', 'party_id' => Party::factory()]), 'passport', 'pc 123456');
    $drafting = User::factory()->create(['party_id' => Party::factory()]);
    app(SaveInvestorVerification::class)->handle($drafting->id, $drafting->context_revision, 0, 'personal', ['date_of_birth' => '1/5/1990'], (string) Str::uuid());

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['limit' => 1]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('contract_version', 'staff-investor-verifications-v1')
            ->where('active_tab', 'submitted')->where('tabs.0.count', 2)->where('tabs.1.count', 0)
            ->where('nav.investors.url', '/admin/investor-verifications')->where('nav.applications', null)
            ->has('entries', 1)->where('entries.0.id', $first->id)->where('entries.0.name', 'Aline Uwase')
            ->where('entries.0.email', $this->person->email)->where('entries.0.id_type', 'national_id')->where('entries.0.selected', false)
            ->where('entries.0.link.url', '/admin/investor-verifications?tab=submitted&verification='.$first->id)
            ->where('pagination.next.url', '/admin/investor-verifications?tab=submitted&before='.$first->id)
            ->where('review', null));

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['limit' => 1, 'before' => $first->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('entries', 1)->where('entries.0.id', $second->id)
            ->where('entries.0.id_type', 'passport')->where('pagination.next', null));

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['before' => strtolower((string) Str::ulid())]))
        ->assertStatus(422)->assertInertia(fn (Assert $page) => $page->where('code', 'CURSOR_INVALID'));
});

test('a selected case shows its answers, private document links and history', function (): void {
    $case = reviewSubmitted($this->person);
    $front = InvestorVerificationDocument::query()->where('investor_verification_id', $case->id)->where('slot', 'front')->sole();

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['verification' => $case->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('entries.0.selected', true)
            ->where('review.id', $case->id)->where('review.revision', 6)->where('review.status', 'submitted')
            ->where('review.account', ['name' => 'Aline Uwase', 'email' => $this->person->email])
            ->where('review.date_of_birth', '01 / 05 / 1990')->where('review.id_type', 'national_id')->where('review.id_number', '1199080012345678')
            ->where('review.decision', null)->has('review.documents', 3)
            ->where('review.documents.0.slot', 'front')->where('review.documents.0.filename', 'front.png')
            ->where('review.documents.0.media_type', 'image/png')->where('review.documents.0.current', true)
            ->where('review.documents.0.link.url', '/admin/investor-verifications/'.$case->id.'/documents/'.$front->id)
            ->where('review.documents.0.view.url', '/admin/investor-verifications/'.$case->id.'/documents/'.$front->id.'?disposition=inline')
            ->has('review.history', 6)->where('review.history.5.status', 'submitted')->where('review.history.5.command', 'verification.submit')
            ->where('review.links.close.url', '/admin/investor-verifications?tab=submitted')
            ->where('review.actions.approve.url', '/admin/investor-verifications/'.$case->id.'/approve')
            ->where('review.actions.reject.url', '/admin/investor-verifications/'.$case->id.'/reject'));

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['verification' => strtolower((string) Str::ulid())]))
        ->assertNotFound()->assertInertia(fn (Assert $page) => $page->where('code', 'VERIFICATION_NOT_FOUND'));
});

test('a document is read privately under investors.verify and only through its own case', function (): void {
    $case = reviewSubmitted($this->person);
    $other = reviewSubmitted(User::factory()->create(['party_id' => Party::factory()]), 'passport', 'AB123456');
    $front = InvestorVerificationDocument::query()->where('investor_verification_id', $case->id)->where('slot', 'front')->sole();

    $response = $this->actingAs($this->officer)->get(route('staff.investor-verifications.document', [$case->id, $front->id]))->assertOk();
    expect($response->getContent())->toBe("\x89PNG\r\n\x1a\nsynthetic identity image")
        ->and($response->headers->get('Content-Type'))->toBe('image/png')
        ->and($response->headers->get('Content-Disposition'))->toBe('attachment; filename=front.png')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.document', [$other->id, $front->id]))->assertNotFound();
    $this->actingAs(reviewStaff(['approver']))->get(route('staff.investor-verifications.document', [$case->id, $front->id]))->assertForbidden();
});

test('a document can be served for viewing in place, and any other disposition still downloads', function (): void {
    $case = reviewSubmitted($this->person);
    $front = InvestorVerificationDocument::query()->where('investor_verification_id', $case->id)->where('slot', 'front')->sole();

    $inline = $this->actingAs($this->officer)->get(route('staff.investor-verifications.document', [$case->id, $front->id, 'disposition' => 'inline']))->assertOk();
    expect($inline->getContent())->toBe("\x89PNG\r\n\x1a\nsynthetic identity image")
        ->and($inline->headers->get('Content-Type'))->toBe('image/png')
        ->and($inline->headers->get('Content-Disposition'))->toBe('inline; filename=front.png')
        ->and($inline->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($inline->headers->get('Cache-Control'))->toContain('no-store');

    $other = $this->actingAs($this->officer)->get(route('staff.investor-verifications.document', [$case->id, $front->id, 'disposition' => 'render']))->assertOk();
    expect($other->headers->get('Content-Disposition'))->toBe('attachment; filename=front.png');

    $this->actingAs(reviewStaff(['approver']))->get(route('staff.investor-verifications.document', [$case->id, $front->id, 'disposition' => 'inline']))->assertForbidden();
});

test('approval verifies the person through the one writer and activates the Investor membership', function (): void {
    $case = reviewSubmitted($this->person);
    $command = reviewDecision($case);
    $context = $this->person->context_revision;

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), $command)
        ->assertRedirect(route('staff.investor-verifications.index', ['verification' => $case->id]))->assertSessionHasNoErrors();

    $party = Party::query()->findOrFail($this->person->party_id);
    $identity = VerifiedPersonIdentity::query()->findOrFail(hash('sha256', 'rw-nid:1199080012345678'));
    $membership = RoleMembership::query()->where('party_id', $party->id)->sole();
    $case->refresh();
    expect($party->verified_at?->toIso8601String())->toBe(now()->toIso8601String())
        ->and($identity->party_id)->toBe($party->id)
        ->and($identity->evidence_reference)->toBe('investor-verification:'.$case->id.'@6')
        ->and([$membership->role, $membership->status, $membership->revision])->toBe(['investor', 'active', 1])
        ->and($this->person->refresh()->context_revision)->toBe($context + 1)
        ->and([$case->status, $case->revision, $case->state['decision']['outcome'], $case->state['decision']['reason']])
        ->toBe(['approved', 7, 'approved', 'Document and selfie match the account holder.']);

    $version = InvestorVerificationVersion::query()->where('investor_verification_id', $case->id)->where('revision', 7)->sole();
    expect([$version->getAttribute('command'), $version->getAttribute('reason'), $version->getAttribute('actor_user_id')])
        ->toBe(['verification.approve', 'Document and selfie match the account holder.', $this->officer->id]);

    $event = IdentityAuditEvent::query()->where('action', 'investor.verify')->sole();
    expect($event->actor_key)->toBe('user:'.$this->officer->id)
        ->and($event->result['code'])->toBe('INVESTOR_VERIFIED')
        ->and($event->result['membership'])->toEqual(['id' => $membership->id, 'role' => 'investor', 'status' => 'active', 'revision' => 1])
        ->and($event->result['verification'])->toEqual(['verification_id' => $case->id, 'revision' => 7,
            'documents' => InvestorVerificationDocument::query()->whereIn('id', array_filter($case->state['uploads']))->get()
                ->mapWithKeys(fn (InvestorVerificationDocument $document): array => [$document->slot => $document->sha256])->all()]);

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), $command)->assertSessionHasNoErrors();
    expect(CommandOperation::query()->where('command', 'investor.verification.approve')->count())->toBe(1)
        ->and(IdentityAuditEvent::query()->where('action', 'investor.verify')->count())->toBe(1);

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['tab' => 'decided', 'verification' => $case->id]))
        ->assertInertia(fn (Assert $page) => $page->where('tabs.0.count', 0)->where('tabs.1.count', 1)->where('entries.0.status', 'approved')
            ->where('entries.0.decided_at', now()->toIso8601String())->where('review.decision.outcome', 'approved')
            ->where('review.documents.0.link.url', fn (string $url): bool => str_starts_with($url, '/admin/investor-verifications/'))
            ->where('review.actions', []));
    $this->actingAs($this->person->refresh())->get(route('investor.verification'))->assertRedirect(route('dashboard'));
});

test('rejection keeps the reason for the participant, whose next edit reopens the case', function (): void {
    $case = reviewSubmitted($this->person);

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', $case), reviewDecision($case, 'The ID photo is blurred. Upload a sharper photo.'))
        ->assertSessionHasNoErrors();
    expect([$case->refresh()->status, $case->revision, $case->state['decision']['reason']])
        ->toBe(['rejected', 7, 'The ID photo is blurred. Upload a sharper photo.'])
        ->and(Party::query()->findOrFail($this->person->party_id)->verified_at)->toBeNull()
        ->and(RoleMembership::query()->count())->toBe(0);

    $this->actingAs($this->person)->get(route('investor.verification'))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'rejected')->where('decision_reason', 'The ID photo is blurred. Upload a sharper photo.'));
    expect(app(UploadInvestorVerificationDocument::class)->handle($this->person->id, $this->person->context_revision, 7, 'id_front', 'sharper.png',
        "\x89PNG\r\n\x1a\nsharper image", (string) Str::uuid())['status'])->toBe('completed')
        ->and($case->refresh()->status)->toBe('draft');
});

test('a decision on a moved or closed case is refused and changes nothing', function (): void {
    $case = reviewSubmitted($this->person);

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), [...reviewDecision($case), 'expected_revision' => 5])
        ->assertSessionHasErrors(['form' => 'This case changed since you opened it. Review it again.']);
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', $case), reviewDecision($case, 'Unreadable.'))->assertSessionHasNoErrors();
    $case->refresh();
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), reviewDecision($case))
        ->assertSessionHasErrors(['form' => 'This case is no longer waiting for review.']);
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', strtolower((string) Str::ulid())), reviewDecision($case))
        ->assertSessionHasErrors(['form' => 'This case no longer exists.']);
    expect(Party::query()->findOrFail($this->person->party_id)->verified_at)->toBeNull()
        ->and($case->status)->toBe('rejected');
});

test('an identity already verified for another person is never relinked by staff approval', function (): void {
    $case = reviewSubmitted($this->person);
    $other = Party::factory()->create();
    $other->forceFill(['verified_at' => now()->subDay()])->save();
    (new VerifiedPersonIdentity)->forceFill(['identity_digest' => hash('sha256', 'rw-nid:1199080012345678'), 'party_id' => $other->id,
        'evidence_reference' => 'operator:prior'])->save();

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), reviewDecision($case))
        ->assertSessionHasErrors(['form' => 'This ID is already verified for another person, or the account needs an identity operator. It cannot be approved here.']);
    expect($this->person->refresh()->party_id)->not->toBe($other->id)
        ->and($case->refresh()->status)->toBe('submitted')
        ->and(Party::query()->findOrFail($this->person->party_id)->verified_at)->toBeNull()
        ->and(CommandOperation::query()->where('command', 'investor.verification.approve')->sole()->result['code'])->toBe('IDENTITY_RECONCILIATION_REQUIRED');
});

test('a case whose Party has several accounts, or an unverified email, waits for an identity operator', function (): void {
    $case = reviewSubmitted($this->person);
    User::factory()->create(['party_id' => $this->person->party_id]);
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), reviewDecision($case))
        ->assertSessionHasErrors(['form' => 'This ID is already verified for another person, or the account needs an identity operator. It cannot be approved here.']);

    $unverified = User::factory()->unverified()->create(['party_id' => Party::factory()]);
    $pending = reviewSubmitted($unverified, 'passport', 'XY987654');
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $pending), reviewDecision($pending))
        ->assertSessionHasErrors(['form' => 'The account needs a verified email and cannot be a staff or operator account.']);
    expect($pending->refresh()->status)->toBe('submitted');
});

test('staff without investors.verify cannot decide, and every decision needs a reason', function (): void {
    $case = reviewSubmitted($this->person);
    $this->actingAs(reviewStaff(['approver']))->post(route('staff.investor-verifications.approve', $case), reviewDecision($case))->assertForbidden();
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', $case), [...reviewDecision($case), 'reason' => ''])
        ->assertSessionHasErrors(['reason' => 'Give the reason for this decision.']);
    expect(app(ReviewInvestorVerifications::class)->reject($this->officer->id, $case->id, $case->revision, '   ', (string) Str::uuid()))
        ->toMatchArray(['status' => 'rejected', 'code' => 'DECISION_REASON_REQUIRED', 'http_status' => 422]);

    $key = (string) Str::uuid();
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', $case), reviewDecision($case, 'Blurred.', $key))->assertSessionHasNoErrors();
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', $case), reviewDecision($case, 'Different words.', $key))
        ->assertSessionHasErrors(['form' => 'That request was already used for different details. Try again.']);
    expect($case->refresh()->status)->toBe('rejected')
        ->and(Party::query()->findOrFail($this->person->party_id)->verified_at)->toBeNull();
});

test('the queue searches account names and emails and pages decided cases newest first', function (): void {
    $aline = reviewSubmitted($this->person);
    $this->travel(1)->minutes();
    $jean = reviewSubmitted(User::factory()->create(['name' => 'Jean Habimana', 'email' => 'jean@example.rw', 'party_id' => Party::factory()]), 'passport', 'JH123456');

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['search' => 'HABIM']))
        ->assertInertia(fn (Assert $page) => $page->where('search', 'HABIM')->has('entries', 1)->where('entries.0.id', $jean->id)
            ->where('tabs.1.link.url', '/admin/investor-verifications?tab=decided&search=HABIM'));
    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['search' => 'jean@example']))
        ->assertInertia(fn (Assert $page) => $page->has('entries', 1));
    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['search' => '%']))
        ->assertInertia(fn (Assert $page) => $page->has('entries', 0));

    foreach ([$aline, $jean] as $case) {
        $this->travel(1)->minutes();
        $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', $case), reviewDecision($case, 'Blurred photo.'))->assertSessionHasNoErrors();
    }
    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['tab' => 'decided', 'limit' => 1]))
        ->assertInertia(fn (Assert $page) => $page->where('active_tab', 'decided')->where('entries.0.id', $jean->id)->where('entries.0.status', 'rejected')
            ->where('pagination.next.url', '/admin/investor-verifications?tab=decided&before='.$jean->id));
    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['tab' => 'decided', 'limit' => 1, 'before' => $jean->id]))
        ->assertInertia(fn (Assert $page) => $page->has('entries', 1)->where('entries.0.id', $aline->id)->where('pagination.next', null));
});

test('both decisions lock the two accounts in ascending ID before reading the reviewer\'s permission', function (): void {
    $case = reviewSubmitted($this->person);
    $reviewer = reviewStaff(['compliance']);
    expect($this->person->id)->toBeLessThan($reviewer->id);
    $userLocks = function (Closure $decision): array {
        $locks = [];
        DB::listen(function ($query) use (&$locks): void {
            $ids = [];
            if (preg_match('/from "users" where "users"\."id" in \(([0-9, ]+)\) order by "id" asc for update/', $query->sql, $ids) === 1) {
                $locks[] = array_map('intval', explode(', ', $ids[1]));
                sort($locks[array_key_last($locks)]);
            } elseif (str_contains($query->sql, 'from "users"') && str_contains($query->sql, 'for update')) {
                $locks[] = $query->bindings;
            }
        });
        $decision();

        return $locks;
    };

    $locks = $userLocks(fn () => $this->actingAs($reviewer)->post(route('staff.investor-verifications.reject', $case), reviewDecision($case, 'Blurred.')));
    expect($locks[0])->toBe([$this->person->id, $reviewer->id]);

    $other = User::factory()->create(['party_id' => Party::factory()]);
    $pending = reviewSubmitted($other, 'passport', 'LK123456');
    $late = reviewStaff(['compliance']);
    $locks = $userLocks(fn () => $this->actingAs($late)->post(route('staff.investor-verifications.approve', $pending), reviewDecision($pending)));
    expect($locks[0])->toBe([$other->id, $late->id])
        ->and($pending->refresh()->status)->toBe('approved');
});

test('a replayed decision rechecks the reviewer\'s current permission, and changed input is refused', function (): void {
    $case = reviewSubmitted($this->person);
    $command = reviewDecision($case);
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), $command)->assertSessionHasNoErrors();
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), [...$command, 'reason' => 'Another reason.'])
        ->assertSessionHasErrors(['form' => 'That request was already used for different details. Try again.']);

    app(ConfigureStaffAccess::class)->handle($this->officer->id, true, 'Moved to analysis.', (string) Str::uuid(), ['analyst']);
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), $command)->assertForbidden();
    expect(CommandOperation::query()->where('command', 'investor.verification.approve')->count())->toBe(1);
});

test('a failure after the person and membership are written rolls every effect back', function (): void {
    $case = reviewSubmitted($this->person);
    $context = $this->person->context_revision;

    expect(fn () => app(IdentityAccessStore::class)->verifyInvestor($this->officer->id, $this->person->id, 'rw-nid:1199080012345678',
        'investor-verification:'.$case->id.'@6', 'Matches.', (string) Str::uuid(), fn (): never => throw new RuntimeException('Submission lock failed.')))
        ->toThrow(RuntimeException::class, 'Submission lock failed.');

    expect(Party::query()->findOrFail($this->person->party_id)->verified_at)->toBeNull()
        ->and(VerifiedPersonIdentity::query()->count())->toBe(0)
        ->and(RoleMembership::query()->count())->toBe(0)
        ->and($this->person->refresh()->context_revision)->toBe($context)
        ->and(IdentityAuditEvent::query()->where('action', 'investor.verify')->count())->toBe(0)
        ->and($case->refresh()->status)->toBe('submitted');

    expect(fn () => app(IdentityAccessStore::class)->verifyInvestor(reviewStaff(['approver'])->id, $this->person->id, 'rw-nid:1199080012345678',
        'investor-verification:'.$case->id.'@6', 'Matches.', (string) Str::uuid(), fn (): array => []))
        ->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
});

test('an existing membership is never reset by an approval', function (): void {
    $case = reviewSubmitted($this->person);
    (new RoleMembership)->forceFill(['party_id' => $this->person->party_id, 'role' => 'investor', 'status' => 'revoked', 'revision' => 3])->save();

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), reviewDecision($case))
        ->assertSessionHasErrors(['form' => 'This ID is already verified for another person, or the account needs an identity operator. It cannot be approved here.']);
    expect(RoleMembership::query()->sole()->only(['status', 'revision']))->toBe(['status' => 'revoked', 'revision' => 3])
        ->and(Party::query()->findOrFail($this->person->party_id)->verified_at)->toBeNull()
        ->and($case->refresh()->status)->toBe('submitted');
});

test('an approval binds the submission\'s own hash-pinned uploads and refuses one that no longer matches', function (): void {
    $case = reviewSubmitted($this->person);
    $case->forceFill(['state' => [...$case->state, 'uploads' => [...$case->state['uploads'], 'selfie' => strtolower((string) Str::ulid())]]])->save();

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), reviewDecision($case))->assertSessionHasErrors('form');
    expect(CommandOperation::query()->where('command', 'investor.verification.approve')->sole()->result['code'])->toBe('VERIFICATION_DOCUMENT_REQUIRED')
        ->and(Party::query()->findOrFail($this->person->party_id)->verified_at)->toBeNull()
        ->and($case->refresh()->status)->toBe('submitted');
});

test('an approval re-hashes every current upload and refuses one whose content no longer matches, changing nothing', function (): void {
    $case = reviewSubmitted($this->person);
    $tampered = new InvestorVerificationDocument;
    $tampered->forceFill(['investor_verification_id' => $case->id, 'slot' => 'front', 'filename' => 'front.png', 'media_type' => 'image/png',
        'size_bytes' => 4, 'sha256' => str_repeat('0', 64), 'content' => 'fake', 'actor_user_id' => $this->person->id])->save();
    $case->forceFill(['state' => [...$case->state, 'uploads' => [...$case->state['uploads'], 'front' => $tampered->id]]])->save();
    $context = $this->person->refresh()->context_revision;
    $versions = InvestorVerificationVersion::query()->count();

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), reviewDecision($case))
        ->assertSessionHasErrors(['form' => 'A document on this case no longer matches what was uploaded, so it cannot be approved. Reject it so the person can upload it again.']);

    expect(CommandOperation::query()->where('command', 'investor.verification.approve')->sole()->result['code'])->toBe('VERIFICATION_DOCUMENT_INTEGRITY_FAILED')
        ->and(Party::query()->findOrFail($this->person->party_id)->verified_at)->toBeNull()
        ->and(RoleMembership::query()->count())->toBe(0)
        ->and($this->person->refresh()->context_revision)->toBe($context)
        ->and($case->refresh()->only(['status', 'revision']))->toBe(['status' => 'submitted', 'revision' => 6])
        ->and(InvestorVerificationVersion::query()->count())->toBe($versions)
        ->and(IdentityAuditEvent::query()->where('action', 'investor.verify')->count())->toBe(0);
});

test('a document whose name carries a percent sign downloads with a safe fallback name', function (): void {
    $case = reviewSubmitted($this->person);
    $content = "\x89PNG\r\n\x1a\nsynthetic identity image";
    $document = new InvestorVerificationDocument;
    $document->forceFill(['investor_verification_id' => $case->id, 'slot' => 'front', 'filename' => 'ID 100%.png', 'media_type' => 'image/png',
        'size_bytes' => strlen($content), 'sha256' => hash('sha256', $content), 'content' => $content, 'actor_user_id' => $this->person->id])->save();

    $response = $this->actingAs($this->officer)->get(route('staff.investor-verifications.document', [$case->id, $document->id]))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toBe("attachment; filename=\"ID 100_.png\"; filename*=utf-8''ID%20100%25.png");
});

test('a document whose content no longer matches its pinned hash is not served', function (): void {
    $case = reviewSubmitted($this->person);
    $tampered = new InvestorVerificationDocument;
    $tampered->forceFill(['investor_verification_id' => $case->id, 'slot' => 'front', 'filename' => 'front.png', 'media_type' => 'image/png',
        'size_bytes' => 4, 'sha256' => str_repeat('0', 64), 'content' => 'fake', 'actor_user_id' => $this->person->id])->save();

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.document', [$case->id, $tampered->id]))->assertStatus(409);
});
