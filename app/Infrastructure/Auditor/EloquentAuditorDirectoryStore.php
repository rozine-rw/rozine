<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditorDirectoryStore;
use App\Models\AuditorProfile;
use App\Models\AuditorProfileVersion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Accreditation state is encrypted at rest, so standing is read per profile here rather than in SQL.
 * The network is a few dozen partners, so the whole match is read and paged in memory.
 *
 * @phpstan-import-type State from \App\Domain\Auditor\AccreditationProfile
 * @phpstan-import-type Directory from AuditorDirectoryStore
 * @phpstan-import-type DirectoryRow from AuditorDirectoryStore
 * @phpstan-import-type Detail from AuditorDirectoryStore
 * @phpstan-import-type Licence from AuditorDirectoryStore
 * @phpstan-import-type HistoryEntry from AuditorDirectoryStore
 */
final class EloquentAuditorDirectoryStore implements AuditorDirectoryStore
{
    /** @return Directory */
    public function directory(string $chip, string $search, int $limit): array
    {
        $partners = DB::query()->fromSub($this->partners(), 'partners');
        if ($search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $partners->where(fn (Builder $match) => $match->whereLike('name', $term)->orWhereLike('email', $term));
        }
        $records = $partners->orderBy('name')->orderBy('party_id')->get()->all();
        $profiles = AuditorProfile::query()->whereIn('party_id', array_map(fn (object $record): string => (string) $record->party_id, $records))
            ->get()->keyBy('party_id');
        $soon = now('Africa/Kigali')->addDays(30)->format('Y-m-d');
        $rows = [];
        $expiring = 0;
        foreach ($records as $record) {
            $state = $profiles->get((string) $record->party_id)?->state;
            $rows[] = $row = self::row($record, $state);
            if ($row['standing'] === 'active' && (string) $state['standing']['expires_on'] <= $soon) {
                $expiring++;
            }
        }
        $counts = ['all' => count($rows)];
        foreach (['active', 'pending', 'licence_expired', 'suspended'] as $standing) {
            $counts[$standing] = count(array_filter($rows, fn (array $row): bool => $row['standing'] === $standing));
        }
        $matches = $chip === 'all' ? $rows : array_values(array_filter($rows, fn (array $row): bool => $row['standing'] === $chip));

        return ['rows' => array_slice($matches, 0, $limit), 'matching' => count($matches), 'counts' => $counts, 'licences_expiring' => $expiring];
    }

    /** @return Detail|null */
    public function partner(string $partyId): ?array
    {
        $record = DB::query()->fromSub($this->partners(), 'partners')->where('party_id', $partyId)->first();
        if ($record === null) {
            return null;
        }
        $profile = AuditorProfile::query()->where('party_id', $partyId)->first();
        $engagements = DB::table('audit_assignments')->join('business_profiles', 'business_profiles.id', '=', 'audit_assignments.business_id')
            ->where('audit_assignments.party_id', $partyId)->where('audit_assignments.status', 'accepted')
            ->orderByDesc('audit_assignments.updated_at')->orderBy('audit_assignments.id')
            ->get(['audit_assignments.id', DB::raw("business_profiles.profile->>'name' AS business"), DB::raw("business_profiles.profile->>'district' AS district")]);

        return ['row' => self::row($record, $profile?->state), 'licence' => $profile === null ? null : self::licence($profile),
            'engagements' => array_values($engagements->map(fn (object $engagement): array => ['id' => (string) $engagement->id,
                'business' => (string) $engagement->business, 'district' => (string) $engagement->district])->all()),
            'history' => $profile === null ? [] : self::history($profile)];
    }

    /** Every Party with an Auditor membership or an accreditation profile, one row each, with its active engagements. */
    private function partners(): Builder
    {
        return DB::table('parties')
            ->where(fn (Builder $partner) => $partner->whereExists(fn (Builder $membership) => $membership->selectRaw('1')->from('role_memberships')
                ->whereColumn('role_memberships.party_id', 'parties.id')->where('role_memberships.role', 'auditor'))
                ->orWhereExists(fn (Builder $profile) => $profile->selectRaw('1')->from('auditor_profiles')->whereColumn('auditor_profiles.party_id', 'parties.id')))
            ->selectRaw("parties.id AS party_id,
                coalesce((SELECT u.name FROM users u WHERE u.party_id = parties.id ORDER BY u.id LIMIT 1), '') AS name,
                coalesce((SELECT u.email FROM users u WHERE u.party_id = parties.id ORDER BY u.id LIMIT 1), '') AS email,
                (SELECT count(*) FROM audit_assignments a WHERE a.party_id = parties.id AND a.status = 'accepted') AS active_engagements");
    }

    /**
     * @param  State|null  $state
     * @return DirectoryRow
     */
    private static function row(object $record, ?array $state): array
    {
        $record = (array) $record;
        $standing = $state['standing'] ?? ['status' => 'none', 'licence' => null, 'expires_on' => null];
        $submission = $state['submission'] ?? ['status' => 'none'];

        return ['party_id' => (string) $record['party_id'], 'name' => (string) $record['name'], 'email' => (string) $record['email'],
            'licence' => $standing['licence'] ?? ($submission['status'] === 'pending' ? $submission['licence'] : null),
            'standing' => match (true) {
                in_array($standing['status'], ['suspended', 'revoked'], true) => 'suspended',
                $standing['status'] === 'active' && (string) $standing['expires_on'] < self::today() => 'licence_expired',
                $standing['status'] === 'active' => 'active',
                default => 'pending',
            },
            'active_engagements' => (int) $record['active_engagements']];
    }

    /**
     * A submission waiting for review is what staff verify; otherwise the reviewed licence shows.
     *
     * @return Licence|null
     */
    private static function licence(AuditorProfile $profile): ?array
    {
        $state = $profile->state;
        $submission = $state['submission'];
        if ($submission['status'] === 'pending') {
            return ['licence' => $submission['licence'], 'expires_on' => $submission['expires_on'], 'state' => 'pending',
                'revision' => $profile->revision, 'submission_id' => $submission['id']];
        }
        $standing = $state['standing'];
        if ($standing['licence'] === null || $standing['expires_on'] === null) {
            return null;
        }

        return ['licence' => $standing['licence'], 'expires_on' => $standing['expires_on'],
            'state' => $standing['expires_on'] < self::today() ? 'expired' : 'verified', 'revision' => $profile->revision, 'submission_id' => null];
    }

    /**
     * The accreditation history, newest first. A review reads by what it changed: a decided
     * submission was approved or rejected, and otherwise the standing it left was confirmed,
     * suspended or revoked.
     *
     * @return list<HistoryEntry>
     */
    private static function history(AuditorProfile $profile): array
    {
        $versions = AuditorProfileVersion::query()->where('auditor_profile_id', $profile->id)->orderBy('revision')->get();
        $actors = DB::table('users')->whereIn('id', $versions->pluck('actor_user_id')->all())->pluck('name', 'id');
        $history = [];
        $before = null;
        foreach ($versions as $version) {
            $after = $version->snapshot['state'];
            $history[] = ['id' => $version->id, 'at' => CarbonImmutable::parse($version->getRawOriginal('created_at'))->utc()->toIso8601String(),
                'actor' => (string) ($actors[$version->actor_user_id] ?? ''), 'reason' => $version->reason,
                'event' => self::event($version->command, $before, $after)];
            $before = $after;
        }

        return array_reverse($history);
    }

    /**
     * @param  State|null  $before
     * @param  State  $after
     * @return 'submitted'|'renewal_submitted'|'withdrawn'|'availability'|'approved'|'rejected'|'suspended'|'revoked'
     */
    private static function event(string $command, ?array $before, array $after): string
    {
        $decided = ($before['submission']['status'] ?? null) === 'pending' && $after['submission']['status'] !== 'pending';

        return match ($command) {
            'accreditation.submit' => 'submitted',
            'accreditation.renew' => 'renewal_submitted',
            'accreditation.withdraw' => 'withdrawn',
            'availability.update' => 'availability',
            default => match (true) {
                $decided && $after['submission']['status'] === 'rejected' => 'rejected',
                $decided, $after['standing']['status'] === 'active' => 'approved',
                $after['standing']['status'] === 'revoked' => 'revoked',
                default => 'suspended',
            },
        };
    }

    /** Licences lapse by the calendar in Kigali, as the accreditation rules read them. */
    private static function today(): string
    {
        return now('Africa/Kigali')->format('Y-m-d');
    }
}
