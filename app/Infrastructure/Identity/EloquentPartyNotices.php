<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Identity\PartyNoticeKind;
use App\Models\PartyNotice;
use App\Models\PartyNoticeDelivery;
use App\Models\User;
use App\Notifications\Investor\RaiseExpired;
use App\Notifications\Investor\VerificationDecided;
use App\Notifications\RozineMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Notices in `party_notices`, receipts in `party_notice_deliveries`. The in-app receipt is written
 * with the notice; an e-mailed kind goes to each of the Party's accounts once the event commits,
 * and each send leaves a `sent` or `failed` receipt.
 *
 * @phpstan-import-type Inbox from PartyNotices
 */
final readonly class EloquentPartyNotices implements PartyNotices
{
    public function record(string $partyId, PartyNoticeKind $kind, array $facts, string $eventKey): void
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
            DB::afterCommit(fn () => $this->mail($id, $partyId, $this->message($kind, $facts)));
        }
    }

    public function inbox(int $userId, int $limit): array
    {
        $notices = $this->owned($userId)->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get();

        return ['unread' => $this->unread($userId), 'notices' => array_values($notices->map(fn (PartyNotice $notice): array => [
            'id' => $notice->id, 'kind' => $notice->kind, 'facts' => $notice->facts,
            'created_at' => $notice->created_at->toIso8601String(), 'read' => $notice->read_at !== null])->all())];
    }

    public function unread(int $userId): int
    {
        return $this->owned($userId)->whereNull('read_at')->count();
    }

    public function markRead(int $userId, ?string $noticeId): int
    {
        $query = $this->owned($userId)->whereNull('read_at');

        return ($noticeId === null ? $query : $query->whereKey($noticeId))->update(['read_at' => now('UTC')->format('Y-m-d H:i:s.u')]);
    }

    /** @return Builder<PartyNotice> */
    private function owned(int $userId): Builder
    {
        return PartyNotice::query()->where('party_id', User::query()->whereKey($userId)->whereNotNull('party_id')->select('party_id'));
    }

    /** @param  array<string, string>  $facts */
    private function message(PartyNoticeKind $kind, array $facts): RozineMail
    {
        return match ($kind) {
            PartyNoticeKind::RaiseRefunded => new RaiseExpired($facts['business'], $facts['amount'], route('investor.wallet')),
            PartyNoticeKind::VerificationApproved => VerificationDecided::approved(route('investor.deals')),
            default => VerificationDecided::rejected($facts['reason'], route('investor.verification')),
        };
    }

    private function mail(string $noticeId, string $partyId, RozineMail $message): void
    {
        foreach (User::query()->where('party_id', $partyId)->orderBy('id')->get() as $user) {
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
