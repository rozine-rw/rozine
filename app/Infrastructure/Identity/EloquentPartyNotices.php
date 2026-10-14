<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Identity\PartyNoticeKind;
use App\Models\PartyNotice;
use App\Models\PartyNoticeDelivery;
use App\Models\User;
use App\Notifications\Account\NewDeviceSignIn;
use App\Notifications\Auditor\AccreditationDecided;
use App\Notifications\Auditor\AuditJobType;
use App\Notifications\Auditor\JobOffered;
use App\Notifications\Business\ApplicationDecided;
use App\Notifications\Business\ApplicationOutcome;
use App\Notifications\Business\RaiseExpired as BusinessRaiseExpired;
use App\Notifications\Investor\InvestmentConfirmed;
use App\Notifications\Investor\RaiseExpired;
use App\Notifications\Investor\ReportPublished;
use App\Notifications\Investor\VerificationDecided;
use App\Notifications\Investor\WalletTransfer;
use App\Notifications\Investor\WalletTransferCompleted;
use App\Notifications\RozineMail;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Notices in `party_notices`, receipts in `party_notice_deliveries`. The in-app receipt is written
 * with the notice; an e-mailed kind goes to each named account (by default the Party's own) once
 * the event commits, and each send leaves a `sent` or `failed` receipt.
 *
 * @phpstan-import-type Inbox from PartyNotices
 */
final readonly class EloquentPartyNotices implements PartyNotices
{
    public function record(string $partyId, PartyNoticeKind $kind, array $facts, string $eventKey, ?array $mailTo = null): void
    {
        $notice = new PartyNotice;
        $notice->forceFill(['party_id' => $partyId, 'kind' => $kind->value, 'event_key' => $eventKey, 'facts' => $facts,
            'created_at' => now('UTC')]);
        $inserted = PartyNotice::query()->insertOrIgnore([...$notice->getAttributes(), 'id' => $notice->newUniqueId()]);
        if ($inserted === 0) {
            return;
        }
        $id = (string) PartyNotice::query()->where('event_key', $eventKey)->value('id');
        $this->receipt($id, 'in_app', null, 'delivered', null);
        if ($kind->mailed()) {
            DB::afterCommit(fn () => $this->mail($id, $mailTo ?? $this->accounts($partyId), $this->message($kind, $facts)));
        }
    }

    public function recordForAccount(int $userId, PartyNoticeKind $kind, array $facts, string $eventKey): void
    {
        $partyId = User::query()->whereKey($userId)->value('party_id');
        if (is_string($partyId)) {
            DB::transaction(fn () => $this->record($partyId, $kind, $facts, $eventKey));
        }
    }

    public function inbox(int $userId, int $limit): array
    {
        return $this->listed($this->owned($userId), $limit);
    }

    public function unread(int $userId): int
    {
        return $this->owned($userId)->whereNull('read_at')->count();
    }

    public function markRead(int $userId, ?string $noticeId): int
    {
        return $this->read($this->owned($userId), $noticeId);
    }

    public function partyInbox(string $partyId, int $limit): array
    {
        return $this->listed($this->held($partyId), $limit);
    }

    public function partyUnread(string $partyId): int
    {
        return $this->held($partyId)->whereNull('read_at')->count();
    }

    public function markPartyRead(string $partyId, ?string $noticeId): int
    {
        return $this->read($this->held($partyId), $noticeId);
    }

    /**
     * @param  Builder<PartyNotice>  $notices
     * @return Inbox
     */
    private function listed(Builder $notices, int $limit): array
    {
        $page = (clone $notices)->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get();

        return ['unread' => (clone $notices)->whereNull('read_at')->count(), 'notices' => array_values($page->map(fn (PartyNotice $notice): array => [
            'id' => $notice->id, 'kind' => $notice->kind, 'facts' => $notice->facts,
            'created_at' => $notice->created_at->toIso8601String(), 'read' => $notice->read_at !== null])->all())];
    }

    /** @param  Builder<PartyNotice>  $notices */
    private function read(Builder $notices, ?string $noticeId): int
    {
        $query = $notices->whereNull('read_at');

        return ($noticeId === null ? $query : $query->whereKey($noticeId))->update(['read_at' => now('UTC')->format('Y-m-d H:i:s.u')]);
    }

    /** @return Builder<PartyNotice> */
    private function owned(int $userId): Builder
    {
        return PartyNotice::query()->where('party_id', User::query()->whereKey($userId)->whereNotNull('party_id')->select('party_id'));
    }

    /** @return Builder<PartyNotice> */
    private function held(string $partyId): Builder
    {
        return PartyNotice::query()->where('party_id', $partyId);
    }

    /** @return list<int> */
    private function accounts(string $partyId): array
    {
        return array_values(array_map(intval(...), User::query()->where('party_id', $partyId)->orderBy('id')->pluck('id')->all()));
    }

    /** @param  array<string, string>  $facts */
    private function message(PartyNoticeKind $kind, array $facts): RozineMail
    {
        return match ($kind) {
            PartyNoticeKind::NewDevice => new NewDeviceSignIn($facts['device'], CarbonImmutable::parse($facts['signed_in_at']), route('security.edit')),
            PartyNoticeKind::RaiseRefunded => new RaiseExpired($facts['business'], $facts['amount'], route('investor.wallet')),
            PartyNoticeKind::ReportPublished => new ReportPublished($facts['business'], CarbonImmutable::parse($facts['period'].'-01')->format('F Y'),
                $facts['partner'], route('investor.portfolio')),
            PartyNoticeKind::HoldingIssued => new InvestmentConfirmed($facts['business'], $facts['amount'], (int) $facts['units'], $facts['expected_return'],
                $facts['maturity_value'], CarbonImmutable::parse($facts['maturity_date'], 'Africa/Kigali'), $facts['reference'],
                route('investor.holdings.show', ['holding' => $facts['holding_id']])),
            PartyNoticeKind::BusinessRaiseExpired => new BusinessRaiseExpired($facts['business'], $facts['target'], $facts['committed'],
                route('business.campaigns.show', ['business' => $facts['business_id'], 'campaign' => $facts['campaign_id']])),
            PartyNoticeKind::BusinessApplicationApproved => new ApplicationDecided(ApplicationOutcome::Approved, $facts['business'], $facts['amount'],
                (int) $facts['term_months'], route('business.applications.publish.show', ['business' => $facts['business_id'], 'application' => $facts['application_id']])),
            PartyNoticeKind::WithdrawalSent => new WalletTransferCompleted(WalletTransfer::Withdrawal, $facts['received'], $facts['account'],
                $facts['reference'], $facts['available'], CarbonImmutable::parse($facts['completed_at']), route('investor.wallet')),
            PartyNoticeKind::AuditorAccreditationApproved => AccreditationDecided::approved(route('auditor.profile')),
            PartyNoticeKind::AuditorAccreditationRejected => AccreditationDecided::rejected($facts['reason'], route('auditor.profile')),
            PartyNoticeKind::AuditorJobOffered => new JobOffered(AuditJobType::from($facts['type']), $facts['business'], $facts['location'],
                CarbonImmutable::parse($facts['accept_by']), route('auditor.jobs.index')),
            PartyNoticeKind::VerificationApproved => VerificationDecided::approved(route('investor.deals')),
            default => VerificationDecided::rejected($facts['reason'], route('investor.verification')),
        };
    }

    /**
     * Sends only for a notice that committed: a callback can outlive a commit that failed at its
     * deferred constraints, and must neither e-mail that event nor fail the next one.
     *
     * @param  list<int>  $accounts
     */
    private function mail(string $noticeId, array $accounts, RozineMail $message): void
    {
        if (! PartyNotice::query()->whereKey($noticeId)->exists()) {
            return;
        }
        foreach (User::query()->whereKey($accounts)->orderBy('id')->get() as $user) {
            try {
                $user->notifyNow($message);
                $this->receipt($noticeId, 'mail', $user->id, 'sent', null);
            } catch (Throwable $failure) {
                report($failure);
                $this->receipt($noticeId, 'mail', $user->id, 'failed', mb_substr($failure::class, 0, 200));
            }
        }
    }

    private function receipt(string $noticeId, string $channel, ?int $userId, string $status, ?string $detail): void
    {
        (new PartyNoticeDelivery)->forceFill(['party_notice_id' => $noticeId, 'channel' => $channel, 'user_id' => $userId,
            'status' => $status, 'detail' => $detail, 'created_at' => now('UTC')])->save();
    }
}
