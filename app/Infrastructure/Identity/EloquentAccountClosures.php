<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\AccountClosures;
use App\Application\Wallet\Contracts\NoteRepayments;
use App\Domain\Identity\ActiveRolePolicy;
use App\Models\IdentityAuditEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reads each role's open obligations straight from the records the role's own pages read, and
 * closes the account by stamping `users.closed_at` (a soft delete): every record stays, the
 * account's sessions and API tokens end, and an immutable identity audit event names the closure.
 * Closing also frees the sign-in email and the verified ID (both kept on record), so the person
 * can sign up and be verified again with a new account.
 *
 * @phpstan-import-type Blocker from AccountClosures
 */
final class EloquentAccountClosures implements AccountClosures
{
    /** The wallet buckets that hold the investor's money, including cash on its way out. */
    /** The placeholder sign-in email a closed account keeps, so its own address can register again. */
    public const RELEASED_EMAIL = 'closed-account-%d@closed.rozine.invalid';

    private const INVESTOR_BUCKETS = ['investor_available', 'investor_held', 'investor_committed', 'investor_withdrawing'];

    public function __construct(private NoteRepayments $notes) {}

    public function standing(int $userId): array
    {
        $user = User::query()->findOrFail($userId);

        return ['roles' => $this->roles($user), 'blockers' => $this->blockers($user)];
    }

    public function investorBlockers(int $userId): array
    {
        $party = User::query()->whereKey($userId)->value('party_id');

        return is_string($party) ? $this->investor($party) : [];
    }

    public function close(int $userId, ?string $reason): array
    {
        return DB::transaction(function () use ($userId, $reason): array {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            if ($user->isClosed()) {
                return [];
            }
            $blockers = $this->blockers($user);
            if ($blockers !== []) {
                return $blockers;
            }

            $closedAt = now('UTC');
            $roles = $this->roles($user);
            User::query()->whereKey($userId)->update([
                'closed_at' => $closedAt, 'closure_reason' => $reason,
                'closed_email' => $user->email, 'email' => sprintf(self::RELEASED_EMAIL, $userId),
            ]);
            DB::table('sessions')->where('user_id', $userId)->delete();
            $user->tokens()->delete();
            $identityReleased = $this->releaseIdentity($user, $closedAt);

            $after = ['closed_at' => $closedAt->toIso8601String(), 'roles' => $roles, 'email_released' => true, 'identity_released' => $identityReleased];
            $requestId = (string) Str::uuid();
            (new IdentityAuditEvent)->forceFill([
                'id' => strtolower((string) Str::ulid()), 'actor_key' => 'user:'.$userId, 'actor_user_id' => $userId,
                'target_type' => 'user', 'target_id' => (string) $userId, 'action' => 'account.close',
                'reason' => $reason ?? 'Closed by the account holder', 'request_id' => $requestId,
                'request_hash' => hash('sha256', json_encode([$userId, $after], JSON_THROW_ON_ERROR)),
                'before' => ['closed_at' => null], 'after' => $after, 'result' => ['status' => 'closed'],
                'policy_version' => ActiveRolePolicy::POLICY_VERSION,
            ])->save();

            return [];
        });
    }

    /**
     * Moves the person's verified ID to the released record when no other open account shares the
     * person, so the same ID can be verified for a new account. Nothing is deleted from the record.
     */
    private function releaseIdentity(User $user, CarbonInterface $closedAt): bool
    {
        if ($user->party_id === null || User::query()->where('party_id', $user->party_id)
            ->whereKeyNot($user->id)->whereNull('closed_at')->exists()) {
            return false;
        }
        $identity = DB::table('verified_person_identities')->where('party_id', $user->party_id)->lockForUpdate()->first();
        if ($identity === null) {
            return false;
        }

        DB::table('released_person_identities')->insert([
            'id' => strtolower((string) Str::ulid()), 'identity_digest' => $identity->identity_digest,
            'party_id' => $identity->party_id, 'evidence_reference' => $identity->evidence_reference,
            'closed_user_id' => $user->id, 'verified_at' => $identity->created_at, 'released_at' => $closedAt,
        ]);
        DB::table('verified_person_identities')->where('identity_digest', $identity->identity_digest)->delete();

        return true;
    }

    /** @return list<string> */
    private function roles(User $user): array
    {
        $roles = $user->party_id === null ? [] : DB::table('role_memberships')->where('party_id', $user->party_id)
            ->where('status', 'active')->orderBy('role')->pluck('role')->map(fn (mixed $role): string => (string) $role)->unique()->all();

        return array_values($this->isStaff($user) ? [...$roles, 'staff'] : $roles);
    }

    private function isStaff(User $user): bool
    {
        return DB::table('staff_accounts')->where('user_id', $user->id)->exists();
    }

    /** @return list<Blocker> */
    private function blockers(User $user): array
    {
        if ($this->isStaff($user)) {
            return [self::blocker('STAFF_ACCOUNT', 'staff')];
        }
        if ($user->party_id === null) {
            return [];
        }
        $party = $user->party_id;

        $blockers = [...$this->investor($party), ...$this->auditor($party)];
        foreach ($this->businesses($party) as $businessId) {
            $blockers = [...$blockers, ...$this->business($businessId)];
        }

        return $blockers;
    }

    /** @return list<Blocker> */
    private function investor(string $party): array
    {
        $blockers = [];
        $walletId = DB::table('investor_wallets')->where('party_id', $party)->value('id');
        if ($walletId !== null) {
            if ($this->bucketBalance((string) $walletId, self::INVESTOR_BUCKETS) > 0) {
                $blockers[] = self::blocker('INVESTOR_WALLET_BALANCE', 'investor');
            }
            if ($this->pendingDeposits('wallet_deposit_intents', 'wallet_provider_events', (string) $walletId)) {
                $blockers[] = self::blocker('INVESTOR_MONEY_MOVING', 'investor');
            }
        }

        $commitments = DB::table('primary_commitments')->join('primary_reservations', 'primary_reservations.id', '=', 'primary_commitments.primary_reservation_id')
            ->where('primary_reservations.party_id', $party)
            ->whereNotExists(fn ($query) => $query->from('primary_holdings')->whereColumn('primary_holdings.commitment_id', 'primary_commitments.id'))
            ->whereNotExists(fn ($query) => $query->from('ledger_entries')->where('ledger_entries.source_type', 'primary_reservation')
                ->whereColumn('ledger_entries.source_id', 'primary_reservations.id')->where('ledger_entries.kind', 'primary_refund'))
            ->whereNotExists(fn ($query) => $query->from('business_campaign_closures')
                ->whereColumn('business_campaign_closures.business_campaign_id', 'primary_reservations.business_campaign_id'))
            ->exists();
        if ($commitments || DB::table('investor_cart_live_checkouts')->where('party_id', $party)->exists()) {
            $blockers[] = self::blocker('INVESTOR_COMMITMENTS', 'investor');
        }

        foreach ($this->unitsHeld($party) as $campaignId => $units) {
            if ($units > 0 && ! $this->notes->repaid($campaignId)) {
                $blockers[] = self::blocker('INVESTOR_HOLDINGS', 'investor');
                break;
            }
        }

        if (DB::table('secondary_listings')->where('seller_party_id', $party)->where('state', 'open')->exists()
            || DB::table('secondary_reservations')->where('buyer_party_id', $party)->whereNull('ended')->exists()) {
            $blockers[] = self::blocker('INVESTOR_MARKET_ORDERS', 'investor');
        }

        return $blockers;
    }

    /**
     * Units the party still holds in each note: issued to it, plus bought on the market, less sold
     * on the market (every unit it sells comes from one of its own lots).
     *
     * @return array<string, int>
     */
    private function unitsHeld(string $party): array
    {
        $held = [];
        $rows = DB::table('primary_holdings')->where('party_id', $party)->groupBy('business_campaign_id')
            ->selectRaw('business_campaign_id AS note, sum(units) AS units')->get()
            ->concat(DB::table('secondary_transfers')->where('buyer_party_id', $party)->groupBy('business_campaign_id')
                ->selectRaw('business_campaign_id AS note, sum(units) AS units')->get())
            ->concat(DB::table('secondary_transfers')->where('seller_party_id', $party)->groupBy('business_campaign_id')
                ->selectRaw('business_campaign_id AS note, -sum(units) AS units')->get());
        foreach ($rows as $row) {
            $note = (string) $row->note;
            $held[$note] = ($held[$note] ?? 0) + (int) $row->units;
        }

        return $held;
    }

    /**
     * The businesses whose active mandate names this person.
     *
     * @return list<string>
     */
    private function businesses(string $party): array
    {
        $now = now('UTC')->format('Y-m-d\TH:i:s\Z');

        return array_values(DB::table('business_profiles')->join('business_mandates', 'business_mandates.business_id', '=', 'business_profiles.id')
            ->whereColumn('business_mandates.version', 'business_profiles.mandate_version')
            ->whereJsonContains('business_mandates.terms->people', [['party_id' => $party]])
            ->where('business_mandates.terms->status', 'active')->where('business_mandates.terms->effective_at', '<=', $now)
            ->whereRaw("(business_mandates.terms->>'expires_at' IS NULL OR business_mandates.terms->>'expires_at' > ?)", [$now])
            ->orderBy('business_profiles.id')->pluck('business_profiles.id')->map(fn (mixed $id): string => (string) $id)->all());
    }

    /** @return list<Blocker> */
    private function business(string $businessId): array
    {
        $blockers = [];
        $live = DB::table('business_campaigns')->where('business_id', $businessId)
            ->whereNotExists(fn ($query) => $query->from('business_campaign_closures')->whereColumn('business_campaign_closures.business_campaign_id', 'business_campaigns.id'))
            ->whereNotExists(fn ($query) => $query->from('disbursements')->join('disbursement_closings', 'disbursement_closings.disbursement_id', '=', 'disbursements.id')
                ->whereColumn('disbursements.business_campaign_id', 'business_campaigns.id')->where('disbursement_closings.kind', 'issued'))
            ->exists();
        if ($live) {
            $blockers[] = self::blocker('BUSINESS_LIVE_RAISE', 'business', $businessId);
        }

        $inReview = DB::table('business_applications')->where('business_id', $businessId)->where('status', 'submitted')
            ->whereNotExists(fn ($query) => $query->from('business_application_releases')->whereColumn('business_application_releases.business_application_id', 'business_applications.id'))
            ->exists();
        if ($inReview) {
            $blockers[] = self::blocker('BUSINESS_APPLICATION_IN_REVIEW', 'business', $businessId);
        }

        $notes = DB::table('disbursements')->join('disbursement_closings', 'disbursement_closings.disbursement_id', '=', 'disbursements.id')
            ->where('disbursements.business_id', $businessId)->whereNotNull('disbursement_closings.due_dates')->pluck('disbursements.business_campaign_id');
        foreach ($notes as $note) {
            if (! $this->notes->repaid((string) $note)) {
                $blockers[] = self::blocker('BUSINESS_REPAYMENTS_OWED', 'business', $businessId);
                break;
            }
        }

        $walletId = DB::table('business_wallets')->where('business_id', $businessId)->value('id');
        if ($walletId !== null) {
            if ($this->bucketBalance((string) $walletId, ['business_available', 'business_withdrawing']) > 0) {
                $blockers[] = self::blocker('BUSINESS_WALLET_BALANCE', 'business', $businessId);
            }
            if ($this->pendingDeposits('business_deposit_intents', 'business_provider_events', (string) $walletId)) {
                $blockers[] = self::blocker('BUSINESS_MONEY_MOVING', 'business', $businessId);
            }
        }

        return $blockers;
    }

    /** @return list<Blocker> */
    private function auditor(string $party): array
    {
        $blockers = [];
        if (DB::table('audit_assignments')->where('party_id', $party)->whereIn('status', ['offered', 'accepted'])->exists()) {
            $blockers[] = self::blocker('AUDITOR_OPEN_AUDITS', 'auditor');
        }
        $pendingReports = DB::table('audit_reports')->where('author_party_id', $party)
            ->where(fn ($query) => $query->whereIn('status', ['draft', 'changes_requested'])
                ->orWhereExists(fn ($publication) => $publication->from('audit_report_publications')
                    ->whereColumn('audit_report_publications.audit_report_id', 'audit_reports.id')->where('audit_report_publications.status', 'pending')))
            ->exists();
        if ($pendingReports) {
            $blockers[] = self::blocker('AUDITOR_REPORTS_PENDING', 'auditor');
        }

        return $blockers;
    }

    /** @param  list<string>  $kinds */
    private function bucketBalance(string $walletId, array $kinds): float
    {
        return (float) DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
            ->where('ledger_accounts.wallet_id', $walletId)->whereIn('ledger_accounts.kind', $kinds)
            ->selectRaw("coalesce(sum(CASE ledger_lines.direction WHEN 'credit' THEN ledger_lines.amount ELSE -ledger_lines.amount END), 0) AS balance")
            ->value('balance');
    }

    /** A deposit is still on its way while its intent has no applied final provider outcome. */
    private function pendingDeposits(string $intents, string $events, string $walletId): bool
    {
        return DB::table($intents)->where('wallet_id', $walletId)
            ->whereNotIn('id', DB::table($events)->where('disposition', 'applied')->whereIn('state', ['succeeded', 'failed'])->select('intent_id'))
            ->exists();
    }

    /**
     * @param  'investor'|'business'|'auditor'|'staff'  $role
     * @return Blocker
     */
    private static function blocker(string $code, string $role, ?string $businessId = null): array
    {
        return ['code' => $code, 'role' => $role, 'business_id' => $businessId];
    }
}
