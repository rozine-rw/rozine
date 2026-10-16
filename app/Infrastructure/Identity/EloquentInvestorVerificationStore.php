<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\InvestorVerificationStore;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\InvestorVerificationCase;
use App\Domain\Identity\VerificationDocument;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\InvestorVerification;
use App\Models\InvestorVerificationDocument;
use App\Models\InvestorVerificationVersion;
use App\Models\Party;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type State from InvestorVerificationCase
 * @phpstan-import-type Submission from InvestorVerificationStore
 */
final class EloquentInvestorVerificationStore implements InvestorVerificationStore
{
    /** The KYC rules each recorded step, upload and submission ran under; receipts report the same version. */
    private const string POLICY_VERSION = 'engineering-2026-10-06.1';

    public function __construct(
        private OperationJournal $journal,
        private InvestorVerificationCase $cases,
        private VerificationDocument $documents,
    ) {}

    /** @return Submission */
    public function get(int $userId): array
    {
        return $this->own($userId, null, fn (User $user, Party $party, ?InvestorVerification $record, bool $verified): array => [
            'revision' => $record->revision ?? 0, 'status' => $record->status ?? 'draft', 'state' => $record->state ?? $this->cases->empty(),
            'verified' => $verified, 'identity_context_revision' => $user->context_revision,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, int $expectedRevision, string $step, array $input, string $requestId): array
    {
        $answers = ['date_of_birth' => $input['date_of_birth'] ?? null, 'id_type' => $input['id_type'] ?? null, 'id_number' => $input['id_number'] ?? null];

        return $this->execute($userId, $contextRevision, $expectedRevision, 'verification.save', $requestId, ['step' => $step, ...$answers],
            fn (array $state, string $status): array => $this->cases->save($state, $status, $step, $answers, now()->toDateTimeImmutable()));
    }

    /** @return array<string, mixed> */
    public function upload(int $userId, int $contextRevision, int $expectedRevision, string $slot, string $filename, string $content, string $requestId): array
    {
        $documentId = strtolower((string) Str::ulid());
        $source = [];

        return $this->execute($userId, $contextRevision, $expectedRevision, 'verification.upload', $requestId,
            ['slot' => $slot, 'filename_sha256' => hash('sha256', $filename), 'sha256' => hash('sha256', $content)],
            function (array $state, string $status) use ($slot, $filename, $content, $documentId, &$source): array {
                $state = $this->cases->upload($state, $status, $slot, $documentId);
                $source = $this->documents->describe($filename, $content);

                return $state;
            },
            function (InvestorVerification $record) use ($slot, $content, $documentId, $userId, &$source): void {
                (new InvestorVerificationDocument)->forceFill([...$source, 'id' => $documentId, 'investor_verification_id' => $record->id,
                    'slot' => InvestorVerificationCase::SLOTS[$slot], 'content' => $content, 'actor_user_id' => $userId])->save();
            });
    }

    /** @return array<string, mixed> */
    public function submit(int $userId, int $contextRevision, int $expectedRevision, string $requestId): array
    {
        return $this->execute($userId, $contextRevision, $expectedRevision, 'verification.submit', $requestId, [],
            fn (array $state, string $status): array => $this->cases->submit($state, $status, now()->toDateTimeImmutable()), submitted: true);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(State, string): State  $change
     * @param  Closure(InvestorVerification): void|null  $afterSave
     * @return array<string, mixed>
     */
    private function execute(int $userId, int $contextRevision, int $expectedRevision, string $command, string $requestId, array $input, Closure $change, ?Closure $afterSave = null, bool $submitted = false): array
    {
        try {
            return $this->journal($userId, $contextRevision, $expectedRevision, $command, $requestId, $input, $change, $afterSave, $submitted);
        } catch (CommandRejection $exception) {
            // Refused before it was recorded (a reused request_id): answered like a recorded refusal.
            return ['status' => 'rejected', 'code' => $exception->reason, 'field_errors' => $exception->fieldErrors, 'http_status' => $exception->status];
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(State, string): State  $change
     * @param  Closure(InvestorVerification): void|null  $afterSave
     * @return array<string, mixed>
     */
    private function journal(int $userId, int $contextRevision, int $expectedRevision, string $command, string $requestId, array $input, Closure $change, ?Closure $afterSave, bool $submitted): array
    {
        return $this->own($userId, $contextRevision, fn (User $user, Party $party, ?InvestorVerification $record, bool $verified): array => $this->journal->execute(
            'party:'.$party->id, $userId, $command, $requestId, 'investor.verification', $party->id,
            ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, ...$input],
            // The account and Party are already locked and checked above, for a first run and a replay alike.
            static function (): void {},
            self::underKycPolicy(function () use ($party, $record, $verified, $userId, $expectedRevision, $command, $change, $afterSave, $submitted): OperationResult {
                if ($verified) {
                    throw new CommandRejection('VERIFICATION_NOT_REQUIRED');
                }
                if (($record->revision ?? 0) !== $expectedRevision) {
                    throw new CommandRejection('VERSION_CONFLICT', 409, $record->revision ?? 0);
                }
                $state = $change($record->state ?? $this->cases->empty(), $record->status ?? 'draft');
                $record ??= new InvestorVerification;
                $record->forceFill(['party_id' => $party->id, 'revision' => $expectedRevision + 1, 'state' => $state,
                    'status' => $submitted ? 'submitted' : 'draft', 'submitted_at' => $submitted ? now() : null])->save();
                if ($afterSave !== null) {
                    $afterSave($record);
                }
                (new InvestorVerificationVersion)->forceFill(['investor_verification_id' => $record->id, 'revision' => $record->revision,
                    'status' => $record->status, 'snapshot' => $state, 'actor_user_id' => $userId, 'command' => $command,
                    'policy_version' => self::POLICY_VERSION])->save();

                return new OperationResult($submitted ? 'VERIFICATION_SUBMITTED' : 'VERIFICATION_SAVED',
                    ['verification_id' => $record->id, 'status' => $record->status, 'step' => $state['step']], $record->revision, [], self::POLICY_VERSION);
            })));
    }

    /**
     * A read with no lock: it changes nothing, and an approved submission is never edited again.
     *
     * @return array{id_type: 'national_id'|'passport'|'drivers_license', id_number: string}|null
     */
    public function approvedDocument(int $userId): ?array
    {
        $partyId = User::query()->whereKey($userId)->value('party_id');
        $state = $partyId === null ? null
            : InvestorVerification::query()->where('party_id', $partyId)->where('status', 'approved')->value('state');
        if (! is_array($state) || ($state['id_number'] ?? '') === '') {
            return null;
        }

        /** @var State $state */
        return ['id_type' => $state['id_type'], 'id_number' => $state['id_number']];
    }

    /**
     * Refusals recorded for these commands carry the KYC policy they were decided under, like their successes.
     *
     * @param  Closure(): OperationResult  $operation
     * @return Closure(): OperationResult
     */
    private static function underKycPolicy(Closure $operation): Closure
    {
        return static function () use ($operation): OperationResult {
            try {
                return $operation();
            } catch (CommandRejection $rejection) {
                throw $rejection->underPolicy(self::POLICY_VERSION);
            }
        };
    }

    /**
     * The account, then its Party, then the submission: the verified-person writer's lock prefix.
     *
     * @template TResult
     *
     * @param  Closure(User, Party, InvestorVerification|null, bool): TResult  $operation
     * @return TResult
     */
    private function own(int $userId, ?int $contextRevision, Closure $operation): mixed
    {
        return DB::transaction(function () use ($userId, $contextRevision, $operation): mixed {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            if ($user->party_id === null) {
                throw new IdentityViolation('IDENTITY_NOT_LINKED');
            }
            if ($contextRevision !== null && $user->context_revision !== $contextRevision) {
                throw new IdentityViolation('ACTIVE_ROLE_REVISION_CONFLICT', 409);
            }
            $party = Party::query()->lockForUpdate()->findOrFail($user->party_id);
            if ($party->kind !== 'person') {
                throw new IdentityViolation('PARTY_AUTHORITY_REQUIRED');
            }
            $verified = $party->verified_at !== null && $party->verified_at->lte(now()) && $party->verifiedIdentity()->exists();
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['investor-verification:'.$party->id]);

            return $operation($user, $party, InvestorVerification::query()->where('party_id', $party->id)->lockForUpdate()->first(), $verified);
        }, 3);
    }
}
