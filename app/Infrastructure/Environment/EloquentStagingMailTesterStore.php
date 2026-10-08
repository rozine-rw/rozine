<?php

declare(strict_types=1);

namespace App\Infrastructure\Environment;

use App\Application\Environment\Contracts\StagingMailTesterStore;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\StagingMailTester;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type Tester from StagingMailTesterStore
 */
final class EloquentStagingMailTesterStore implements StagingMailTesterStore
{
    private const string PERMISSION = 'staging.mail.testers.manage';

    private const string POLICY_VERSION = 'staging-mail-2026-10-07.1';

    public function __construct(private AuthorizeStaffPermission $staff, private OperationJournal $journal) {}

    public function includes(string $email): bool
    {
        return StagingMailTester::query()->where('email', self::normalized($email))->exists();
    }

    public function authorize(int $actorId): void
    {
        $this->staff->check($actorId, self::PERMISSION);
    }

    /** @return list<Tester> */
    public function list(int $actorId): array
    {
        $this->staff->check($actorId, self::PERMISSION);
        $testers = StagingMailTester::query()->orderBy('email')->get();
        $names = User::query()->whereKey($testers->pluck('added_by_user_id')->unique()->all())->pluck('name', 'id');

        return array_values($testers->map(fn (StagingMailTester $tester): array => ['id' => $tester->id, 'email' => $tester->email,
            'added_by' => (string) ($names[$tester->added_by_user_id] ?? ''), 'added_at' => $tester->created_at->toIso8601String()])->all());
    }

    /** @return array<string, mixed> */
    public function add(int $actorId, string $email, string $reason, string $requestId): array
    {
        $email = self::normalized($email);

        if (mb_strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['status' => 'rejected', 'code' => 'TESTER_EMAIL_INVALID', 'field_errors' => ['email' => ['Enter one email address.']], 'http_status' => 422];
        }

        return $this->command($actorId, 'staging.mail.tester.add', $requestId, $email, $reason, ['email' => $email], function () use ($actorId, $email, $reason): OperationResult {
            $id = strtolower((string) Str::ulid());
            // ON CONFLICT DO NOTHING: a concurrent add of the same address is refused, never a failed transaction.
            $added = StagingMailTester::query()->insertOrIgnore(['id' => $id, 'email' => $email, 'added_by_user_id' => $actorId,
                'created_at' => now(), 'updated_at' => now()]);

            if ($added === 0) {
                throw new CommandRejection('TESTER_ALREADY_APPROVED', 409);
            }

            return new OperationResult('STAGING_MAIL_TESTER_ADDED', ['tester_id' => $id, 'email' => $email, 'reason' => $reason], 1, [], self::POLICY_VERSION);
        });
    }

    /** @return array<string, mixed> */
    public function remove(int $actorId, string $testerId, string $reason, string $requestId): array
    {
        return $this->command($actorId, 'staging.mail.tester.remove', $requestId, $testerId, $reason, [], function () use ($testerId, $reason): OperationResult {
            $tester = StagingMailTester::query()->lockForUpdate()->find($testerId)
                ?? throw new CommandRejection('TESTER_NOT_FOUND', 404);
            $tester->delete();

            return new OperationResult('STAGING_MAIL_TESTER_REMOVED', ['tester_id' => $testerId, 'email' => $tester->email, 'reason' => $reason], 1, [], self::POLICY_VERSION);
        });
    }

    /**
     * Checks the superadmin's current permission under its account lock, then records the change in the
     * journal, whose kept result carries the reason, and which replays a repeated request instead of
     * applying it twice.
     *
     * @param  array<string, mixed>  $input
     * @param  Closure(): OperationResult  $operation
     * @return array<string, mixed>
     */
    private function command(int $actorId, string $command, string $requestId, string $targetId, string $reason, array $input, Closure $operation): array
    {
        if (trim($reason) === '' || mb_strlen($reason) > 1000) {
            return ['status' => 'rejected', 'code' => 'CHANGE_REASON_REQUIRED', 'field_errors' => ['reason' => ['Give the reason for this change, in at most 1,000 characters.']], 'http_status' => 422];
        }

        try {
            return DB::transaction(fn (): array => $this->staff->handle($actorId, self::PERMISSION, fn (): array => $this->journal->execute(
                'staff:'.$actorId, $actorId, $command, $requestId, 'staging.mail.tester', $targetId, [...$input, 'reason' => $reason],
                static function (): void {},
                static function () use ($operation): OperationResult {
                    try {
                        return $operation();
                    } catch (CommandRejection $rejection) {
                        throw $rejection->underPolicy(self::POLICY_VERSION);
                    }
                })), 3);
        } catch (CommandRejection $exception) {
            // Refused before it was recorded, such as a reused request_id with other details.
            return ['status' => 'rejected', 'code' => $exception->reason, 'field_errors' => $exception->fieldErrors, 'http_status' => $exception->status];
        }
    }

    private static function normalized(string $email): string
    {
        return strtolower(trim($email));
    }
}
