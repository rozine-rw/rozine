<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\UserDirectoryStore;
use App\Models\StaffAccount;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * @phpstan-import-type Directory from UserDirectoryStore
 * @phpstan-import-type DirectoryRow from UserDirectoryStore
 * @phpstan-import-type Detail from UserDirectoryStore
 */
final class EloquentUserDirectoryStore implements UserDirectoryStore
{
    /** How many identity audit events the detail lists, newest first. */
    private const HISTORY = 25;

    /** @return Directory */
    public function directory(string $role, string $kyc, string $sort, string $search, int $limit, bool $closed = false): array
    {
        $everyone = DB::query()->fromSub($this->accounts(), 'accounts')->whereNull('closed_at');
        $closedCount = DB::table('users')->whereNotNull('closed_at')->count();
        $platform = (clone $everyone)->selectRaw("count(*) AS everyone, count(*) FILTER (WHERE signed_up_at >= ?) AS new_this_week,
            count(*) FILTER (WHERE kyc = 'verified') AS verified, count(*) FILTER (WHERE kyc = 'in_review') AS awaiting", [now()->subDays(7)])->first();

        $accounts = $closed ? DB::query()->fromSub($this->accounts(), 'accounts')->whereNotNull('closed_at') : clone $everyone;
        if ($search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $accounts->where(fn (Builder $match) => $match->whereLike('name', $term)->orWhereLike('email', $term));
        }
        if ($kyc !== 'any') {
            $accounts->where('kyc', $kyc);
        }
        $totals = (clone $accounts)->selectRaw('count(*) AS everyone, count(*) FILTER (WHERE is_investor) AS investor, count(*) FILTER (WHERE is_business) AS business,
            count(*) FILTER (WHERE is_auditor) AS auditor, count(*) FILTER (WHERE is_staff) AS staff,
            count(*) FILTER (WHERE NOT (is_investor OR is_business OR is_auditor OR is_staff)) AS no_role')->first();
        match ($role) {
            'investor', 'business', 'auditor', 'staff' => $accounts->where('is_'.$role, true),
            'no_role' => $accounts->whereNot(fn (Builder $any) => $any->where('is_investor', true)->orWhere('is_business', true)
                ->orWhere('is_auditor', true)->orWhere('is_staff', true)),
            default => null,
        };
        $matching = (clone $accounts)->count();
        match ($sort) {
            'name' => $accounts->orderByRaw('lower(name)')->orderBy('id'),
            'oldest' => $accounts->orderBy('signed_up_at')->orderBy('id'),
            default => $closed ? $accounts->orderByDesc('closed_at')->orderByDesc('id') : $accounts->orderByDesc('signed_up_at')->orderByDesc('id'),
        };

        return ['rows' => $this->rows(array_values($accounts->limit($limit)->get()->all())), 'matching' => $matching,
            'counts' => ['all' => (int) ($totals->everyone ?? 0), 'investor' => (int) ($totals->investor ?? 0), 'business' => (int) ($totals->business ?? 0),
                'auditor' => (int) ($totals->auditor ?? 0), 'staff' => (int) ($totals->staff ?? 0), 'no_role' => (int) ($totals->no_role ?? 0)],
            'everyone' => (int) ($platform->everyone ?? 0), 'new_this_week' => (int) ($platform->new_this_week ?? 0), 'kyc_verified' => (int) ($platform->verified ?? 0),
            'awaiting_review' => (int) ($platform->awaiting ?? 0), 'closed' => $closedCount];
    }

    /** @return Detail|null */
    public function user(int $userId): ?array
    {
        $record = DB::query()->fromSub($this->accounts(), 'accounts')->where('id', $userId)->first();
        if ($record === null) {
            return null;
        }
        $row = $this->rows([$record])[0];
        $account = DB::table('users')->where('id', $userId)->first(['email_verified_at', 'two_factor_confirmed_at']);
        $partyId = $row['party_id'];
        $requests = $partyId === null ? collect() : DB::table('role_access_requests')->where('party_id', $partyId)->orderByDesc('created_at')
            ->get(['role', 'organization', 'reference', 'created_at']);
        $membershipIds = $partyId === null ? [] : DB::table('role_memberships')->where('party_id', $partyId)->pluck('id')->map(fn (mixed $id): string => (string) $id)->all();
        $history = DB::table('identity_audit_events AS event')->leftJoin('users AS actor', 'actor.id', '=', 'event.actor_user_id')
            ->where(function (Builder $about) use ($userId, $partyId, $membershipIds): void {
                $about->where(fn (Builder $user) => $user->where('event.target_type', 'user')->where('event.target_id', (string) $userId));
                if ($membershipIds !== []) {
                    $about->orWhere(fn (Builder $membership) => $membership->where('event.target_type', 'membership')->whereIn('event.target_id', $membershipIds));
                }
                if ($partyId !== null) {
                    $about->orWhere(fn (Builder $party) => $party->where('event.target_type', 'party')->where('event.target_id', $partyId));
                }
            })
            ->orderByDesc('event.created_at')->orderByDesc('event.id')->limit(self::HISTORY)
            ->get(['event.id', 'event.created_at', 'event.action', 'event.reason', 'actor.name']);

        $asked = [];
        foreach ($requests as $request) {
            $asked[] = ['role' => $request->role === 'business' ? 'business' : 'auditor', 'organization' => (string) $request->organization,
                'reference' => $request->reference === null ? null : (string) $request->reference, 'sent_at' => (string) self::time($request->created_at)];
        }

        return ['row' => $row,
            'email_verified_at' => self::time($account?->email_verified_at), 'two_factor' => $account?->two_factor_confirmed_at !== null,
            'passkeys' => DB::table('passkeys')->where('user_id', $userId)->count(),
            'verification_id' => $record->verification_id === null ? null : (string) $record->verification_id,
            'submitted_at' => self::time($record->submitted_at),
            'requests' => $asked,
            'history' => array_values($history->map(fn (stdClass $event): array => ['id' => (string) $event->id, 'at' => (string) self::time($event->created_at),
                'actor' => (string) ($event->name ?? ''), 'action' => (string) $event->action, 'reason' => (string) $event->reason])->all())];
    }

    /**
     * Every account, one row each, with the flags the chips filter on. A role counts while its
     * membership is not revoked; a staff account counts whether or not it is enabled.
     */
    private function accounts(): Builder
    {
        return DB::table('users')->leftJoin('parties', 'parties.id', '=', 'users.party_id')
            ->leftJoin('investor_verifications AS iv', 'iv.party_id', '=', 'users.party_id')
            ->leftJoin('staff_accounts', 'staff_accounts.user_id', '=', 'users.id')
            ->selectRaw("users.id, users.party_id, users.name, users.email, users.email_verified_at IS NOT NULL AS email_verified,
                users.created_at AS signed_up_at, users.closed_at, users.closure_reason, iv.id AS verification_id, iv.submitted_at,
                (SELECT max(s.last_activity) FROM sessions s WHERE s.user_id = users.id) AS last_activity,
                CASE WHEN parties.verified_at IS NOT NULL OR iv.status = 'approved' THEN 'verified' WHEN iv.status = 'submitted' THEN 'in_review'
                    WHEN iv.status = 'rejected' THEN 'rejected' WHEN iv.id IS NOT NULL THEN 'in_progress' ELSE 'not_started' END AS kyc,
                EXISTS (SELECT 1 FROM role_memberships m WHERE m.party_id = users.party_id AND m.role = 'investor' AND m.status <> 'revoked') AS is_investor, EXISTS (SELECT 1 FROM role_memberships m WHERE m.party_id = users.party_id AND m.role = 'business' AND m.status <> 'revoked') AS is_business, EXISTS (SELECT 1 FROM role_memberships m WHERE m.party_id = users.party_id AND m.role = 'auditor' AND m.status <> 'revoked') AS is_auditor,
                staff_accounts.user_id IS NOT NULL AS is_staff, coalesce(staff_accounts.enabled, false) AS staff_enabled,
                EXISTS (SELECT 1 FROM investor_account_restrictions r WHERE r.party_id = users.party_id AND r.effective_at <= now()
                    AND (r.expires_at IS NULL OR r.expires_at > now())) AS restricted");
    }

    /**
     * @param  list<stdClass>  $records
     * @return list<DirectoryRow>
     */
    private function rows(array $records): array
    {
        $parties = array_values(array_filter(array_map(fn (stdClass $record): ?string => $record->party_id === null ? null : (string) $record->party_id, $records)));
        $memberships = DB::table('role_memberships')->whereIn('party_id', $parties)->orderBy('created_at')->orderBy('role')
            ->get(['party_id', 'role', 'status', 'created_at'])->groupBy('party_id');
        $requests = DB::table('role_access_requests')->whereIn('party_id', $parties)->orderBy('created_at')->get(['party_id', 'role'])->groupBy('party_id');
        $staffIds = array_map(fn (stdClass $record): int => (int) $record->id, array_filter($records, fn (stdClass $record): bool => (bool) $record->is_staff));
        $staff = StaffAccount::query()->whereKey($staffIds)->with('roles')->get()->keyBy('user_id');

        return array_map(function (stdClass $record) use ($memberships, $requests, $staff): array {
            $partyId = $record->party_id === null ? null : (string) $record->party_id;
            $held = [];
            foreach ($partyId === null ? [] : $memberships->get($partyId, collect())->all() as $membership) {
                $held[] = ['role' => self::role((string) $membership->role), 'status' => self::status((string) $membership->status),
                    'since' => (string) self::time($membership->created_at)];
            }
            $holding = array_column($held, 'role');
            $asked = $partyId === null ? [] : $requests->get($partyId, collect())->pluck('role')->all();
            $requested = [];
            foreach (['business', 'auditor'] as $role) {
                if (in_array($role, $asked, true) && ! in_array($role, $holding, true)) {
                    $requested[] = $role;
                }
            }
            $account = $staff->get((int) $record->id);
            $staffRoles = $account === null ? [] : array_values(array_map(fn (mixed $name): string => (string) $name, $account->roles->pluck('name')->sort()->all()));

            return ['id' => (int) $record->id, 'party_id' => $partyId, 'name' => (string) $record->name, 'email' => (string) $record->email,
                'email_verified' => (bool) $record->email_verified, 'signed_up_at' => (string) self::time($record->signed_up_at),
                'last_seen_at' => $record->last_activity === null ? null : Carbon::createFromTimestamp((int) $record->last_activity)->toIso8601String(),
                'memberships' => $held, 'staff' => $account === null ? null : ['roles' => $staffRoles, 'enabled' => (bool) $record->staff_enabled],
                'requested' => $requested, 'kyc' => self::kyc((string) $record->kyc), 'restricted' => (bool) $record->restricted,
                'closed_at' => self::time($record->closed_at), 'closure_reason' => $record->closure_reason === null ? null : (string) $record->closure_reason];
        }, $records);
    }

    /**
     * The membership table only holds these three roles.
     *
     * @return 'investor'|'business'|'auditor'
     */
    private static function role(string $role): string
    {
        return match ($role) {
            'investor' => 'investor',
            'business' => 'business',
            default => 'auditor',
        };
    }

    /** @return 'pending'|'active'|'suspended'|'revoked' */
    private static function status(string $status): string
    {
        return match ($status) {
            'active' => 'active',
            'suspended' => 'suspended',
            'revoked' => 'revoked',
            default => 'pending',
        };
    }

    /** @return 'verified'|'in_review'|'in_progress'|'rejected'|'not_started' */
    private static function kyc(string $state): string
    {
        return match ($state) {
            'verified' => 'verified',
            'in_review' => 'in_review',
            'in_progress' => 'in_progress',
            'rejected' => 'rejected',
            default => 'not_started',
        };
    }

    private static function time(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse((string) $value)->toIso8601String();
    }
}
