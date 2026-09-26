<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\Contracts\AuditSourceFactsStore;
use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Evidence\Contracts\StatementStore;
use App\Application\Evidence\WithBusinessStatementVerification;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AuditReportWindow;
use App\Domain\Auditor\MonthlyReportReview;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditDisputeProof;
use App\Models\AuditPublicationEvent;
use App\Models\AuditReport;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\AuditReportSignature;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 * @phpstan-import-type Verification from \App\Application\Evidence\Contracts\StatementStore
 */
final class EloquentAuditReportPublicationStore implements AuditReportPublicationStore
{
    public function __construct(private WithBusinessStatementVerification $evidence, private WithBusinessAuthority $authority,
        private IdentityRepository $identities, private AuditAssignmentStore $assignments, private AuditReportCryptography $cryptography,
        private OperationJournal $journal, private CanonicalJson $json, private AuditReportWindow $window, private AuditSourceFactsStore $sourceFacts,
        private MonthlyReportReview $reviewPolicy, private StatementStore $statements,
        private BusinessAuthorityStore $businesses, private AuthorizeStaffPermission $staff) {}

    /** @param array<string, mixed> $payload */
    public function open(string $reportId, string $digest, array $payload): void
    {
        $this->completeAmendment($reportId);
        $monthly = ($payload['publication_policy'] ?? null) === MonthlyReportReview::POLICY;
        $deliveredAt = $monthly ? CarbonImmutable::parse($payload['sealed_at']) : null;
        $publication = new AuditReportPublication;
        $publication->forceFill(['policy_version' => $monthly ? MonthlyReportReview::POLICY : MonthlyReportReview::LEGACY_POLICY,
            'delivered_at' => $deliveredAt, 'due_at' => $deliveredAt?->addHours(24), 'audit_report_id' => $reportId, 'business_id' => $payload['business']['id'],
            'mandate_version' => $payload['business']['mandate_version'], 'report_revision' => $payload['sealed_revision'],
            'digest' => $digest, 'revision' => 1, 'status' => 'pending'])->save();
        if ($monthly) {
            $this->event($publication, 'report.delivered', 'system', null, null,
                ['delivered_at' => $payload['sealed_at'], 'due_at' => $publication->due_at?->toIso8601String()], 1);
        }
    }

    /** @return array{id: string, kind: string, status: string}|null */
    public function latestForBusiness(string $businessId): ?array
    {
        $report = AuditReport::query()->join('audit_report_publications as publications', 'publications.audit_report_id', '=', 'audit_reports.id')
            ->where('audit_reports.business_id', $businessId)->where('audit_reports.status', 'sealed')
            ->whereNotExists(fn (Builder $query): Builder => $query->selectRaw('1')->from('audit_reports as amendments')
                ->whereColumn('amendments.amends_id', 'audit_reports.id'))
            ->orderByDesc('audit_reports.id')->first(['audit_reports.id', 'audit_reports.kind', 'publications.status as publication_status']);

        return $report === null ? null : ['id' => $report->id, 'kind' => $report->kind, 'status' => $report->getAttribute('publication_status')];
    }

    /** @return array<string, mixed> */
    public function get(int $userId, int $contextRevision, string $businessId, string $reportId): array
    {
        return $this->scope($userId, $contextRevision, $businessId, $reportId, 'business.view',
            fn (AuditReportPublication $publication, AuditReportSeal $seal, array $business, string $partyId, bool $current): array => $this->project($publication, $seal, $business, $partyId, $current));
    }

    /** @return array<string, mixed> */
    public function cosign(int $userId, int $contextRevision, string $businessId, string $reportId, int $expectedRevision, int $reportRevision, int $mandateVersion, string $digest, bool $accepted, string $note, string $requestId): array
    {
        return $this->scope($userId, $contextRevision, $businessId, $reportId, 'report.cosign',
            function (AuditReportPublication $publication, AuditReportSeal $seal, array $business, string $partyId, bool $current) use ($userId, $contextRevision, $expectedRevision, $reportRevision, $mandateVersion, $digest, $accepted, $note, $requestId): array {
                $this->requireSigner($business, $partyId);
                $input = ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision,
                    'report_revision' => $reportRevision, 'mandate_version' => $mandateVersion, 'digest' => $digest, 'accepted' => $accepted, 'note' => $note];
                try {
                    $this->json->encode($input);
                } catch (CommandRejection) {
                    $input = ['invalid_input_sha256' => hash('sha256', serialize($input))];
                }

                return $this->journal->execute('party:'.$partyId, $userId, 'report.cosign', $requestId, 'audit.publication', $publication->id,
                    $input, function (): void {}, function () use ($publication, $seal, $business, $partyId, $userId, $current, $expectedRevision, $reportRevision, $mandateVersion, $digest, $accepted, $note): OperationResult {
                        if ($publication->revision !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', revision: $publication->revision);
                        }
                        if ($publication->report_revision !== $reportRevision || ! hash_equals($publication->digest, $digest)) {
                            throw new CommandRejection('DIGEST_STALE', revision: $publication->revision);
                        }
                        if ($publication->mandate_version !== $mandateVersion || $business['mandate_version'] !== $mandateVersion) {
                            throw new CommandRejection('MANDATE_STALE', revision: $publication->revision);
                        }
                        if (AuditReport::query()->where('amends_id', $publication->audit_report_id)->exists()) {
                            throw new CommandRejection('AUDIT_REPORT_AMENDED', revision: $publication->revision);
                        }
                        if (! $accepted) {
                            throw new CommandRejection('REPORT_ACCEPTANCE_REQUIRED', 422, $publication->revision, ['accepted' => ['Confirm this sealed report before co-signing.']]);
                        }
                        $note = str_replace(["\r\n", "\r"], "\n", $note);
                        if (! mb_check_encoding($note, 'UTF-8') || mb_strlen($note) > 100 || preg_match('/[^\P{Cc}\n\t]|[\p{Cf}\x{2028}\x{2029}]/u', $note)) {
                            throw new CommandRejection('REPORT_NOTE_INVALID', 422, $publication->revision, ['note' => ['Use plain text of at most 100 characters.']]);
                        }
                        $existing = AuditReportSignature::query()->where('audit_report_publication_id', $publication->id)->where('actor_party_id', $partyId)->first();
                        if ($existing !== null) {
                            return $this->receipt($publication, $existing);
                        }
                        if (! $current || ! $this->cryptography->verify($seal->audit_signing_key_id, $seal->jws, $seal->payload)) {
                            throw new CommandRejection('AUDIT_PUBLICATION_UNAVAILABLE', revision: $publication->revision);
                        }
                        $at = now('UTC')->toImmutable();
                        if ($this->monthly($publication)) {
                            $this->reviewPolicy->requireOpen($publication->status, $publication->due_at->toIso8601String(), $at->toDateTimeImmutable());
                        }
                        if (! $this->monthly($publication) && ! $this->window->mayPublish($seal->payload['report']['period'], $at->toDateTimeImmutable())) {
                            throw new CommandRejection('REPORT_WINDOW_CLOSED', revision: $publication->revision);
                        }
                        $payload = ['report_id' => $publication->audit_report_id, 'report_revision' => $reportRevision, 'digest' => $digest,
                            'mandate_version' => $mandateVersion, 'mandate_sha256' => hash('sha256', $this->json->encode($business['mandate'])),
                            'actor_party_id' => $partyId, 'actor_user_id' => $userId, 'accepted' => true, 'note' => $note,
                            'signed_at' => $at->format('Y-m-d\TH:i:s\Z')];
                        $signature = new AuditReportSignature;
                        $signature->forceFill(['audit_report_publication_id' => $publication->id, 'publication_revision' => $publication->revision + 1,
                            'actor_party_id' => $partyId, 'actor_user_id' => $userId, 'payload' => $payload,
                            'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $at])->save();
                        $signed = AuditReportSignature::query()->where('audit_report_publication_id', $publication->id)->pluck('actor_party_id')->all();
                        $complete = $this->monthly($publication) || array_diff($business['mandate']['required_signatories'], $signed) === [];
                        if ($this->monthly($publication)) {
                            $this->event($publication, 'report.cosign', 'party', $userId, $partyId, ['signature_id' => $signature->id, 'published_reason' => 'signed'], at: $at);
                            $publication->published_reason = 'signed';
                        }
                        $publication->forceFill(['revision' => $publication->revision + 1, 'status' => $complete ? 'published' : 'pending',
                            'published_at' => $complete ? $at : null])->save();

                        return $this->receipt($publication, $signature);
                    });
            });
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId, string $command = 'report.cosign'): array
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new CommandRejection('OPERATION_NOT_FOUND', 404);

        if (! in_array($command, ['report.cosign', 'report.dispute'], true)) {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }

        return $this->journal->find('party:'.$partyId, $command, $requestId,
            function (string $type, string $id) use ($userId, $contextRevision, $partyId): void {
                $publication = $type === 'audit.publication' ? AuditReportPublication::query()->find($id) : null;
                if ($publication === null) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->authority->handle($userId, $contextRevision, $publication->business_id, 'report.cosign', null,
                    function (array $business, array $identity) use ($partyId): void {
                        if (($identity['party']['id'] ?? null) !== $partyId) {
                            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                        }
                        $this->requireSigner($business, $partyId);
                    });
            });
    }

    /** @return array<string, mixed> */
    public function forAuditor(string $reportId): array
    {
        $publication = AuditReportPublication::query()->where('audit_report_id', $reportId)->firstOrFail();
        $seal = $this->seal($publication);
        $due = $this->monthly($publication) ? $publication->due_at->toIso8601String() : $this->window->dueAt($seal->payload['report']['period']);

        return ['policy_version' => $publication->policy_version, 'delivered_at' => $publication->delivered_at?->toIso8601String(),
            'published_reason' => $publication->published_reason, 'sealed_at' => $seal->payload['sealed_at'], 'digest' => $seal->digest, 'licence' => $seal->payload['report']['licence'],
            'signature_ref' => $seal->id, 'report_id' => $reportId, 'key_id' => $seal->audit_signing_key_id,
            'seal_status' => $this->validSeal($seal) ? 'valid' : 'unavailable',
            'dispute' => $this->reviewData($publication), 'can_dispute_uphold' => $this->monthly($publication) && $publication->status === 'disputed',
            'cosign' => [...$this->reviewFacts($publication), 'party' => $seal->payload['business']['profile']['name'], 'state' => $publication->status === 'published' ? ($this->monthly($publication) && $publication->published_reason !== 'signed' ? 'pending' : 'signed') : ($publication->status === 'pending' && $due !== null && $due < now('UTC')->format('Y-m-d\TH:i:s\Z') ? 'overdue' : 'pending'),
                'due_on' => $due, 'signed_at' => $this->monthly($publication) && $publication->published_reason !== 'signed' ? null : $publication->published_at?->toIso8601String()],
            'published_at' => $publication->published_at?->toIso8601String(), 'sources' => $seal->payload['sources']];
    }

    /** @return array<string, mixed> */
    public function verify(string $reportId): array
    {
        $seal = AuditReportSeal::query()->where('audit_report_id', $reportId)->first() ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);
        $report = AuditReport::query()->findOrFail($reportId);
        $child = AuditReport::query()->where('amends_id', $reportId)->whereIn('id', AuditReportSeal::query()->select('audit_report_id'))->value('id');

        return ['report_id' => $reportId, 'digest' => $seal->digest,
            'seal_status' => $this->validSeal($seal) ? 'valid' : 'unavailable',
            'amends_id' => $report->amends_id !== null && AuditReportSeal::query()->where('audit_report_id', $report->amends_id)->exists() ? $report->amends_id : null,
            'amended_by' => $child];
    }

    /**
     * @template TResult
     *
     * @param  Closure(AuditReportPublication, AuditReportSeal, Business, string, bool): TResult  $operation
     * @return TResult
     */
    private function scope(int $userId, int $contextRevision, string $businessId, string $reportId, string $permission, Closure $operation): mixed
    {
        return $this->evidence->handle($userId, $contextRevision, $businessId, $permission, null,
            function (array $business, array $identity, ?array $verification) use ($reportId, $operation): mixed {
                AuditReport::query()->whereKey($reportId)->where('business_id', $business['id'])->sharedLock()->first()
                    ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);
                $publication = AuditReportPublication::query()->where('business_id', $business['id'])->where('audit_report_id', $reportId)->lockForUpdate()->first()
                    ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);
                $seal = $this->seal($publication);
                $partyId = $identity['party']['id'] ?? throw new CommandRejection('ACTION_FORBIDDEN', 403);
                $source = $seal->payload['sources']['verification'];
                $current = ! AuditReport::query()->where('amends_id', $reportId)->exists()
                    && $business['mandate_version'] === $publication->mandate_version
                    && $this->json->encode($business['mandate']) === $this->json->encode($seal->payload['business']['mandate'])
                    && $verification !== null && $verification['current'] && $source !== null
                    && $verification['id'] === $source['id'] && hash_equals($verification['sha256'], $source['sha256']);
                if (! $current) {
                    return $operation($publication, $seal, $business, $partyId, false);
                }

                return $this->assignments->withPublicationAuthority($seal->payload['assignment'],
                    function (bool $valid) use ($publication, $seal, $business, $partyId, $operation): mixed {
                        $facts = $valid ? $this->sourceFacts->forAccepted($seal->payload['assignment']) : null;
                        $pinned = $seal->payload['sources']['source_facts']['source'];

                        return $operation($publication, $seal, $business, $partyId, $valid && $facts !== null
                            && $facts['source']['id'] === $pinned['id'] && hash_equals($facts['source']['sha256'], $pinned['sha256']));
                    });
            });
    }

    private function seal(AuditReportPublication $publication): AuditReportSeal
    {
        $seal = AuditReportSeal::query()->where('audit_report_id', $publication->audit_report_id)->firstOrFail();
        if (($seal->payload['publication_policy'] ?? MonthlyReportReview::LEGACY_POLICY) !== $publication->policy_version
            || $seal->report_revision !== $publication->report_revision || ! hash_equals($seal->digest, $publication->digest)
            || ! $this->validSeal($seal, false)) {
            throw new CommandRejection('AUDIT_SEAL_UNAVAILABLE', 503);
        }

        return $seal;
    }

    private function validSeal(AuditReportSeal $seal, bool $current = true): bool
    {
        return ($seal->payload['report']['report_id'] ?? null) === $seal->audit_report_id
            && ($seal->payload['sealed_revision'] ?? null) === $seal->report_revision
            && hash_equals($seal->digest, hash('sha256', $this->json->encode($seal->payload['report'])))
            && $this->cryptography->verify($seal->audit_signing_key_id, $seal->jws, $seal->payload, $current);
    }

    /** @param Business $business */
    private function requireSigner(array $business, string $partyId): void
    {
        if (! in_array($partyId, $business['mandate']['required_signatories'], true)) {
            throw new CommandRejection('ACTION_FORBIDDEN', 403);
        }
    }

    /** @param Business $business
     * @return array<string, mixed>
     */
    private function project(AuditReportPublication $publication, AuditReportSeal $seal, array $business, string $partyId, bool $current): array
    {
        $signatures = AuditReportSignature::query()->where('audit_report_publication_id', $publication->id)->orderBy('id')->get()->keyBy('actor_party_id');
        $signers = [];
        foreach ($seal->payload['business']['mandate']['people'] as $person) {
            if (! in_array($person['party_id'], $seal->payload['business']['mandate']['required_signatories'], true)) {
                continue;
            }
            $signature = $signatures->get($person['party_id']);
            if ($signature !== null && ! hash_equals($signature->sha256, hash('sha256', $this->json->encode($signature->payload)))) {
                throw new CommandRejection('AUDIT_SIGNATURE_UNAVAILABLE', 503);
            }
            $signers[] = ['name' => $person['name'], 'state' => $signature === null ? 'pending' : 'signed',
                'signed_at' => $signature?->created_at->toIso8601String(), 'is_you' => $person['party_id'] === $partyId];
        }
        $due = $this->monthly($publication) ? $publication->due_at->toIso8601String() : $this->window->dueAt($seal->payload['report']['period']);
        $valid = $this->cryptography->verify($seal->audit_signing_key_id, $seal->jws, $seal->payload);
        $available = $current && $valid && ($this->monthly($publication)
            ? $this->reviewPolicy->isOpen($publication->status, $due, now()->toDateTimeImmutable())
            : $this->window->mayPublish($seal->payload['report']['period'], now()->toDateTimeImmutable()));
        $own = $signatures->get($partyId);
        $person = array_find($business['mandate']['people'], fn (array $person): bool => $person['party_id'] === $partyId);
        $canSign = $available && $publication->status === 'pending' && $own === null
            && in_array($partyId, $business['mandate']['required_signatories'], true) && in_array('report.cosign', $person['permissions'] ?? [], true);

        return ['business' => ['id' => $business['id'], 'name' => $business['profile']['name']],
            'report' => ['id' => $publication->audit_report_id, 'revision' => $publication->report_revision, 'kind' => $seal->payload['report']['period'] === null ? 'flash' : 'monthly',
                'period' => $seal->payload['report']['period'], 'digest' => $publication->digest, 'procedure_version' => $seal->payload['report']['procedure_version'],
                'auditor' => ['name' => $seal->payload['auditor_name'], 'licence' => $seal->payload['report']['licence']],
                'auditor_note' => $seal->payload['report']['draft']['note'], 'findings' => $seal->payload['report']['findings'],
                'seal' => ['status' => $valid ? 'valid' : 'unavailable', 'signed_at' => $seal->payload['sealed_at']], 'published_at' => $publication->published_at?->toIso8601String()],
            'cosign' => [...$this->reviewFacts($publication), 'revision' => $publication->revision,
                'state' => $this->monthly($publication) && in_array($publication->status, ['disputed', 'escalated', 'amended'], true) ? 'unavailable'
                    : ($publication->status === 'published' ? ($this->monthly($publication) && $publication->published_reason !== 'signed' ? 'pending' : 'signed') : (! $available ? 'unavailable' : ($signatures->isEmpty() ? 'pending' : 'partly_signed'))),
                'mandate_version' => $publication->mandate_version, 'required_signatures' => $this->monthly($publication) ? 1 : count($signers), 'signed_count' => $signatures->count(), 'signers' => $signers,
                'your_note' => $own?->payload['note'] ?? '', 'due_at' => $due, 'overdue' => ! $this->monthly($publication) && $publication->status === 'pending' && $due !== null && $due < now('UTC')->format('Y-m-d\TH:i:s\Z')],
            'can_cosign' => $canSign, 'can_dispute' => $this->monthly($publication) && $canSign];
    }

    private function monthly(AuditReportPublication $publication): bool
    {
        return $publication->policy_version === MonthlyReportReview::POLICY;
    }

    /** @param array<string, mixed> $payload */
    private function event(AuditReportPublication $publication, string $command, string $actorKind, ?int $userId, ?string $partyId, array $payload, ?int $revision = null, ?CarbonImmutable $at = null): AuditPublicationEvent
    {
        $payload = ['report_id' => $publication->audit_report_id, 'digest' => $publication->digest,
            'policy_version' => $publication->policy_version, ...$payload];
        $event = new AuditPublicationEvent;
        $event->forceFill(['audit_report_publication_id' => $publication->id, 'publication_revision' => $revision ?? $publication->revision + 1,
            'command' => $command, 'actor_kind' => $actorKind, 'actor_user_id' => $userId, 'actor_party_id' => $partyId,
            'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $at ?? now('UTC')])->save();

        return $event;
    }

    /** @return array<string, mixed> */
    private function reviewFacts(AuditReportPublication $publication): array
    {
        return ['policy_version' => $publication->policy_version,
            'delivered_at' => $publication->delivered_at?->toIso8601String(), 'published_reason' => $publication->published_reason,
            'dispute' => $this->reviewData($publication)];
    }

    /** @return array<string, mixed>|null */
    private function reviewData(AuditReportPublication $publication): ?array
    {
        if (! $this->monthly($publication)) {
            return null;
        }
        $event = AuditPublicationEvent::query()->where('audit_report_publication_id', $publication->id)
            ->where('publication_revision', $publication->revision)->first();
        if ($event === null || ! hash_equals($event->sha256, hash('sha256', $this->json->encode($event->payload)))
            || ($event->payload['review'] ?? null) !== $publication->review) {
            throw new CommandRejection('AUDIT_REVIEW_UNAVAILABLE', 503);
        }
        if ($publication->review === null) {
            return null;
        }
        $proofs = AuditDisputeProof::query()->where('audit_report_publication_id', $publication->id)->orderBy('id')->get();

        return [...$publication->review, 'proof_files' => $proofs->map(fn (AuditDisputeProof $proof): array => [
            'id' => $proof->id, 'name' => $this->proofName($proof), 'mime_type' => $proof->mime_type,
            'size_bytes' => $proof->size_bytes, 'sha256' => $proof->sha256])->all()];
    }

    public function requireAmendable(string $reportId): void
    {
        $publication = AuditReportPublication::query()->where('audit_report_id', $reportId)->lockForUpdate()->first();
        if ($publication !== null && $this->monthly($publication) && $publication->status === 'escalated'
            && ($this->reviewData($publication)['outcome'] ?? null) !== 'amendment_required') {
            throw new CommandRejection('REPORT_DISPUTE_STAFF_REVIEW_REQUIRED', revision: $publication->revision);
        }
    }

    private function completeAmendment(string $reportId): void
    {
        $parentId = AuditReport::query()->findOrFail($reportId)->amends_id;
        if ($parentId === null) {
            return;
        }
        $this->requireAmendable($parentId);
        $publication = AuditReportPublication::query()->where('audit_report_id', $parentId)->lockForUpdate()->first();
        if ($publication === null || ! $this->monthly($publication) || in_array($publication->status, ['published', 'amended'], true)) {
            return;
        }
        $review = $publication->review === null ? null : [...$publication->review, 'revision' => $publication->revision + 1,
            'status' => 'resolved', 'outcome' => 'amended', 'amendment' => ['report_id' => $reportId]];
        $this->event($publication, 'report.amended', 'system', null, null, ['review' => $review, 'amendment_id' => $reportId]);
        $publication->forceFill(['revision' => $publication->revision + 1, 'status' => 'amended', 'review' => $review])->save();
    }

    /** @param array<string, mixed> $input */
    private function requireReviewInput(AuditReportPublication $publication, array $input): void
    {
        if (! $this->monthly($publication)) {
            throw new CommandRejection('REPORT_REVIEW_NOT_SUPPORTED');
        }
        $this->reviewData($publication);
        if ($publication->revision !== $input['expected_revision']) {
            throw new CommandRejection('VERSION_CONFLICT', revision: $publication->revision);
        }
        if ($publication->report_revision !== $input['report_revision'] || ! hash_equals($publication->digest, $input['digest'])) {
            throw new CommandRejection('DIGEST_STALE', revision: $publication->revision);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<array{filename: string, content: string}>  $files
     * @return array<string, mixed>
     */
    public function dispute(int $userId, string $businessId, string $reportId, array $input, array $files): array
    {
        return $this->scope($userId, $input['identity_context_revision'], $businessId, $reportId, 'report.cosign',
            function (AuditReportPublication $publication, AuditReportSeal $seal, array $business, string $partyId, bool $current) use ($userId, $input, $files): array {
                $this->requireSigner($business, $partyId);
                $text = str_replace(["\r\n", "\r"], "\n", trim($input['supporting_text'] ?? ''));
                $metadata = array_map(fn (array $file): array => ['filename' => $file['filename'], 'size_bytes' => strlen($file['content']),
                    'sha256' => hash('sha256', $file['content'])], $files);
                $fingerprint = $this->fingerprint([...array_diff_key($input, ['request_id' => true]), 'supporting_text' => $text, 'proof_files' => $metadata]);

                return $this->journal->execute('party:'.$partyId, $userId, 'report.dispute', $input['request_id'], 'audit.publication', $publication->id,
                    $fingerprint, function (): void {}, function () use ($publication, $seal, $business, $partyId, $userId, $input, $files, $text, $current): OperationResult {
                        $this->requireReviewInput($publication, $input);
                        if ($publication->mandate_version !== $input['mandate_version'] || $business['mandate_version'] !== $input['mandate_version']) {
                            throw new CommandRejection('MANDATE_STALE', revision: $publication->revision);
                        }
                        if (AuditReport::query()->where('amends_id', $publication->audit_report_id)->exists()) {
                            throw new CommandRejection('AUDIT_REPORT_AMENDED', revision: $publication->revision);
                        }
                        if (! $current || ! $this->validSeal($seal)) {
                            throw new CommandRejection('AUDIT_PUBLICATION_UNAVAILABLE', revision: $publication->revision);
                        }
                        $at = now('UTC')->toImmutable();
                        $this->reviewPolicy->requireOpen($publication->status, $publication->due_at->toIso8601String(), $at->toDateTimeImmutable());
                        $text = $this->reviewPolicy->proofText($text, count($files));
                        $proofs = [];
                        foreach ($files as $file) {
                            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($file['content']);
                            if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true) || strlen($file['content']) < 1
                                || strlen($file['content']) > 10 * 1024 * 1024 || ! mb_check_encoding($file['filename'], 'UTF-8') || mb_strlen($file['filename']) > 255) {
                                throw new CommandRejection('REPORT_DISPUTE_PROOF_INVALID', 422, fieldErrors: ['proof_files' => ['Use PDF, JPEG or PNG originals, up to 10 MiB each.']]);
                            }
                            $proofs[] = ['id' => strtolower((string) Str::ulid()), 'filename' => $file['filename'], 'content' => $file['content'],
                                'mime_type' => $mime, 'size_bytes' => strlen($file['content']), 'sha256' => hash('sha256', $file['content'])];
                        }
                        $review = ['id' => strtolower((string) Str::ulid()), 'revision' => $publication->revision + 1, 'status' => 'under_review',
                            'submitted_at' => $at->format('Y-m-d\TH:i:s\Z'), 'supporting_text' => $text,
                            'outcome' => null, 'resolution_note' => null, 'resolution_note_by' => null, 'amendment' => null];
                        $event = $this->event($publication, 'report.dispute', 'party', $userId, $partyId,
                            ['review' => $review, 'proofs' => array_map(fn (array $file): array => array_diff_key($file, ['content' => true, 'filename' => true]), $proofs)], at: $at);
                        foreach ($proofs as $proof) {
                            (new AuditDisputeProof)->forceFill([...$proof, 'audit_report_publication_id' => $publication->id,
                                'audit_publication_event_id' => $event->id, 'created_at' => $at])->save();
                        }
                        $publication->forceFill(['revision' => $publication->revision + 1, 'status' => 'disputed', 'review' => $review])->save();

                        return $this->reviewReceipt($publication, 'REPORT_DISPUTED');
                    });
            });
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function uphold(int $userId, string $reportId, array $input): array
    {
        return $this->auditorScope($userId, $input['identity_context_revision'], $reportId,
            function (AuditReportPublication $publication, string $partyId) use ($userId, $input): array {
                return $this->journal->execute('party:'.$partyId, $userId, 'audit.dispute.uphold', $input['request_id'], 'audit.publication', $publication->id,
                    $this->fingerprint(array_diff_key($input, ['request_id' => true])), function (): void {}, function () use ($publication, $userId, $partyId, $input): OperationResult {
                        $this->requireReviewInput($publication, $input);
                        if ($publication->status !== 'disputed') {
                            throw new CommandRejection('REPORT_REVIEW_CLOSED', revision: $publication->revision);
                        }
                        $reason = $this->reviewPolicy->decisionReason($input['reason']);
                        $review = [...$publication->review, 'revision' => $publication->revision + 1, 'status' => 'escalated', 'resolution_note' => $reason, 'resolution_note_by' => 'cpa'];
                        $this->event($publication, 'audit.dispute.uphold', 'party', $userId, $partyId, ['review' => $review]);
                        $publication->forceFill(['revision' => $publication->revision + 1, 'status' => 'escalated', 'review' => $review])->save();

                        return $this->reviewReceipt($publication, 'REPORT_DISPUTE_ESCALATED');
                    });
            });
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function staffDecision(int $userId, string $assignmentId, string $reportId, string $command, array $input): array
    {
        if (! in_array($command, ['audit.dispute.escalate', 'audit.dispute.resolve'], true)) {
            throw new CommandRejection('ACTION_FORBIDDEN', 403);
        }

        return $this->staffScope($userId, $assignmentId, $reportId, function (AuditReportPublication $publication) use ($userId, $command, $input): array {
            return $this->journal->execute('staff:'.$userId, $userId, $command, $input['request_id'], 'audit.publication', $publication->id,
                $this->fingerprint(array_diff_key($input, ['request_id' => true])), function (): void {}, function () use ($publication, $userId, $command, $input): OperationResult {
                    $this->requireReviewInput($publication, $input);
                    $escalate = $command === 'audit.dispute.escalate';
                    if ($publication->status !== ($escalate ? 'disputed' : 'escalated')) {
                        throw new CommandRejection('REPORT_REVIEW_CLOSED', revision: $publication->revision);
                    }
                    $reason = $this->reviewPolicy->decisionReason($input['reason']);
                    $decision = $input['decision'] ?? '';
                    if (! $escalate && ! in_array($decision, ['uphold', 'require_amendment'], true)) {
                        throw new CommandRejection('REPORT_DISPUTE_DECISION_INVALID', 422);
                    }
                    $publish = ! $escalate && $decision === 'uphold';
                    if ($publish) {
                        $this->statements->withSystemVerification($publication->business_id, function (array $business, ?array $verification) use ($publication): void {
                            $this->withCurrentEvidence($publication, $this->seal($publication), $business, $verification, function (bool $current): void {
                                if (! $current) {
                                    throw new CommandRejection('AUDIT_PUBLICATION_UNAVAILABLE');
                                }
                            });
                        });
                    }
                    $review = [...$publication->review, 'revision' => $publication->revision + 1,
                        'status' => $publish ? 'resolved' : 'escalated', 'resolution_note' => $reason, 'resolution_note_by' => 'staff',
                        'outcome' => $escalate ? null : ($publish ? 'upheld' : 'amendment_required')];
                    $at = now('UTC')->toImmutable();
                    $this->event($publication, $command, 'staff', $userId, null, ['review' => $review, 'decision' => $decision], at: $at);
                    $publication->forceFill(['revision' => $publication->revision + 1, 'status' => $publish ? 'published' : 'escalated',
                        'review' => $review, 'published_at' => $publish ? $at : null, 'published_reason' => $publish ? 'staff_resolved' : null])->save();

                    return $this->reviewReceipt($publication, $publish ? 'REPORT_PUBLISHED' : ($escalate ? 'REPORT_DISPUTE_ESCALATED' : 'REPORT_DISPUTE_AMENDMENT_REQUIRED'));
                });
        });
    }

    /** @return array{inspected: int, published: int, blocked: array<string, string>, next_cursor: string|null} */
    public function advanceDue(int $limit = 100, ?string $after = null): array
    {
        $ids = AuditReportPublication::query()->where('policy_version', MonthlyReportReview::POLICY)->where('status', 'pending')
            ->where('due_at', '<=', now('UTC'))->when($after !== null, fn ($query) => $query->where('id', '>', $after))
            ->orderBy('id')->limit(max(1, min(100, $limit)))->get(['id', 'business_id', 'audit_report_id']);
        $published = 0;
        $blocked = [];
        foreach ($ids as $candidate) {
            try {
                $published += $this->statements->withSystemVerification($candidate->business_id,
                    function (array $business, ?array $verification) use ($candidate): int {
                        $publication = AuditReportPublication::query()->whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                        $this->reviewData($publication);
                        if (! $this->reviewPolicy->mayAutoApprove($publication->status, $publication->due_at->toIso8601String(), now()->toDateTimeImmutable())) {
                            return 0;
                        }

                        return $this->withCurrentEvidence($publication, $this->seal($publication), $business, $verification,
                            function (bool $current) use ($publication): int {
                                if (! $current) {
                                    throw new CommandRejection('AUDIT_PUBLICATION_UNAVAILABLE');
                                }
                                $at = now('UTC')->toImmutable();
                                $this->event($publication, 'report.auto_approve', 'system', null, null, ['published_reason' => 'auto_approved'], at: $at);
                                $publication->forceFill(['revision' => $publication->revision + 1, 'status' => 'published',
                                    'published_at' => $at, 'published_reason' => 'auto_approved'])->save();

                                return 1;
                            });
                    });
            } catch (CommandRejection|IdentityViolation $failure) {
                $blocked[$candidate->audit_report_id ?? $candidate->id] = $failure->getMessage();
            }
        }

        return ['inspected' => $ids->count(), 'published' => $published, 'blocked' => $blocked,
            'next_cursor' => $ids->count() === max(1, min(100, $limit)) ? $ids->last()->id : null];
    }

    /**
     * @template TResult
     *
     * @param  Business  $business
     * @param  Verification|null  $verification
     * @param  Closure(bool): TResult  $operation
     * @return TResult
     */
    private function withCurrentEvidence(AuditReportPublication $publication, AuditReportSeal $seal, array $business, ?array $verification, Closure $operation): mixed
    {
        $source = $seal->payload['sources']['verification'];
        $current = ! AuditReport::query()->where('amends_id', $publication->audit_report_id)->exists()
            && $business['mandate_version'] === $publication->mandate_version
            && $this->json->encode($business['mandate']) === $this->json->encode($seal->payload['business']['mandate'])
            && $verification !== null && $verification['current'] && $source !== null
            && $verification['id'] === $source['id'] && hash_equals($verification['sha256'], $source['sha256']) && $this->validSeal($seal);
        if (! $current) {
            return $operation(false);
        }

        return $this->assignments->withPublicationAuthority($seal->payload['assignment'], function (bool $valid) use ($seal, $operation): mixed {
            $facts = $valid ? $this->sourceFacts->forAccepted($seal->payload['assignment']) : null;
            $pinned = $seal->payload['sources']['source_facts']['source'];

            return $operation($valid && $facts !== null && $facts['source']['id'] === $pinned['id'] && hash_equals($facts['source']['sha256'], $pinned['sha256']));
        });
    }

    /** @template TResult
     * @param  Closure(AuditReportPublication, string): TResult  $operation
     * @return TResult
     */
    private function auditorScope(int $userId, int $contextRevision, string $reportId, Closure $operation): mixed
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);
        $report = AuditReport::query()->whereKey($reportId)->where('author_party_id', $partyId)->first()
            ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);

        return $this->assignments->withAccepted($userId, $contextRevision, $report->assignment_id, function (array $assignment) use ($reportId, $partyId, $operation): mixed {
            $report = AuditReport::query()->whereKey($reportId)->where('assignment_revision', $assignment['revision'])->where('author_party_id', $partyId)->sharedLock()->first()
                ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);
            $publication = AuditReportPublication::query()->where('audit_report_id', $report->id)->lockForUpdate()->first()
                ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);
            $this->reviewData($publication);

            return $operation($publication, $partyId);
        });
    }

    /** @template TResult
     * @param  Closure(AuditReportPublication): TResult  $operation
     * @return TResult
     */
    private function staffScope(int $userId, string $assignmentId, string $reportId, Closure $operation): mixed
    {
        $this->staff->check($userId, 'audit.assignments.manage');
        $report = AuditReport::query()->whereKey($reportId)->where('assignment_id', $assignmentId)->first()
            ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);

        return $this->businesses->withAudit(null, null, $report->business_id, [$report->author_party_id], false,
            fn (): mixed => $this->staff->handle($userId, 'audit.assignments.manage', function () use ($reportId, $operation): mixed {
                AuditReport::query()->whereKey($reportId)->sharedLock()->firstOrFail();
                $publication = AuditReportPublication::query()->where('audit_report_id', $reportId)->lockForUpdate()->first()
                    ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);
                $this->reviewData($publication);

                return $operation($publication);
            }));
    }

    /** @return array<string, mixed> */
    public function staffCase(int $userId, string $assignmentId, string $reportId): array
    {
        return $this->staffScope($userId, $assignmentId, $reportId, function (AuditReportPublication $publication) use ($reportId, $assignmentId): array {
            $available = true;
            try {
                $available = $this->statements->withSystemVerification($publication->business_id,
                    fn (array $business, ?array $verification): bool => $this->withCurrentEvidence($publication, $this->seal($publication), $business, $verification, fn (bool $current): bool => $current));
            } catch (CommandRejection|IdentityViolation) {
                $available = false;
            }

            return [
                'report_id' => $reportId, 'assignment_id' => $assignmentId, 'revision' => $publication->revision,
                'report_revision' => $publication->report_revision, 'digest' => $publication->digest, 'status' => $publication->status,
                'policy_version' => $publication->policy_version, 'due_at' => $publication->due_at?->toIso8601String(),
                'published_reason' => $publication->published_reason, 'dispute' => $this->reviewData($publication),
                'publication_available' => $available, 'unavailable_reason' => $available ? null : 'AUDIT_PUBLICATION_UNAVAILABLE'];
        });
    }

    /** @return array{content: string, filename: string, mime_type: string} */
    public function proof(int $userId, ?int $contextRevision, string $role, string $scopeId, string $reportId, string $proofId): array
    {
        /** @return array{content: string, filename: string, mime_type: string} */
        $read = function (AuditReportPublication $publication) use ($proofId): array {
            $proof = AuditDisputeProof::query()->whereKey($proofId)->where('audit_report_publication_id', $publication->id)->first()
                ?? throw new CommandRejection('REPORT_PROOF_NOT_FOUND', 404);
            $event = AuditPublicationEvent::query()->findOrFail($proof->audit_publication_event_id);
            $metadata = array_find($event->payload['proofs'] ?? [], fn (array $file): bool => $file['id'] === $proof->id);
            if (! hash_equals($event->sha256, hash('sha256', $this->json->encode($event->payload))) || $metadata === null
                || ! hash_equals($proof->sha256, hash('sha256', $proof->content)) || $metadata['sha256'] !== $proof->sha256
                || $metadata['mime_type'] !== $proof->mime_type || $metadata['size_bytes'] !== strlen($proof->content)) {
                throw new RuntimeException('AUDIT_DISPUTE_PROOF_INTEGRITY_FAILED');
            }

            return ['content' => $proof->content, 'filename' => $this->proofName($proof), 'mime_type' => $proof->mime_type];
        };

        return match ($role) {
            'business' => $this->scope($userId, $contextRevision ?? 0, $scopeId, $reportId, 'business.view', $read),
            'auditor' => $this->auditorScope($userId, $contextRevision ?? 0, $reportId, $read),
            'staff' => $this->staffScope($userId, $scopeId, $reportId, $read),
            default => throw new CommandRejection('ACTION_FORBIDDEN', 403),
        };
    }

    /** @return array<string, mixed> */
    public function findReviewOperation(int $userId, ?int $contextRevision, string $role, string $command, string $requestId): array
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? null;
        if (($role === 'auditor' && ($command !== 'audit.dispute.uphold' || $partyId === null))
            || ($role === 'staff' && ! in_array($command, ['audit.dispute.escalate', 'audit.dispute.resolve'], true))
            || ! in_array($role, ['auditor', 'staff'], true)) {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }

        return $this->journal->find($role === 'staff' ? 'staff:'.$userId : 'party:'.$partyId, $command, $requestId,
            function (string $type, string $id) use ($role, $userId, $contextRevision): void {
                $publication = $type === 'audit.publication' ? AuditReportPublication::query()->find($id) : null;
                if ($publication === null) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                if ($role === 'staff') {
                    $assignmentId = AuditReport::query()->findOrFail($publication->audit_report_id)->assignment_id;
                    $this->staffScope($userId, $assignmentId, $publication->audit_report_id, function (): void {});
                } else {
                    $this->auditorScope($userId, $contextRevision ?? 0, $publication->audit_report_id, function (): void {});
                }
            });
    }

    private function proofName(AuditDisputeProof $proof): string
    {
        return 'audit-proof-'.$proof->id.match ($proof->mime_type) {
            'application/pdf' => '.pdf', 'image/jpeg' => '.jpg', 'image/png' => '.png', default => '.bin',
        };
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function fingerprint(array $input): array
    {
        try {
            $this->json->encode($input);

            return $input;
        } catch (CommandRejection) {
            return ['invalid_input_sha256' => hash('sha256', serialize($input))];
        }
    }

    private function reviewReceipt(AuditReportPublication $publication, string $code): OperationResult
    {
        return new OperationResult($code, ['audit_id' => $publication->audit_report_id, 'report_id' => $publication->audit_report_id, 'business_id' => $publication->business_id,
            'assignment_id' => AuditReport::query()->findOrFail($publication->audit_report_id)->assignment_id,
            'digest' => $publication->digest], $publication->revision);
    }

    private function receipt(AuditReportPublication $publication, AuditReportSignature $signature): OperationResult
    {
        return new OperationResult($publication->status === 'published' ? 'REPORT_PUBLISHED' : 'REPORT_COSIGNATURE_RECORDED',
            ['report_id' => $publication->audit_report_id, 'business_id' => $publication->business_id, 'digest' => $publication->digest,
                'signature_id' => $signature->id, 'signed_at' => $signature->created_at->toIso8601String()], $publication->revision);
    }
}
