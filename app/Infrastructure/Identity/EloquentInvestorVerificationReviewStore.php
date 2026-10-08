<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\InvestorVerificationReviewStore;
use App\Application\Identity\VerifyInvestorIdentity;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\InvestorVerificationCase;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\InvestorVerification;
use App\Models\InvestorVerificationDocument;
use App\Models\InvestorVerificationVersion;
use App\Models\Party;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Queue from InvestorVerificationReviewStore
 * @phpstan-import-type Review from InvestorVerificationReviewStore
 */
final class EloquentInvestorVerificationReviewStore implements InvestorVerificationReviewStore
{
    private const POLICY_VERSION = 'engineering-2026-10-06.1';

    public function __construct(
        private OperationJournal $journal,
        private InvestorVerificationCase $cases,
        private AuthorizeStaffPermission $staff,
        private VerifyInvestorIdentity $verify,
    ) {}

    /** @return Queue */
    public function queue(int $actorId, string $tab, string $search, ?string $before, int $limit): array
    {
        $this->staff->check($actorId, 'investors.verify');
        $decided = $tab === 'decided';
        $query = $this->withAccount(InvestorVerification::query())->whereIn('status', $decided ? ['approved', 'rejected'] : ['submitted']);
        if ($search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $query->whereExists(fn ($users) => $users->selectRaw('1')->from('users')->whereColumn('users.party_id', 'investor_verifications.party_id')
                ->where(fn ($match) => $match->whereLike('users.name', $term)->orWhereLike('users.email', $term)));
        }
        if ($before !== null) {
            $anchor = InvestorVerification::query()->find($before) ?? throw new CommandRejection('CURSOR_INVALID', 422);
            $decided
                ? $query->whereRaw('(updated_at, id) < (?, ?)', [$anchor->getRawOriginal('updated_at'), $anchor->id])
                : $query->whereRaw('(submitted_at, id) > (?, ?)', [$anchor->getRawOriginal('submitted_at'), $anchor->id]);
        }
        $decided ? $query->orderByDesc('updated_at')->orderByDesc('id') : $query->orderBy('submitted_at')->orderBy('id');
        $records = $query->limit($limit + 1)->get();
        $more = $records->count() > $limit;
        $records = $records->take($limit);

        return ['tab' => $decided ? 'decided' : 'submitted', 'search' => $search, 'limit' => $limit, 'before' => $before,
            'entries' => array_values($records->map(fn (InvestorVerification $record): array => [
                'id' => $record->id, 'revision' => $record->revision, 'status' => $record->status,
                'submitted_at' => $record->submitted_at?->toIso8601String() ?? '', 'decided_at' => $record->state['decision']['decided_at'] ?? null,
                'name' => (string) $record->getAttribute('account_name'), 'email' => (string) $record->getAttribute('account_email'),
                'id_type' => $record->state['id_type'],
            ])->all()),
            'next_cursor' => $more ? $records->last()?->id : null,
            'counts' => ['submitted' => InvestorVerification::query()->where('status', 'submitted')->count(),
                'decided' => InvestorVerification::query()->whereIn('status', ['approved', 'rejected'])->count()]];
    }

    /** @return Review */
    public function show(int $actorId, string $verificationId): array
    {
        $this->staff->check($actorId, 'investors.verify');
        $record = $this->withAccount(InvestorVerification::query())->find($verificationId) ?? throw new CommandRejection('VERIFICATION_NOT_FOUND', 404);
        $current = array_filter($record->state['uploads']);

        return ['id' => $record->id, 'revision' => $record->revision, 'status' => $record->status,
            'submitted_at' => $record->submitted_at?->toIso8601String(),
            'account' => ['name' => (string) $record->getAttribute('account_name'), 'email' => (string) $record->getAttribute('account_email')],
            'state' => $record->state,
            'documents' => array_values(InvestorVerificationDocument::query()->where('investor_verification_id', $record->id)
                ->orderBy('created_at')->orderBy('id')->get()->map(fn (InvestorVerificationDocument $document): array => [
                    'id' => $document->id, 'slot' => $document->slot, 'filename' => $document->filename, 'media_type' => $document->media_type,
                    'size_bytes' => $document->size_bytes, 'sha256' => $document->sha256,
                    'uploaded_at' => (string) $document->getAttribute('created_at')?->toIso8601String(), 'current' => in_array($document->id, $current, true),
                ])->all()),
            'history' => array_values(InvestorVerificationVersion::query()->where('investor_verification_id', $record->id)
                ->orderBy('revision')->get()->map(fn (InvestorVerificationVersion $version): array => [
                    'revision' => (int) $version->getAttribute('revision'), 'status' => (string) $version->getAttribute('status'),
                    'command' => (string) $version->getAttribute('command'), 'reason' => $version->getAttribute('reason'),
                    'at' => (string) $version->getAttribute('created_at')?->toIso8601String(),
                ])->all()),
            'allowed_actions' => $record->status === 'submitted' ? ['approve', 'reject'] : []];
    }

    /** @return array{filename: string, media_type: string, content: string} */
    public function document(int $actorId, string $verificationId, string $documentId): array
    {
        $this->staff->check($actorId, 'investors.verify');
        $document = InvestorVerificationDocument::query()->where('investor_verification_id', $verificationId)->find($documentId)
            ?? throw new CommandRejection('VERIFICATION_DOCUMENT_NOT_FOUND', 404);

        if (! hash_equals($document->sha256, hash('sha256', $document->content))) {
            throw new CommandRejection('VERIFICATION_DOCUMENT_INTEGRITY_FAILED', 409);
        }

        return ['filename' => $document->filename, 'media_type' => $document->media_type, 'content' => $document->content];
    }

    /** @return array<string, mixed> */
    public function approve(int $actorId, string $verificationId, int $expectedRevision, string $reason, string $requestId): array
    {
        return $this->command($actorId, $verificationId, $expectedRevision, $reason, $requestId, 'approve', function (?int $participant) use ($actorId, $verificationId, $expectedRevision, $reason, $requestId): OperationResult {
            $record = $this->decidable(InvestorVerification::query()->find($verificationId), $expectedRevision);
            $userId = $this->locked($record, $participant);
            try {
                $result = $this->verify->handle($actorId, $userId, $this->cases->identityReference($record->state),
                    'investor-verification:'.$record->id.'@'.$record->revision, $reason, $requestId,
                    fn (): array => $this->decide($verificationId, $expectedRevision, 'approved', $reason, $actorId));
            } catch (IdentityViolation $violation) {
                // The verified-person writer's refusal is this decision's outcome, kept like any other.
                throw new CommandRejection($violation->reason, $violation->status);
            }

            return new OperationResult('INVESTOR_VERIFIED', ['verification_id' => $verificationId, 'status' => 'approved',
                'party_id' => $result['party_id'], 'membership' => $result['membership']], $result['verification']['revision'], [], self::POLICY_VERSION);
        });
    }

    /** @return array<string, mixed> */
    public function reject(int $actorId, string $verificationId, int $expectedRevision, string $reason, string $requestId): array
    {
        return $this->command($actorId, $verificationId, $expectedRevision, $reason, $requestId, 'reject', function (?int $participant) use ($actorId, $verificationId, $expectedRevision, $reason): OperationResult {
            $record = $this->decidable(InvestorVerification::query()->find($verificationId), $expectedRevision);
            $this->locked($record, $participant);
            Party::query()->lockForUpdate()->findOrFail($record->party_id);
            $decision = $this->decide($verificationId, $expectedRevision, 'rejected', $reason, $actorId);

            return new OperationResult('VERIFICATION_REJECTED', ['verification_id' => $verificationId, 'status' => 'rejected'],
                $decision['revision'], [], self::POLICY_VERSION);
        });
    }

    /**
     * Locks the reviewer and the case's one account in ascending ID, then checks the reviewer's current
     * permission under that lock, before the journal either replays a recorded decision or records a new
     * one. Every later lock (identity, Party, submission) follows these accounts.
     *
     * @param  Closure(int|null): OperationResult  $operation
     * @return array<string, mixed>
     */
    private function command(int $actorId, string $verificationId, int $expectedRevision, string $reason, string $requestId, string $decision, Closure $operation): array
    {
        if (trim($reason) === '' || mb_strlen($reason) > 1000) {
            return ['status' => 'rejected', 'code' => 'DECISION_REASON_REQUIRED', 'field_errors' => ['reason' => ['Give the reason for this decision, in at most 1,000 characters.']], 'http_status' => 422];
        }
        try {
            return DB::transaction(function () use ($actorId, $verificationId, $expectedRevision, $reason, $requestId, $decision, $operation): array {
                $record = InvestorVerification::query()->find($verificationId);
                $participant = $record === null ? null : $this->participant($record->party_id);
                User::query()->whereKey(array_filter([$actorId, $participant]))->orderBy('id')->lockForUpdate()->get();

                return $this->staff->handle($actorId, 'investors.verify', fn (): array => $this->journal->execute('staff:'.$actorId, $actorId,
                    'investor.verification.'.$decision, $requestId, 'investor.verification', $verificationId,
                    ['expected_revision' => $expectedRevision, 'reason' => $reason],
                    // The accounts and the reviewer's permission are already locked and checked above, for a first run and a replay alike.
                    static function (): void {},
                    static function () use ($operation, $participant): OperationResult {
                        try {
                            return $operation($participant);
                        } catch (CommandRejection $rejection) {
                            // A recorded refusal carries the KYC policy it was decided under, like an approval or rejection.
                            throw $rejection->underPolicy(self::POLICY_VERSION);
                        }
                    }));
            }, 3);
        } catch (CommandRejection $exception) {
            // Refused before it was recorded (a reused request_id): answered like a recorded refusal.
            return ['status' => 'rejected', 'code' => $exception->reason, 'field_errors' => $exception->fieldErrors, 'http_status' => $exception->status];
        }
    }

    /** A read before any lock, rechecked under the submission lock in decide(). */
    private function decidable(?InvestorVerification $record, int $expectedRevision): InvestorVerification
    {
        if ($record === null) {
            throw new CommandRejection('VERIFICATION_NOT_FOUND', 404);
        }
        if ($record->revision !== $expectedRevision) {
            throw new CommandRejection('VERSION_CONFLICT', 409, $record->revision);
        }
        if ($record->status !== 'submitted') {
            throw new CommandRejection('VERIFICATION_NOT_SUBMITTED', 409, $record->revision);
        }

        return $record;
    }

    /** The one account a self-submitted case belongs to, or null when there is none or more than one. */
    private function participant(string $partyId): ?int
    {
        $users = User::query()->where('party_id', $partyId)->pluck('id');

        return $users->count() === 1 ? (int) $users->first() : null;
    }

    /**
     * Rereads the case's account under the account locks: it must still be exactly the one account locked
     * before the journal ran. Several accounts, none, or a changed account-to-Party link is for an identity
     * operator, never a staff decision.
     */
    private function locked(InvestorVerification $record, ?int $participant): int
    {
        $current = $this->participant($record->party_id);
        if ($current === null || $current !== $participant) {
            throw new CommandRejection('IDENTITY_RECONCILIATION_REQUIRED', 409);
        }

        return $current;
    }

    /**
     * Locks the submission last, rechecks it and closes it with the reviewer's reason. An approval also
     * rechecks the whole submission and binds the hash-pinned uploads it verified.
     *
     * @param  'approved'|'rejected'  $outcome
     * @return array{verification_id: string, revision: int, documents?: array<string, string>}
     */
    private function decide(string $verificationId, int $expectedRevision, string $outcome, string $reason, int $actorId): array
    {
        $record = $this->decidable(InvestorVerification::query()->whereKey($verificationId)->lockForUpdate()->first(), $expectedRevision);
        $facts = ['verification_id' => $record->id];
        if ($outcome === 'approved') {
            $this->cases->complete($record->state, now()->toDateTimeImmutable());
            $uploads = array_filter($record->state['uploads']);
            $documents = InvestorVerificationDocument::query()->where('investor_verification_id', $record->id)->whereIn('id', $uploads)->get()->keyBy('id');
            if ($documents->count() !== count($uploads)) {
                throw new CommandRejection('VERIFICATION_DOCUMENT_REQUIRED', 409, $record->revision);
            }
            // The reader's own integrity check: evidence whose content no longer matches its pinned hash never approves.
            foreach ($documents as $document) {
                if (! hash_equals($document->sha256, hash('sha256', $document->content))) {
                    throw new CommandRejection('VERIFICATION_DOCUMENT_INTEGRITY_FAILED', 409, $record->revision);
                }
            }
            $facts['documents'] = array_map(fn (string $id): string => (string) $documents[$id]->sha256, $uploads);
        }
        $state = [...$record->state, 'decision' => ['outcome' => $outcome, 'reason' => $reason, 'decided_at' => now()->toIso8601String()]];
        $record->forceFill(['revision' => $expectedRevision + 1, 'status' => $outcome, 'state' => $state])->save();
        (new InvestorVerificationVersion)->forceFill(['investor_verification_id' => $record->id, 'revision' => $record->revision,
            'status' => $outcome, 'snapshot' => $state, 'actor_user_id' => $actorId, 'command' => 'verification.'.($outcome === 'approved' ? 'approve' : 'reject'),
            'reason' => $reason, 'policy_version' => self::POLICY_VERSION])->save();

        return [...$facts, 'revision' => $record->revision];
    }

    /**
     * @param  Builder<InvestorVerification>  $query
     * @return Builder<InvestorVerification>
     */
    private function withAccount(Builder $query): Builder
    {
        $account = fn (string $column) => User::query()->select($column)->whereColumn('users.party_id', 'investor_verifications.party_id')->orderBy('id')->limit(1);

        return $query->select('investor_verifications.*')->addSelect(['account_name' => $account('name'), 'account_email' => $account('email')]);
    }
}
