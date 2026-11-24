<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Business\OnboardBusiness;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\AccountClosures;
use App\Application\Identity\Contracts\PartyNotices;
use App\Application\Identity\Contracts\RoleApplicationStore;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\PartyNoticeKind;
use App\Domain\Identity\RevisionItems;
use App\Domain\Identity\RoleApplicationCase;
use App\Domain\Identity\VerificationDocument;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\RoleApplication;
use App\Models\RoleApplicationDocument;
use App\Models\RoleApplicationVersion;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type State from RoleApplicationCase
 * @phpstan-import-type Standing from RoleApplicationStore
 * @phpstan-import-type Queue from RoleApplicationStore
 * @phpstan-import-type Review from RoleApplicationStore
 * @phpstan-import-type Approval from RoleApplicationStore
 */
final class EloquentRoleApplicationStore implements RoleApplicationStore
{
    /** The KYB and auditor-licence rules each recorded step, upload and decision ran under. */
    private const string POLICY_VERSION = 'role-application-2026-11-23.1';

    /** The staff permission that reviews each role's applications. */
    private const array PERMISSIONS = ['business' => 'businesses.verify', 'auditor' => 'audit.partners.verify'];

    public function __construct(
        private OperationJournal $journal,
        private RoleApplicationCase $cases,
        private VerificationDocument $documents,
        private AuthorizeStaffPermission $staff,
        private AccountClosures $closures,
        private PartyNotices $notices,
    ) {}

    /** @return Standing */
    public function get(int $userId): array
    {
        return $this->own($userId, null, fn (User $user, Party $party, ?RoleApplication $record): array => [
            'revision' => $record->revision ?? 0, 'status' => $record->status ?? 'none', 'role' => $record?->role, 'state' => $record?->state,
            'kyc' => $this->kyc($party), 'roles' => $this->roles($party),
            'blockers' => array_values(array_unique(array_column($this->closures->investorBlockers($userId), 'code'))),
            'identity_context_revision' => $user->context_revision,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, int $expectedRevision, string $role, string $step, array $input, string $requestId): array
    {
        return $this->execute($userId, $contextRevision, $expectedRevision, 'application.save', $requestId, ['role' => $role, 'step' => $step, 'input' => $input],
            function (?RoleApplication $record) use ($role, $step, $input): array {
                $this->cases->role($role);
                $state = $record === null || $record->role !== $role ? $this->cases->empty($role) : $record->state;

                return $this->cases->save($state, $record->status ?? 'draft', $step, $input, $this->today(), $this->industries(), $this->districts());
            });
    }

    /** @return array<string, mixed> */
    public function upload(int $userId, int $contextRevision, int $expectedRevision, string $slot, string $filename, string $content, string $requestId): array
    {
        $documentId = strtolower((string) Str::ulid());
        $source = [];

        return $this->execute($userId, $contextRevision, $expectedRevision, 'application.upload', $requestId,
            ['slot' => $slot, 'filename_sha256' => hash('sha256', $filename), 'sha256' => hash('sha256', $content)],
            function (?RoleApplication $record) use ($slot, $filename, $content, $documentId, &$source): array {
                // The Auditor's first step takes the certificate with the licence, so its upload may start the application.
                if ($record === null && $slot !== 'practising_certificate') {
                    throw new CommandRejection('APPLICATION_STEP_OUT_OF_ORDER');
                }
                $state = $this->cases->upload($record->state ?? $this->cases->empty('auditor'), $record->status ?? 'draft', $slot, $documentId);
                $source = $this->documents->describe($filename, $content);

                return $state;
            },
            function (RoleApplication $record) use ($slot, $content, $documentId, $userId, &$source): void {
                (new RoleApplicationDocument)->forceFill([...$source, 'id' => $documentId, 'role_application_id' => $record->id,
                    'slot' => $slot, 'content' => $content, 'actor_user_id' => $userId])->save();
            });
    }

    /** @return array<string, mixed> */
    public function submit(int $userId, int $contextRevision, int $expectedRevision, bool $declared, string $requestId): array
    {
        return $this->execute($userId, $contextRevision, $expectedRevision, 'application.submit', $requestId, ['declared' => $declared],
            function (?RoleApplication $record) use ($declared): array {
                if ($record === null) {
                    throw new CommandRejection('APPLICATION_STEP_OUT_OF_ORDER');
                }

                return $this->cases->submit($record->state, $record->status, $declared, $this->today(), $this->industries(), $this->districts());
            }, submitted: true);
    }

    /** @return array<string, array{id: string, filename: string, media_type: string}> */
    public function uploads(int $userId): array
    {
        return $this->own($userId, null, function (User $user, Party $party, ?RoleApplication $record): array {
            $current = $record->state['documents'] ?? [];
            $documents = RoleApplicationDocument::query()->where('role_application_id', $record?->id)
                ->whereIn('id', array_values($current))->get(['id', 'filename', 'media_type'])->keyBy('id');
            $uploads = [];
            foreach ($current as $slot => $documentId) {
                $document = $documents->get($documentId);
                if ($document !== null) {
                    $uploads[$slot] = ['id' => (string) $documentId, 'filename' => $document->filename, 'media_type' => $document->media_type];
                }
            }

            return $uploads;
        });
    }

    /** @return array{filename: string, media_type: string, content: string} */
    public function ownDocument(int $userId, string $slot): array
    {
        return $this->own($userId, null, function (User $user, Party $party, ?RoleApplication $record) use ($slot): array {
            $documentId = $record?->state['documents'][$slot] ?? null;

            return $this->content($documentId === null ? null
                : RoleApplicationDocument::query()->where('role_application_id', $record?->id)->find($documentId));
        });
    }

    /** @return Queue */
    public function queue(int $actorId, string $role, string $tab, string $search, int $limit): array
    {
        $this->cases->role($role);
        $this->staff->check($actorId, self::PERMISSIONS[$role]);
        $tab = in_array($tab, ['submitted', 'changes_requested', 'approved', 'rejected'], true) ? $tab : 'submitted';
        $query = $this->withAccount(RoleApplication::query())->where('role', $role)->where('status', $tab);
        if ($search !== '') {
            $term = '%'.addcslashes(mb_strtolower($search), '%_\\').'%';
            $query->where(fn (Builder $match) => $match->whereLike('search_name', $term)->orWhereExists(fn ($users) => $users->selectRaw('1')->from('users')
                ->whereColumn('users.party_id', 'role_applications.party_id')
                ->where(fn ($inner) => $inner->whereLike('users.name', $term)->orWhereLike('users.email', $term))));
        }
        $tab === 'submitted' ? $query->orderBy('submitted_at')->orderBy('id') : $query->orderByDesc('updated_at')->orderByDesc('id');
        $counts = [];
        foreach (RoleApplication::query()->where('role', $role)->whereIn('status', ['submitted', 'changes_requested', 'approved', 'rejected'])
            ->groupBy('status')->selectRaw('status, count(*) AS total')->pluck('total', 'status') as $status => $total) {
            $counts[(string) $status] = (int) $total;
        }

        return ['role' => $role === 'auditor' ? 'auditor' : 'business', 'tab' => $tab, 'counts' => $counts,
            'entries' => array_values($query->limit($limit)->get()->map(fn (RoleApplication $record): array => [
                'id' => $record->id, 'revision' => $record->revision, 'role' => $record->role, 'status' => $record->status,
                'name' => (string) $record->getAttribute('account_name'), 'email' => (string) $record->getAttribute('account_email'),
                'company' => $record->state['answers']['company']['legal_name'] ?? null, 'company_code' => $record->state['answers']['company']['rdb_code'] ?? null,
                'submitted_at' => $record->submitted_at?->toIso8601String(), 'kyc' => $this->kyc(Party::query()->findOrFail($record->party_id)),
            ])->all())];
    }

    /**
     * @param  list<string>  $partyIds
     * @return array<string, array{id: string, role: string, status: string}>
     */
    public function forParties(array $partyIds): array
    {
        return RoleApplication::query()->whereIn('party_id', $partyIds)->where('status', '!=', 'draft')->get(['id', 'party_id', 'role', 'status'])
            ->mapWithKeys(fn (RoleApplication $record): array => [$record->party_id => ['id' => $record->id, 'role' => $record->role, 'status' => $record->status]])->all();
    }

    /** @return Review */
    public function show(int $actorId, string $applicationId): array
    {
        $record = $this->withAccount(RoleApplication::query())->find($applicationId) ?? throw new CommandRejection('APPLICATION_NOT_FOUND', 404);
        $this->staff->check($actorId, self::PERMISSIONS[$record->role]);
        $party = Party::query()->findOrFail($record->party_id);
        $userId = User::query()->where('party_id', $record->party_id)->orderBy('id')->value('id');
        $account = $userId === null ? null : User::query()->find((int) $userId);
        $current = array_values($record->state['documents']);

        return ['id' => $record->id, 'revision' => $record->revision, 'role' => $record->role, 'status' => $record->status,
            'submitted_at' => $record->submitted_at?->toIso8601String(), 'decided_at' => $record->decided_at?->toIso8601String(),
            'account' => ['name' => (string) $record->getAttribute('account_name'), 'email' => (string) $record->getAttribute('account_email'),
                'two_factor' => $account?->hasEnabledTwoFactorAuthentication() === true],
            'kyc' => $this->kyc($party), 'kyc_verification_id' => InvestorVerification::query()->where('party_id', $party->id)->value('id'),
            'state' => $record->state, 'people' => $this->cases->peopleById($record->state), 'revisable' => $this->cases->revisable($record->state),
            'documents' => array_values(RoleApplicationDocument::query()->where('role_application_id', $record->id)->orderBy('created_at')->orderBy('id')
                ->get(['id', 'slot', 'filename', 'media_type', 'size_bytes', 'created_at'])->map(fn (RoleApplicationDocument $document): array => [
                    'id' => $document->id, 'slot' => $document->slot, 'filename' => $document->filename, 'media_type' => $document->media_type,
                    'size_bytes' => $document->size_bytes, 'uploaded_at' => $document->created_at->toIso8601String(), 'current' => in_array($document->id, $current, true),
                ])->all()),
            'history' => array_values(RoleApplicationVersion::query()->where('role_application_id', $record->id)->orderBy('revision')->get()
                ->map(fn (RoleApplicationVersion $version): array => ['revision' => $version->revision, 'status' => $version->status,
                    'command' => $version->command, 'reason' => $version->reason, 'at' => $version->created_at->toIso8601String()])->all()),
            // What still stands in the way of approval only matters while it can be approved; on a decided
            // application it would show rules added since (QA #752) as if they blocked it.
            'blockers' => $userId === null || $record->status !== 'submitted' ? []
                : array_values(array_unique(array_column($this->closures->investorBlockers((int) $userId), 'code'))),
            'problems' => $record->status !== 'submitted' ? []
                : array_values(array_unique(array_merge(...array_values($this->cases->problems($record->state, $this->today(), $this->industries(), $this->districts()))))),
            'business_id' => $record->business_id,
            'allowed_actions' => $record->status === 'submitted' ? ['approve', 'more', 'reject'] : []];
    }

    /** @return array{filename: string, media_type: string, content: string} */
    public function document(int $actorId, string $applicationId, string $documentId): array
    {
        $role = RoleApplication::query()->whereKey($applicationId)->value('role');
        if (! is_string($role)) {
            throw new CommandRejection('APPLICATION_NOT_FOUND', 404);
        }
        $this->staff->check($actorId, self::PERMISSIONS[$role]);

        return $this->content(RoleApplicationDocument::query()->where('role_application_id', $applicationId)->find($documentId));
    }

    /** @return array<string, mixed> */
    public function decide(int $actorId, string $applicationId, int $expectedRevision, string $decision, string $note, string $requestId, Closure $switch, array $items = []): array
    {
        if (! in_array($decision, ['approve', 'more', 'reject'], true)) {
            return ['status' => 'rejected', 'code' => 'DECISION_INVALID', 'field_errors' => [], 'http_status' => 422];
        }
        $note = trim($note);
        if (($decision === 'reject' && $note === '') || mb_strlen($note) > 1000) {
            return ['status' => 'rejected', 'code' => 'DECISION_NOTE_REQUIRED', 'field_errors' => ['note' => ['Tell the applicant why, in at most 1,000 characters.']], 'http_status' => 422];
        }
        if ($decision === 'more' && $items === []) {
            return ['status' => 'rejected', 'code' => 'REVISION_ITEMS_REQUIRED', 'field_errors' => ['items' => ['Tick at least one answer or document the applicant must change.']], 'http_status' => 422];
        }
        $role = RoleApplication::query()->whereKey($applicationId)->value('role');
        if (! is_string($role)) {
            return ['status' => 'rejected', 'code' => 'APPLICATION_NOT_FOUND', 'field_errors' => [], 'http_status' => 404];
        }
        $reason = $note !== '' ? $note : ($decision === 'approve' ? 'Approved after reviewing the application and its documents.' : '');
        try {
            return DB::transaction(function () use ($actorId, $applicationId, $expectedRevision, $decision, $reason, $requestId, $switch, $role, $items): array {
                $partyId = (string) RoleApplication::query()->whereKey($applicationId)->value('party_id');
                $participant = $this->participant($partyId);
                User::query()->whereKey(array_filter([$actorId, $participant]))->orderBy('id')->lockForUpdate()->get();

                return $this->staff->handle($actorId, self::PERMISSIONS[$role], fn (): array => $this->journal->execute('staff:'.$actorId, $actorId,
                    'role.application.'.$decision, $requestId, 'role.application', $applicationId,
                    ['expected_revision' => $expectedRevision, 'reason' => $reason, 'items' => $items], static function (): void {},
                    fn (): OperationResult => $this->underPolicy(fn (): OperationResult => $this->decided($actorId, $applicationId, $expectedRevision, $decision, $reason, $requestId, $participant, $switch, $items))));
            }, 3);
        } catch (CommandRejection $exception) {
            return ['status' => 'rejected', 'code' => $exception->reason, 'field_errors' => $exception->fieldErrors, 'http_status' => $exception->status];
        } catch (IdentityViolation $violation) {
            return ['status' => 'rejected', 'code' => $violation->reason, 'field_errors' => [], 'http_status' => $violation->status];
        }
    }

    /**
     * @param  Closure(Approval): (string|null)  $switch
     * @param  list<mixed>  $items
     */
    private function decided(int $actorId, string $applicationId, int $expectedRevision, string $decision, string $reason, string $requestId, ?int $participant, Closure $switch, array $items): OperationResult
    {
        $record = RoleApplication::query()->whereKey($applicationId)->lockForUpdate()->first() ?? throw new CommandRejection('APPLICATION_NOT_FOUND', 404);
        if ($record->revision !== $expectedRevision) {
            throw new CommandRejection('VERSION_CONFLICT', 409, $record->revision);
        }
        if ($record->status !== 'submitted') {
            throw new CommandRejection('APPLICATION_NOT_SUBMITTED', 409, $record->revision);
        }
        if ($participant === null || $this->participant($record->party_id) !== $participant) {
            throw new CommandRejection('IDENTITY_RECONCILIATION_REQUIRED', 409, $record->revision);
        }
        $party = Party::query()->lockForUpdate()->findOrFail($record->party_id);
        $state = $record->state;
        $businessId = null;
        if ($decision === 'approve') {
            if ($this->kyc($party) !== 'approved') {
                throw new CommandRejection('KYC_APPROVAL_REQUIRED', 409, $record->revision);
            }
            if ($this->cases->problems($state, $this->today(), $this->industries(), $this->districts()) !== []) {
                throw new CommandRejection('APPLICATION_INCOMPLETE', 409, $record->revision);
            }
            if ($this->closures->investorBlockers($participant) !== []) {
                throw new CommandRejection('INVESTOR_POSITIONS_OPEN', 409, $record->revision);
            }
            $documents = RoleApplicationDocument::query()->where('role_application_id', $record->id)->whereIn('id', array_values($state['documents']))->get()->keyBy('id');
            foreach ($state['documents'] as $documentId) {
                $document = $documents->get($documentId);
                if ($document === null || ! hash_equals($document->sha256, hash('sha256', $document->content))) {
                    throw new CommandRejection('APPLICATION_DOCUMENT_INTEGRITY_FAILED', 409, $record->revision);
                }
            }
            $certificate = $record->role === 'auditor' ? $documents->get($state['documents']['practising_certificate']) : null;
            $businessId = $switch(['party_id' => $party->id, 'user_id' => $participant, 'email' => (string) User::query()->whereKey($participant)->value('email'),
                'state' => $state, 'reference' => 'role-application:'.$record->id.'@'.$record->revision, 'request_id' => $requestId,
                'certificate' => $certificate === null ? null : ['filename' => $certificate->filename, 'content' => $certificate->content]]);
        } else {
            $picked = $decision === 'more' ? RevisionItems::pick($items, $this->cases->revisable($state)) : [];
            $reason = $reason !== '' ? $reason : RevisionItems::summary($picked);
            $state = $this->cases->returned($state, $reason, $picked, now()->toIso8601String());
        }
        $status = match ($decision) {
            'approve' => 'approved',
            'more' => 'changes_requested',
            default => 'rejected',
        };
        $record->forceFill(['revision' => $expectedRevision + 1, 'status' => $status, 'state' => $state, 'decided_at' => now(),
            'business_id' => $businessId])->save();
        (new RoleApplicationVersion)->forceFill(['role_application_id' => $record->id, 'revision' => $record->revision, 'status' => $status,
            'snapshot' => $state, 'actor_user_id' => $actorId, 'command' => 'application.'.$decision, 'reason' => $reason,
            'policy_version' => self::POLICY_VERSION])->save();
        $this->notices->record($party->id, match ($decision) {
            'approve' => PartyNoticeKind::RoleApplicationApproved,
            'more' => PartyNoticeKind::RoleApplicationReturned,
            default => PartyNoticeKind::RoleApplicationRejected,
        }, [...$this->noticeFacts($state), ...($decision === 'approve' ? [] : ['note' => $reason])],
            'role-application:'.$record->id.'@'.$record->revision, null,
            $decision === 'more' ? array_map(fn (array $item): array => ['label' => $item['label'], 'note' => $item['note']], $state['revision']['items'] ?? []) : []);

        return new OperationResult(match ($decision) {
            'approve' => 'APPLICATION_APPROVED',
            'more' => 'APPLICATION_RETURNED',
            default => 'APPLICATION_REJECTED',
        }, ['application_id' => $record->id, 'status' => $status, 'business_id' => $businessId], $record->revision, [], self::POLICY_VERSION);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(RoleApplication|null): State  $change
     * @param  Closure(RoleApplication): void|null  $afterSave
     * @return array<string, mixed>
     */
    private function execute(int $userId, int $contextRevision, int $expectedRevision, string $command, string $requestId, array $input, Closure $change, ?Closure $afterSave = null, bool $submitted = false): array
    {
        try {
            return $this->own($userId, $contextRevision, fn (User $user, Party $party, ?RoleApplication $record): array => $this->journal->execute(
                'party:'.$party->id, $userId, $command, $requestId, 'role.application', $party->id,
                ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, ...$input],
                static function (): void {},
                fn (): OperationResult => $this->underPolicy(function () use ($user, $party, $record, $userId, $expectedRevision, $command, $change, $afterSave, $submitted): OperationResult {
                    if (! in_array($this->kyc($party), ['submitted', 'approved'], true)) {
                        throw new CommandRejection('KYC_NOT_SENT');
                    }
                    if (array_intersect(['business', 'auditor'], $this->roles($party)) !== []) {
                        throw new CommandRejection('ROLE_ALREADY_HELD');
                    }
                    if (($record->revision ?? 0) !== $expectedRevision) {
                        throw new CommandRejection('VERSION_CONFLICT', 409, $record->revision ?? 0);
                    }
                    $state = $change($record);
                    if ($submitted && $state['role'] === 'auditor' && ! $user->hasEnabledTwoFactorAuthentication()) {
                        throw new CommandRejection('TWO_FACTOR_REQUIRED');
                    }
                    $record ??= new RoleApplication;
                    $record->forceFill(['party_id' => $party->id, 'role' => $state['role'], 'revision' => $expectedRevision + 1, 'state' => $state,
                        'status' => $submitted ? 'submitted' : ($record->status ?? 'draft'), 'submitted_at' => $submitted ? now() : $record->submitted_at,
                        'search_name' => mb_strtolower($state['answers']['company']['legal_name'] ?? $user->name)])->save();
                    if ($afterSave !== null) {
                        $afterSave($record);
                    }
                    (new RoleApplicationVersion)->forceFill(['role_application_id' => $record->id, 'revision' => $record->revision,
                        'status' => $record->status, 'snapshot' => $state, 'actor_user_id' => $userId, 'command' => $command,
                        'policy_version' => self::POLICY_VERSION])->save();
                    if ($submitted) {
                        $this->notices->record($party->id, PartyNoticeKind::RoleApplicationSubmitted, $this->noticeFacts($state),
                            'role-application:'.$record->id.'@'.$record->revision);
                    }

                    return new OperationResult($submitted ? 'APPLICATION_SUBMITTED' : 'APPLICATION_SAVED',
                        ['application_id' => $record->id, 'role' => $record->role, 'status' => $record->status, 'step' => $state['step']], $record->revision, [], self::POLICY_VERSION);
                })));
        } catch (CommandRejection $exception) {
            return ['status' => 'rejected', 'code' => $exception->reason, 'field_errors' => $exception->fieldErrors, 'http_status' => $exception->status];
        }
    }

    /**
     * @param  Closure(): OperationResult  $operation
     */
    private function underPolicy(Closure $operation): OperationResult
    {
        try {
            return $operation();
        } catch (CommandRejection $rejection) {
            throw $rejection->underPolicy(self::POLICY_VERSION);
        } catch (IdentityViolation $violation) {
            throw (new CommandRejection($violation->reason, $violation->status))->underPolicy(self::POLICY_VERSION);
        }
    }

    /**
     * What every notice about an application names: the role, and the company for a business.
     *
     * @param  State  $state
     * @return array{role: 'business'|'auditor', company?: string}
     */
    private function noticeFacts(array $state): array
    {
        $company = $state['answers']['company']['legal_name'] ?? null;

        $facts = ['role' => $state['role']];
        if ($state['role'] === 'business' && is_string($company) && $company !== '') {
            $facts['company'] = $company;
        }

        return $facts;
    }

    /** @return array{filename: string, media_type: string, content: string} */
    private function content(?RoleApplicationDocument $document): array
    {
        if ($document === null) {
            throw new CommandRejection('APPLICATION_DOCUMENT_NOT_FOUND', 404);
        }
        if (! hash_equals($document->sha256, hash('sha256', $document->content))) {
            throw new CommandRejection('APPLICATION_DOCUMENT_INTEGRITY_FAILED', 409);
        }

        return ['filename' => $document->filename, 'media_type' => $document->media_type, 'content' => $document->content];
    }

    /** Where the person's own identity check stands: approved once their Party is verified. */
    /** @return 'none'|'draft'|'submitted'|'approved'|'rejected' */
    private function kyc(Party $party): string
    {
        if ($party->verified_at !== null && $party->verified_at->lte(now()) && $party->verifiedIdentity()->exists()) {
            return 'approved';
        }
        $status = InvestorVerification::query()->where('party_id', $party->id)->value('status');

        return match ($status) {
            'draft' => 'draft',
            'submitted' => 'submitted',
            'rejected' => 'rejected',
            default => 'none',
        };
    }

    /** @return list<string> */
    private function roles(Party $party): array
    {
        return array_values($party->memberships()->where('status', '!=', 'revoked')->orderBy('role')->pluck('role')->map(fn (mixed $role): string => (string) $role)->all());
    }

    /** The one account an application belongs to, or null when there is none or more than one. */
    private function participant(string $partyId): ?int
    {
        $users = User::query()->where('party_id', $partyId)->pluck('id');

        return $users->count() === 1 ? (int) $users->first() : null;
    }

    private function today(): string
    {
        return now()->setTimezone('Africa/Kigali')->format('Y-m-d');
    }

    /** @return list<string> */
    private function industries(): array
    {
        return OnboardBusiness::sectors();
    }

    /** @return array<string, list<string>> the districts of each province */
    private function districts(): array
    {
        /** @var array<string, list<string>> $provinces */
        $provinces = (array) config('rwanda.districts', []);

        return $provinces;
    }

    /**
     * @param  Builder<RoleApplication>  $query
     * @return Builder<RoleApplication>
     */
    private function withAccount(Builder $query): Builder
    {
        $account = fn (string $column) => User::query()->select($column)->whereColumn('users.party_id', 'role_applications.party_id')->orderBy('id')->limit(1);

        return $query->select('role_applications.*')->addSelect(['account_name' => $account('name'), 'account_email' => $account('email')]);
    }

    /**
     * The account, then its Party, then the application: the identity writers' lock prefix.
     *
     * @template TResult
     *
     * @param  Closure(User, Party, RoleApplication|null): TResult  $operation
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

            return $operation($user, $party, RoleApplication::query()->where('party_id', $party->id)->lockForUpdate()->first());
        }, 3);
    }
}
