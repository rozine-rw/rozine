<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Operations\Contracts\PolicyChangeStore;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Operations\PolicyChange;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

/**
 * Policy changes and the founders' decisions, under the journal. The setting's row is locked before
 * its change, so two founders deciding at once apply the change exactly once.
 *
 * @phpstan-import-type Board from PolicyChangeStore
 * @phpstan-import-type Change from PolicyChangeStore
 * @phpstan-import-type Decision from PolicyChangeStore
 */
final class EloquentPolicyChangeStore implements PolicyChangeStore
{
    private const string PROPOSE = 'policies.change';

    private const string APPROVE = 'policies.approve';

    private const string POLICY_VERSION = 'policy-changes-2026-11-26.1';

    /** Decided changes the board lists, newest first. */
    private const int HISTORY = 20;

    public function __construct(private AuthorizeStaffPermission $staff, private OperationJournal $journal) {}

    public function board(int $actorId): array
    {
        $this->staff->check($actorId, 'admin.open');
        $permissions = $this->staff->permissions($actorId);
        $settings = DB::table('policy_settings')->get()->keyBy('key');
        $pending = DB::table('policy_changes')->where('status', 'pending')->get()->keyBy('setting_key');
        $history = DB::table('policy_changes')->where('status', '<>', 'pending')->orderByDesc('decided_at')->orderByDesc('id')->limit(self::HISTORY)->get();
        $changes = $pending->values()->concat($history);
        $decisions = DB::table('policy_change_decisions')->whereIn('policy_change_id', $changes->pluck('id'))->orderBy('id')->get()->groupBy('policy_change_id');
        $names = User::query()->whereKey($changes->pluck('proposed_by_user_id')->concat($decisions->flatten()->pluck('user_id'))->unique()->all())->pluck('name', 'id');
        /** @return Change */
        $change = function (stdClass $row) use ($decisions, $names, $actorId): array {
            /** @var Collection<int, stdClass> $made */
            $made = $decisions[$row->id] ?? collect();

            return ['id' => (string) $row->id, 'key' => (string) $row->setting_key, 'from_value' => (int) $row->from_value, 'to_value' => (int) $row->to_value,
                'status' => (string) $row->status, 'reason' => (string) $row->reason, 'proposed_by' => (string) ($names[$row->proposed_by_user_id] ?? ''),
                'proposed_at' => self::time($row->created_at), 'decided_at' => $row->decided_at === null ? null : self::time($row->decided_at),
                'decisions' => array_values(array_map(fn (stdClass $decision): array => self::decisionRow($decision, (string) ($names[$decision->user_id] ?? '')),
                    $made->all())),
                'viewer_decided' => $made->contains(fn (stdClass $decision): bool => (int) $decision->user_id === $actorId)];
        };
        $rows = [];
        foreach (PolicyChange::SETTINGS as $key => $setting) {
            $stored = $settings[$key] ?? null;
            $rows[] = ['key' => $key, 'unit' => $setting['unit'], 'value' => $stored === null ? $setting['default'] : (int) $stored->value,
                'min' => $setting['min'], 'max' => $setting['max'], 'revision' => $stored === null ? 0 : (int) $stored->revision,
                'updated_at' => $stored === null ? '' : self::time($stored->updated_at),
                'pending' => isset($pending[$key]) ? $change($pending[$key]) : null];
        }

        return ['settings' => $rows, 'history' => array_values(array_map($change, $history->all())), 'approvals_needed' => PolicyChange::FOUNDER_APPROVALS,
            'can_propose' => in_array(self::PROPOSE, $permissions, true), 'can_approve' => in_array(self::APPROVE, $permissions, true)];
    }

    public function propose(int $actorId, string $key, string $value, string $reason, string $requestId): array
    {
        return $this->command($actorId, self::PROPOSE, 'policy.change.propose', $requestId, $key, $reason, ['key' => $key, 'value' => $value],
            function () use ($actorId, $key, $value, $reason): OperationResult {
                if (isset(PolicyChange::SETTINGS[$key])) {
                    DB::table('policy_settings')->insertOrIgnore(['key' => $key, 'value' => PolicyChange::SETTINGS[$key]['default'], 'revision' => 1, 'updated_at' => now()]);
                }
                $setting = DB::table('policy_settings')->where('key', $key)->lockForUpdate()->first();
                $problems = $setting === null ? ['key' => ['Choose a policy to change.']] : PolicyChange::problems($key, $value, (int) $setting->value);
                if ($problems !== []) {
                    throw new CommandRejection('POLICY_VALUE_INVALID', 422, fieldErrors: $problems);
                }
                if (DB::table('policy_changes')->where('setting_key', $key)->where('status', 'pending')->exists()) {
                    throw new CommandRejection('POLICY_CHANGE_PENDING', 409);
                }
                $id = strtolower((string) Str::ulid());
                DB::table('policy_changes')->insert(['id' => $id, 'setting_key' => $key, 'from_value' => (int) $setting->value,
                    'to_value' => PolicyChange::value($value), 'reason' => $reason, 'proposed_by_user_id' => $actorId, 'status' => 'pending', 'created_at' => now()]);

                return new OperationResult('POLICY_CHANGE_PROPOSED', ['policy_change_id' => $id, 'key' => $key, 'from_value' => (int) $setting->value,
                    'to_value' => PolicyChange::value($value), 'reason' => $reason], 1, [], self::POLICY_VERSION);
            });
    }

    public function decide(int $actorId, string $changeId, string $decision, string $reason, string $requestId): array
    {
        return $this->command($actorId, self::APPROVE, 'policy.change.decide', $requestId, $changeId, $reason, ['decision' => $decision],
            function () use ($actorId, $changeId, $decision, $reason): OperationResult {
                if (! in_array($decision, ['approve', 'reject'], true)) {
                    throw new CommandRejection('POLICY_DECISION_INVALID', 422);
                }
                $key = DB::table('policy_changes')->where('id', $changeId)->value('setting_key') ?? throw new CommandRejection('POLICY_CHANGE_NOT_FOUND', 404);
                $setting = DB::table('policy_settings')->where('key', $key)->lockForUpdate()->first();
                $change = DB::table('policy_changes')->where('id', $changeId)->lockForUpdate()->first();
                if ($setting === null || $change === null || $change->status !== 'pending') {
                    throw new CommandRejection('POLICY_CHANGE_CLOSED', 409);
                }
                if (DB::table('policy_change_decisions')->where('policy_change_id', $changeId)->where('user_id', $actorId)->exists()) {
                    throw new CommandRejection('POLICY_ALREADY_DECIDED', 409);
                }
                DB::table('policy_change_decisions')->insert(['policy_change_id' => $changeId, 'user_id' => $actorId, 'decision' => $decision,
                    'reason' => $reason, 'created_at' => now()]);
                /** @var list<'approve'|'reject'> $decisions */
                $decisions = DB::table('policy_change_decisions')->where('policy_change_id', $changeId)->pluck('decision')->all();
                $outcome = PolicyChange::outcome($decisions);
                if ($outcome !== 'pending') {
                    DB::table('policy_changes')->where('id', $changeId)->update(['status' => $outcome, 'decided_at' => now()]);
                }
                if ($outcome === 'applied') {
                    DB::table('policy_settings')->where('key', $key)->update(['value' => (int) $change->to_value, 'revision' => (int) $setting->revision + 1,
                        'updated_at' => now()]);
                }

                return new OperationResult('POLICY_CHANGE_DECIDED', ['policy_change_id' => $changeId, 'key' => (string) $key, 'decision' => $decision,
                    'outcome' => $outcome, 'from_value' => (int) $change->from_value, 'to_value' => (int) $change->to_value, 'reason' => $reason], 1, [], self::POLICY_VERSION);
            });
    }

    /**
     * Checks the staff member's current permission under its account lock, then records the change in
     * the journal, which keeps the reason and replays a repeated request instead of applying it twice.
     *
     * @param  array<string, mixed>  $input
     * @param  Closure(): OperationResult  $operation
     * @return array<string, mixed>
     */
    private function command(int $actorId, string $permission, string $command, string $requestId, string $targetId, string $reason, array $input, Closure $operation): array
    {
        if (trim($reason) === '' || mb_strlen($reason) > 1000) {
            return ['status' => 'rejected', 'code' => 'CHANGE_REASON_REQUIRED', 'field_errors' => ['reason' => ['Give the reason for this change, in at most 1,000 characters.']], 'http_status' => 422];
        }

        try {
            return DB::transaction(fn (): array => $this->staff->handle($actorId, $permission, fn (): array => $this->journal->execute(
                'staff:'.$actorId, $actorId, $command, $requestId, 'policy.change', $targetId, [...$input, 'reason' => $reason],
                static function (): void {},
                static function () use ($operation): OperationResult {
                    try {
                        return $operation();
                    } catch (CommandRejection $rejection) {
                        throw $rejection->underPolicy(self::POLICY_VERSION);
                    }
                })), 3);
        } catch (CommandRejection $exception) {
            return ['status' => 'rejected', 'code' => $exception->reason, 'field_errors' => $exception->fieldErrors, 'http_status' => $exception->status];
        } catch (IdentityViolation $exception) {
            return ['status' => 'rejected', 'code' => $exception->reason, 'field_errors' => [], 'http_status' => $exception->status];
        }
    }

    /** @return Decision */
    private static function decisionRow(stdClass $decision, string $by): array
    {
        return ['by' => $by, 'decision' => $decision->decision === 'reject' ? 'reject' : 'approve', 'reason' => (string) $decision->reason,
            'at' => self::time($decision->created_at)];
    }

    private static function time(mixed $value): string
    {
        return now()->parse((string) $value)->utc()->toIso8601String();
    }
}
