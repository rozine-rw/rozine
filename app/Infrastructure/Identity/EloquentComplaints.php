<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\Complaints;
use App\Application\Identity\Contracts\PartyNotices;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\Complaint as ComplaintRules;
use App\Domain\Identity\PartyNoticeKind;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The complaints adapter. A customer's write is journaled under their Party and a staff write
 * under the staff member; the permitted input keeps only a digest of the text, which is personal
 * data. Each write locks the complaint, so a message never lands after its resolution. Every staff
 * message records an in-app notice for the complaint's Party: answered, or resolved.
 *
 * @phpstan-import-type Thread from Complaints
 * @phpstan-import-type StaffThread from Complaints
 * @phpstan-import-type Overdue from Complaints
 */
final class EloquentComplaints implements Complaints
{
    public function __construct(private AuthorizeActiveRole $authority, private AuthorizeStaffPermission $staff, private OperationJournal $journal,
        private PartyNotices $notices) {}

    public function open(int $userId, string $requestId, string $body): array
    {
        return $this->authority->handle($userId, 'investor', null, null, function (array $identity) use ($userId, $requestId, $body): array {
            $partyId = (string) $identity['party']['id'];

            return $this->journal->execute('party:'.$partyId, $userId, 'complaint.open', $requestId, 'party', $partyId, ['body_sha256' => hash('sha256', $body)],
                function (): void {}, function (string $operationId) use ($partyId, $userId, $body): OperationResult {
                    $this->admissible($body);
                    $at = CarbonImmutable::now('UTC');
                    $complaint = new Complaint;
                    $id = $complaint->newUniqueId();
                    $complaint->forceFill(['id' => $id, 'reference' => ComplaintRules::reference($id), 'party_id' => $partyId, 'role' => 'investor',
                        'channel' => 'in_app', 'opened_by_user_id' => $userId, 'operation_id' => $operationId, ...ComplaintRules::clocks($at),
                        'created_at' => $at])->save();
                    $this->message($complaint, 'customer', $userId, false, $body, $operationId, $at);

                    return new OperationResult('COMPLAINT_OPENED', ['complaint_id' => $complaint->id, 'reference' => $complaint->reference], 1);
                });
        });
    }

    public function reply(int $userId, string $requestId, string $complaintId, string $body): array
    {
        return $this->authority->handle($userId, 'investor', null, null, function (array $identity) use ($userId, $requestId, $complaintId, $body): array {
            $partyId = (string) $identity['party']['id'];

            return $this->journal->execute('party:'.$partyId, $userId, 'complaint.reply', $requestId, 'complaint', $complaintId,
                ['body_sha256' => hash('sha256', $body)], function (): void {}, function (string $operationId) use ($partyId, $userId, $complaintId, $body): OperationResult {
                    $complaint = $this->openComplaint(Complaint::query()->where('party_id', $partyId)->where('role', 'investor'), $complaintId);
                    $this->admissible($body);
                    $this->message($complaint, 'customer', $userId, false, $body, $operationId, CarbonImmutable::now('UTC'));

                    return new OperationResult('COMPLAINT_REPLIED', ['complaint_id' => $complaint->id, 'reference' => $complaint->reference], 1);
                });
        });
    }

    public function forInvestor(int $userId): array
    {
        $partyId = User::query()->whereKey($userId)->value('party_id');
        if (! is_string($partyId)) {
            return [];
        }

        return array_values(array_map(fn (Complaint $complaint): array => $this->thread($complaint),
            Complaint::query()->with('messages')->where('party_id', $partyId)->where('role', 'investor')->orderByDesc('created_at')->orderByDesc('id')->get()->all()));
    }

    public function answer(int $staffId, string $requestId, string $complaintId, string $body, bool $resolves): array
    {
        try {
            return DB::transaction(fn (): array => $this->staff->handle($staffId, 'complaints.answer', fn (): array => $this->journal->execute('staff:'.$staffId, $staffId,
                $resolves ? 'complaint.resolve' : 'complaint.answer', $requestId, 'complaint', $complaintId, ['body_sha256' => hash('sha256', $body)],
                static function (): void {}, function (string $operationId) use ($staffId, $complaintId, $body, $resolves): OperationResult {
                    $complaint = $this->openComplaint(Complaint::query(), $complaintId);
                    $this->admissible($body);
                    $message = $this->message($complaint, 'staff', $staffId, $resolves, $body, $operationId, CarbonImmutable::now('UTC'));
                    $this->notices->record($complaint->party_id, $resolves ? PartyNoticeKind::ComplaintResolved : PartyNoticeKind::ComplaintAnswered,
                        ['reference' => $complaint->reference], 'complaint-message:'.$message->id);

                    return new OperationResult($resolves ? 'COMPLAINT_RESOLVED' : 'COMPLAINT_ANSWERED', ['complaint_id' => $complaint->id,
                        'reference' => $complaint->reference], 1);
                })), 3);
        } catch (CommandRejection $exception) {
            return ['status' => 'rejected', 'code' => $exception->reason, 'field_errors' => $exception->fieldErrors, 'http_status' => $exception->status];
        }
    }

    public function all(): array
    {
        $complaints = Complaint::query()->with('messages')->orderByDesc('created_at')->orderByDesc('id')->get();
        $names = User::query()->whereKey($complaints->pluck('opened_by_user_id')->unique()->all())->pluck('name', 'id');
        $threads = array_map(fn (Complaint $complaint): array => [...$this->thread($complaint), 'party_id' => $complaint->party_id, 'role' => $complaint->role,
            'customer' => (string) ($names[$complaint->opened_by_user_id] ?? 'Customer')], $complaints->all());
        usort($threads, fn (array $a, array $b): int => ($a['status'] === 'resolved') <=> ($b['status'] === 'resolved'));

        return $threads;
    }

    public function overdue(DateTimeImmutable $at): array
    {
        $staffed = ComplaintMessage::query()->select('complaint_id')->where('author', 'staff');
        $resolved = ComplaintMessage::query()->select('complaint_id')->where('resolves', true);
        $complaints = Complaint::query()->join('users', 'users.id', '=', 'complaints.opened_by_user_id')
            ->whereNotIn('complaints.id', $resolved)
            ->where(fn (Builder $query) => $query->where(fn (Builder $late) => $late->whereNotIn('complaints.id', $staffed)->where('complaints.acknowledge_by', '<=', $at))
                ->orWhere('complaints.resolve_by', '<=', $at))
            ->orderBy('complaints.created_at')->orderBy('complaints.id')
            ->get(['complaints.id', 'complaints.reference', 'complaints.party_id', 'complaints.role', 'complaints.acknowledge_by', 'complaints.resolve_by', 'users.name']);
        $acknowledged = ComplaintMessage::query()->where('author', 'staff')->whereIn('complaint_id', $complaints->pluck('id'))->pluck('complaint_id')->unique()->flip();

        return array_values(array_map(function (Complaint $complaint) use ($acknowledged): array {
            $stage = isset($acknowledged[$complaint->id]) ? 'resolve' : 'acknowledge';

            return ['id' => $complaint->id, 'reference' => $complaint->reference, 'party_id' => $complaint->party_id, 'role' => $complaint->role,
                'customer' => (string) $complaint->getAttribute('name'), 'stage' => $stage,
                'due_at' => ($stage === 'resolve' ? $complaint->resolve_by : $complaint->acknowledge_by)->toIso8601String()];
        }, $complaints->all()));
    }

    /**
     * The complaint, locked, while it can still take a message.
     *
     * @param  Builder<Complaint>  $scope
     */
    private function openComplaint(Builder $scope, string $complaintId): Complaint
    {
        $complaint = $scope->whereKey(strtolower($complaintId))->lockForUpdate()->first() ?? throw new CommandRejection('COMPLAINT_NOT_FOUND', 404);
        if (ComplaintMessage::query()->where('complaint_id', $complaint->id)->where('resolves', true)->exists()) {
            throw new CommandRejection('COMPLAINT_RESOLVED', 409);
        }

        return $complaint;
    }

    private function admissible(string $body): void
    {
        $refusal = ComplaintRules::refusal($body);
        if ($refusal !== null) {
            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['body' => [$refusal]]);
        }
    }

    /** @param  'customer'|'staff'  $author */
    private function message(Complaint $complaint, string $author, int $userId, bool $resolves, string $body, string $operationId, CarbonImmutable $at): ComplaintMessage
    {
        $message = new ComplaintMessage;
        $message->forceFill(['complaint_id' => $complaint->id, 'author' => $author, 'author_user_id' => $userId, 'resolves' => $resolves, 'body' => $body,
            'operation_id' => $operationId, 'created_at' => $at])->save();

        return $message;
    }

    /** @return Thread */
    private function thread(Complaint $complaint): array
    {
        $messages = $complaint->messages;
        $acknowledged = $messages->contains(fn (ComplaintMessage $message): bool => $message->author === 'staff');
        $resolved = $messages->contains(fn (ComplaintMessage $message): bool => $message->resolves);

        return ['id' => $complaint->id, 'reference' => $complaint->reference, 'status' => ComplaintRules::status($acknowledged, $resolved),
            'opened_at' => $complaint->created_at->toIso8601String(), 'acknowledge_by' => $complaint->acknowledge_by->toIso8601String(),
            'resolve_by' => $complaint->resolve_by->toIso8601String(),
            'messages' => array_values(array_map(fn (ComplaintMessage $message): array => ['id' => $message->id, 'author' => $message->author,
                'body' => $message->body, 'resolves' => $message->resolves, 'at' => $message->created_at->toIso8601String()], $messages->all()))];
    }
}
