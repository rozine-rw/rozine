<?php

declare(strict_types=1);

use App\Domain\Identity\OnboardingHub;
use App\Domain\Identity\RevisionItems;
use App\Domain\Operations\CommandRejection;


/**
 * @param  array{status?: 'none'|'draft'|'submitted'|'rejected'|'approved', submitted_at?: string, approved_at?: string, reason?: string, requested_at?: string}  $kyc
 * @param  array{role?: 'business'|'auditor', status?: 'draft'|'submitted'|'changes_requested'|'approved'|'rejected', submitted_at?: string, note?: string,
 *     items?: list<array{key: string, label: string, note: string}>, requested_at?: string}|null  $role
 * @param  list<string>  $roles
 * @return array{name: string, created_at: ?string, email_verified_at: ?string, two_factor_at: ?string,
 *     kyc: array{status: 'none'|'draft'|'submitted'|'rejected'|'approved', submitted_at: ?string, approved_at: ?string, reason: ?string,
 *         items: list<array{key: string, label: string, note: string}>, requested_at: ?string},
 *     role: array{role: 'business'|'auditor', status: 'draft'|'submitted'|'changes_requested'|'approved'|'rejected', company: ?string,
 *         submitted_at: ?string, decided_at: ?string, note: ?string, items: list<array{key: string, label: string, note: string}>, requested_at: ?string}|null,
 *     roles: list<string>}
 */
function hubFacts(array $kyc = [], ?array $role = null, array $roles = ['investor'], ?string $twoFactor = null, ?string $email = '2026-11-20T09:00:00+02:00'): array
{
    return [
        'name' => '  Aline   Uwase ', 'created_at' => '2026-11-20T08:00:00+02:00', 'email_verified_at' => $email, 'two_factor_at' => $twoFactor,
        'kyc' => ['status' => $kyc['status'] ?? 'none', 'submitted_at' => $kyc['submitted_at'] ?? null, 'approved_at' => $kyc['approved_at'] ?? null,
            'reason' => $kyc['reason'] ?? null, 'items' => [], 'requested_at' => $kyc['requested_at'] ?? null],
        'role' => $role === null ? null : ['role' => $role['role'] ?? 'business', 'status' => $role['status'] ?? 'draft', 'company' => null,
            'submitted_at' => $role['submitted_at'] ?? null, 'decided_at' => null, 'note' => $role['note'] ?? null, 'items' => $role['items'] ?? [],
            'requested_at' => $role['requested_at'] ?? null],
        'roles' => $roles,
    ];
}

it('starts a new person at the identity check with an unverified e-mail', function (): void {
    $hub = OnboardingHub::build(hubFacts(email: null));

    expect($hub['name'])->toBe('Aline')
        ->and(array_column($hub['steps'], 'state', 'key'))->toBe(['account' => 'done', 'email' => 'current', 'kyc' => 'current', 'open' => 'upcoming'])
        ->and(array_column($hub['actions'], 'kind'))->toBe(['kyc_start', 'two_factor'])->and($hub['next'])->toBe(['kyc_send'])
        ->and(array_column($hub['done'], 'key'))->toBe(['account'])->and($hub['role'])->toBeNull()->and($hub['recent'])->toBe([]);
});

it('asks a person with a draft check and a draft application to finish the check first', function (): void {
    $hub = OnboardingHub::build(hubFacts(['status' => 'draft'], ['role' => 'auditor'], twoFactor: '2026-11-20T10:00:00+02:00'));

    expect(array_column($hub['actions'], 'kind'))->toBe(['kyc_continue'])
        ->and($hub['steps'][3])->toBe(['key' => 'role', 'state' => 'upcoming', 'role' => 'auditor', 'at' => null])
        ->and($hub['steps'][4])->toBe(['key' => 'open', 'state' => 'upcoming', 'role' => 'auditor', 'at' => null])
        ->and($hub['next'])->toBe(['kyc_send', 'role_send', 'role_switch']);
});

it('asks to finish an application once the check is sent', function (): void {
    $hub = OnboardingHub::build(hubFacts(['status' => 'submitted', 'submitted_at' => '2026-11-21T09:00:00+02:00'], ['role' => 'business'], twoFactor: 'x'));

    expect(array_column($hub['actions'], 'kind'))->toBe(['role_continue'])->and($hub['steps'][3]['state'])->toBe('current')
        ->and($hub['steps'][2]['at'])->toBe('2026-11-21T09:00:00+02:00');
});

it('raises what Compliance sent back as urgent, with a blank note left out', function (): void {
    $items = [['key' => 'company.tin', 'label' => 'TIN', 'note' => '']];
    $hub = OnboardingHub::build(hubFacts(['status' => 'rejected', 'reason' => 'Blurred.', 'requested_at' => 'r'],
        ['status' => 'changes_requested', 'note' => '  ', 'items' => $items, 'requested_at' => 'q', 'submitted_at' => 's'], twoFactor: 'x'));

    expect($hub['actions'])->toBe([
        ['id' => 'kyc_changes@r', 'kind' => 'kyc_changes', 'urgent' => true, 'role' => null, 'company' => null, 'note' => 'Blurred.', 'items' => [], 'at' => 'r'],
        ['id' => 'role_changes@q', 'kind' => 'role_changes', 'urgent' => true, 'role' => 'business', 'company' => null, 'note' => null, 'items' => $items, 'at' => 'q'],
    ])->and($hub['steps'][2]['at'])->toBe('r')->and($hub['next'])->toBe(['kyc_resend', 'role_resend', 'role_switch'])
        ->and(array_column($hub['done'], 'key'))->toBe(['account', 'email', 'two_factor', 'role_sent']);
});

it('opens the role a person already holds, and says an Auditor or Investor is ready', function (string $role, string $next): void {
    $hub = OnboardingHub::build(hubFacts(['status' => 'approved', 'approved_at' => 'a', 'submitted_at' => 's'], null, [$role], 'x'));

    expect($hub['steps'][3])->toBe(['key' => 'open', 'state' => 'done', 'role' => $role === 'investor' ? 'investor' : $role, 'at' => null])
        ->and($hub['role'])->toBe($role === 'investor' ? null : $role)->and($hub['next'])->toBe([$next])
        ->and(array_column($hub['done'], 'key'))->toBe(['account', 'email', 'two_factor', 'kyc_sent', 'kyc_approved']);
})->with([
    'auditor' => ['auditor', 'auditor_ready'],
    'business' => ['business', 'business_ready'],
    'investor' => ['investor', 'investor_ready'],
]);

it('keeps the open step waiting while an approved role still has an open application', function (): void {
    $hub = OnboardingHub::build(hubFacts(['status' => 'approved'], ['status' => 'submitted', 'submitted_at' => 's'], ['business'], 'x'));

    expect($hub['steps'][3]['at'])->toBe('s')->and($hub['steps'][4]['state'])->toBe('upcoming')->and($hub['next'])->toBe(['role_review', 'role_switch']);
});

it('names a person with no name plainly', function (): void {
    $facts = hubFacts();
    $facts['name'] = '   ';

    expect(OnboardingHub::build($facts)['name'])->toBe('');
});

it('picks the items to revise from the case, each once, with a short clean note', function (): void {
    $allowed = ['selfie' => 'Selfie', 'id_front' => 'ID front'];

    expect(RevisionItems::pick(null, $allowed))->toBe([])->and(RevisionItems::pick([], $allowed))->toBe([])
        ->and(RevisionItems::pick([['key' => 'selfie', 'note' => ' Too dark '], ['key' => 'id_front']], $allowed))
        ->toBe([['key' => 'selfie', 'label' => 'Selfie', 'note' => 'Too dark'], ['key' => 'id_front', 'label' => 'ID front', 'note' => '']])
        ->and(RevisionItems::summary([['key' => 'selfie', 'label' => 'Selfie', 'note' => ''], ['key' => 'id_front', 'label' => 'ID front', 'note' => '']]))
        ->toBe('Please change: Selfie, ID front.');

    foreach (['not a list', ['key' => 'selfie'], [['key' => 'passport']], [['key' => 'selfie'], ['key' => 'selfie']], [['key' => 'selfie', 'note' => 3]], ['selfie'],
        array_fill(0, RevisionItems::MAX_ITEMS + 1, ['key' => 'selfie'])] as $input) {
        expect(fn () => RevisionItems::pick($input, $allowed))->toThrow(CommandRejection::class);
    }
    foreach ([str_repeat('a', RevisionItems::MAX_NOTE + 1), "bad\u{200B}note"] as $note) {
        expect(fn () => RevisionItems::pick([['key' => 'selfie', 'note' => $note]], $allowed))->toThrow(CommandRejection::class, 'REVISION_NOTE_INVALID');
    }
    expect(RevisionItems::pick([['key' => 'selfie', 'note' => "Line one\nline two"]], $allowed)[0]['note'])->toBe("Line one\nline two");
});
