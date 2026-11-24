<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * The dashboard as the person's progress hub (Erastus, 24 Nov 2026, design "Dashboard hub"): the setup
 * steps and where each stands, what needs them now (urgent when Compliance sent something back), what
 * happens next and what is already done. Built from facts the store reads, so the rules live here.
 *
 * @phpstan-type Item array{key: string, label: string, note: string}
 * @phpstan-type Kyc array{status: 'none'|'draft'|'submitted'|'rejected'|'approved', submitted_at: ?string, approved_at: ?string,
 *     reason: ?string, items: list<Item>, requested_at: ?string}
 * @phpstan-type Role array{role: 'business'|'auditor', status: 'draft'|'submitted'|'changes_requested'|'approved'|'rejected',
 *     company: ?string, submitted_at: ?string, decided_at: ?string, note: ?string, items: list<Item>, requested_at: ?string}
 * @phpstan-type Facts array{name: string, created_at: ?string, email_verified_at: ?string, two_factor_at: ?string,
 *     kyc: Kyc, role: ?Role, roles: list<string>}
 * @phpstan-type Step array{key: 'account'|'email'|'kyc'|'role'|'open', state: 'done'|'attention'|'rejected'|'review'|'current'|'upcoming', role: ?string, at: ?string}
 * @phpstan-type Action array{id: string, kind: 'kyc_changes'|'role_changes'|'role_rejected'|'kyc_start'|'kyc_continue'|'role_continue'|'two_factor',
 *     urgent: bool, role: ?string, company: ?string, note: ?string, items: list<Item>, at: ?string}
 * @phpstan-type Done array{key: 'account'|'email'|'two_factor'|'kyc_sent'|'kyc_approved'|'role_sent'|'role_approved', role: ?string, at: ?string}
 * @phpstan-type Hub array{name: string, steps: list<Step>, actions: list<Action>, next: list<string>, done: list<Done>, role: ?string, company: ?string,
 *     recent: list<array<string, mixed>>}
 */
final class OnboardingHub
{
    /**
     * @param  Facts  $facts
     * @param  list<array<string, mixed>>  $recent  the person's newest onboarding notices
     * @return Hub
     */
    public static function build(array $facts, array $recent = []): array
    {
        $kyc = $facts['kyc'];
        $role = $facts['role'];
        $target = $role['role'] ?? self::heldRole($facts['roles']);

        return [
            'name' => self::firstName($facts['name']),
            'steps' => self::steps($facts, $target),
            'actions' => self::actions($facts),
            'next' => self::next($kyc['status'], $role, $target),
            'done' => self::done($facts),
            'role' => $target,
            'company' => $role['company'] ?? null,
            'recent' => $recent,
        ];
    }

    /**
     * @param  Facts  $facts
     * @return list<Step>
     */
    private static function steps(array $facts, ?string $target): array
    {
        $kyc = $facts['kyc'];
        $role = $facts['role'];
        $steps = [
            ['key' => 'account', 'state' => 'done', 'role' => null, 'at' => $facts['created_at']],
            ['key' => 'email', 'state' => $facts['email_verified_at'] === null ? 'current' : 'done', 'role' => null, 'at' => $facts['email_verified_at']],
            ['key' => 'kyc', 'state' => match ($kyc['status']) {
                'approved' => 'done', 'submitted' => 'review', 'rejected' => 'attention', default => 'current',
            }, 'role' => null, 'at' => $kyc['status'] === 'approved' ? $kyc['approved_at'] : ($kyc['status'] === 'submitted' ? $kyc['submitted_at'] : $kyc['requested_at'])],
        ];
        if ($role !== null) {
            $steps[] = ['key' => 'role', 'state' => match ($role['status']) {
                'approved' => 'done', 'submitted' => 'review', 'changes_requested' => 'attention', 'rejected' => 'rejected',
                default => $kyc['status'] === 'none' || $kyc['status'] === 'draft' ? 'upcoming' : 'current',
            }, 'role' => $role['role'], 'at' => $role['status'] === 'submitted' ? $role['submitted_at'] : $role['decided_at']];
        }
        $open = $target ?? 'investor';
        // Every account starts with Investor access, but investing opens only once the person is verified.
        $ready = $role === null ? $open !== 'investor' || $kyc['status'] === 'approved' : $role['status'] === 'approved';
        $steps[] = ['key' => 'open', 'state' => in_array($open, $facts['roles'], true) && $ready ? 'done' : 'upcoming',
            'role' => $open, 'at' => null];

        return $steps;
    }

    /**
     * Urgent items first: anything Compliance sent back, then what the person has still to send, then
     * what is only recommended.
     *
     * @param  Facts  $facts
     * @return list<Action>
     */
    private static function actions(array $facts): array
    {
        $kyc = $facts['kyc'];
        $role = $facts['role'];
        $actions = [];
        if ($kyc['status'] === 'rejected') {
            $actions[] = self::action('kyc_changes', true, null, null, $kyc['reason'], $kyc['items'], $kyc['requested_at']);
        }
        if ($role !== null && $role['status'] === 'changes_requested') {
            $actions[] = self::action('role_changes', true, $role['role'], $role['company'], $role['note'], $role['items'], $role['requested_at']);
        }
        if ($role !== null && $role['status'] === 'rejected') {
            $actions[] = self::action('role_rejected', false, $role['role'], $role['company'], $role['note'], [], $role['decided_at']);
        }
        if ($kyc['status'] === 'none' || $kyc['status'] === 'draft') {
            $actions[] = self::action($kyc['status'] === 'none' ? 'kyc_start' : 'kyc_continue', false, null, null, null, [], null);
        }
        if ($role !== null && $role['status'] === 'draft' && $kyc['status'] !== 'none' && $kyc['status'] !== 'draft') {
            $actions[] = self::action('role_continue', false, $role['role'], $role['company'], null, [], null);
        }
        if ($facts['two_factor_at'] === null) {
            $actions[] = self::action('two_factor', false, $role['role'] ?? null, null, null, [], null);
        }

        return $actions;
    }

    /**
     * @param  'kyc_changes'|'role_changes'|'role_rejected'|'kyc_start'|'kyc_continue'|'role_continue'|'two_factor'  $kind
     * @param  list<Item>  $items
     * @return Action
     */
    private static function action(string $kind, bool $urgent, ?string $role, ?string $company, ?string $note, array $items, ?string $at): array
    {
        return ['id' => $kind.'@'.($at ?? ''), 'kind' => $kind, 'urgent' => $urgent, 'role' => $role, 'company' => $company,
            'note' => $note === null || trim($note) === '' ? null : $note, 'items' => $items, 'at' => $at];
    }

    /**
     * What happens next, in order, as keys the page words.
     *
     * @param  ?Role  $role
     * @return list<string>
     */
    private static function next(string $kyc, ?array $role, ?string $target): array
    {
        $next = [];
        if ($kyc !== 'approved') {
            $next[] = $kyc === 'submitted' ? 'kyc_review' : ($kyc === 'rejected' ? 'kyc_resend' : 'kyc_send');
        }
        if ($role !== null && $role['status'] !== 'approved') {
            $next[] = match ($role['status']) {
                'submitted' => 'role_review', 'changes_requested' => 'role_resend', 'rejected' => 'role_reapply', default => 'role_send',
            };
            $next[] = 'role_switch';
        }
        if ($next === []) {
            $next[] = $target === 'business' ? 'business_ready' : ($target === 'auditor' ? 'auditor_ready' : 'investor_ready');
        }

        return $next;
    }

    /**
     * @param  Facts  $facts
     * @return list<Done>
     */
    private static function done(array $facts): array
    {
        $kyc = $facts['kyc'];
        $role = $facts['role'];
        $done = [['key' => 'account', 'role' => null, 'at' => $facts['created_at']]];
        if ($facts['email_verified_at'] !== null) {
            $done[] = ['key' => 'email', 'role' => null, 'at' => $facts['email_verified_at']];
        }
        if ($facts['two_factor_at'] !== null) {
            $done[] = ['key' => 'two_factor', 'role' => null, 'at' => $facts['two_factor_at']];
        }
        if ($kyc['submitted_at'] !== null && $kyc['status'] !== 'draft') {
            $done[] = ['key' => 'kyc_sent', 'role' => null, 'at' => $kyc['submitted_at']];
        }
        if ($kyc['status'] === 'approved') {
            $done[] = ['key' => 'kyc_approved', 'role' => null, 'at' => $kyc['approved_at']];
        }
        if ($role !== null && $role['submitted_at'] !== null && $role['status'] !== 'draft') {
            $done[] = ['key' => 'role_sent', 'role' => $role['role'], 'at' => $role['submitted_at']];
        }
        if ($role !== null && $role['status'] === 'approved') {
            $done[] = ['key' => 'role_approved', 'role' => $role['role'], 'at' => $role['decided_at']];
        }

        return $done;
    }

    /** @param list<string> $roles */
    private static function heldRole(array $roles): ?string
    {
        foreach (['business', 'auditor'] as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return null;
    }

    private static function firstName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return $parts[0] ?? '';
    }
}
