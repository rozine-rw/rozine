<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\Contracts\AuditSourceFactsStore;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Evidence\WithBusinessStatementVerification;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AuditReportWindow;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditReport;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\AuditReportSignature;
use Closure;

/**
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 * @phpstan-import-type Verification from \App\Application\Evidence\Contracts\StatementStore
 */
final class EloquentAuditReportPublicationStore implements AuditReportPublicationStore
{
    public function __construct(private WithBusinessStatementVerification $evidence, private WithBusinessAuthority $authority,
        private IdentityRepository $identities, private AuditAssignmentStore $assignments, private AuditReportCryptography $cryptography,
        private OperationJournal $journal, private CanonicalJson $json, private AuditReportWindow $window, private AuditSourceFactsStore $sourceFacts) {}

    /** @param array<string, mixed> $payload */
    public function open(string $reportId, string $digest, array $payload): void
    {
        (new AuditReportPublication)->forceFill(['audit_report_id' => $reportId, 'business_id' => $payload['business']['id'],
            'mandate_version' => $payload['business']['mandate_version'], 'report_revision' => $payload['sealed_revision'],
            'digest' => $digest, 'revision' => 1, 'status' => 'pending'])->save();
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
                        if (! $this->window->mayPublish($seal->payload['report']['period'], $at->toDateTimeImmutable())) {
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
                        $complete = array_diff($business['mandate']['required_signatories'], $signed) === [];
                        $publication->forceFill(['revision' => $publication->revision + 1, 'status' => $complete ? 'published' : 'pending',
                            'published_at' => $complete ? $at : null])->save();

                        return $this->receipt($publication, $signature);
                    });
            });
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId): array
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new CommandRejection('OPERATION_NOT_FOUND', 404);

        return $this->journal->find('party:'.$partyId, 'report.cosign', $requestId,
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
        $due = $this->window->dueAt($seal->payload['report']['period']);

        return ['sealed_at' => $seal->payload['sealed_at'], 'digest' => $seal->digest, 'licence' => $seal->payload['report']['licence'],
            'signature_ref' => $seal->id, 'report_id' => $reportId, 'key_id' => $seal->audit_signing_key_id,
            'seal_status' => $this->validSeal($seal) ? 'valid' : 'unavailable',
            'cosign' => ['party' => $seal->payload['business']['profile']['name'], 'state' => $publication->status === 'published' ? 'signed' : ($due !== null && $due < now('UTC')->format('Y-m-d\TH:i:s\Z') ? 'overdue' : 'pending'),
                'due_on' => $due, 'signed_at' => $publication->published_at?->toIso8601String()],
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
        if ($seal->report_revision !== $publication->report_revision || ! hash_equals($seal->digest, $publication->digest)
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
        $due = $this->window->dueAt($seal->payload['report']['period']);
        $valid = $this->cryptography->verify($seal->audit_signing_key_id, $seal->jws, $seal->payload);
        $available = $current && $valid && $this->window->mayPublish($seal->payload['report']['period'], now()->toDateTimeImmutable());
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
            'cosign' => ['revision' => $publication->revision, 'state' => $publication->status === 'published' ? 'signed' : (! $available ? 'unavailable' : ($signatures->isEmpty() ? 'pending' : 'partly_signed')),
                'mandate_version' => $publication->mandate_version, 'required_signatures' => count($signers), 'signed_count' => $signatures->count(), 'signers' => $signers,
                'your_note' => $own?->payload['note'] ?? '', 'due_at' => $due, 'overdue' => $publication->status !== 'published' && $due !== null && $due < now('UTC')->format('Y-m-d\TH:i:s\Z')],
            'can_cosign' => $canSign];
    }

    private function receipt(AuditReportPublication $publication, AuditReportSignature $signature): OperationResult
    {
        return new OperationResult($publication->status === 'published' ? 'REPORT_PUBLISHED' : 'REPORT_COSIGNATURE_RECORDED',
            ['report_id' => $publication->audit_report_id, 'business_id' => $publication->business_id, 'digest' => $publication->digest,
                'signature_id' => $signature->id, 'signed_at' => $signature->created_at->toIso8601String()], $publication->revision);
    }
}
