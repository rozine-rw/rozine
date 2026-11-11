<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Business\Contracts\AcceptedApplicationStore;
use App\Application\Business\Contracts\BusinessProspectuses;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Business\ProspectusDocument;
use App\Domain\Business\ProspectusDraft;
use App\Domain\Business\ProspectusGates;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessApplication;
use App\Models\BusinessCampaign;
use App\Models\BusinessProspectusDocument;
use App\Models\BusinessProspectusVersion;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The prospectus adapter. Facts the Business cannot edit (principal, term, instalment, verified
 * revenue, the sealed report's date and the registered names) come from the accepted application's
 * current release input, so a prospectus is only ever judged against terms that still hold. Saves
 * and the signature are journaled under the acting Party; the permitted input keeps only a digest
 * of the document, since profiles name real people. Versions are numbered under an advisory lock.
 *
 * @phpstan-import-type Inputs from ProspectusGates
 * @phpstan-import-type Facts from ProspectusGates
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 * @phpstan-import-type Prospectus from BusinessProspectuses
 */
final class EloquentBusinessProspectuses implements BusinessProspectuses
{
    /** Mandate roles and the PRO-R02 profile each one needs, strongest first. */
    private const array PROFILE_ROLES = ['director' => 'director', 'owner' => 'owner', 'beneficial_owner' => 'owner'];

    public function __construct(private WithBusinessAuthority $authority, private OperationJournal $journal, private CanonicalJson $json,
        private AcceptedApplicationStore $accepted, private AuditReportPublicationStore $reports, private PublishedMonthlyReports $monthly,
        private ProspectusGates $gates, private ProspectusDocument $documentRules) {}

    public function current(int $userId, int $contextRevision, string $businessId, string $applicationId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity) use ($applicationId): array {
                $application = $this->application($business['id'], $applicationId);
                $latest = $this->latest($application->id);
                $signed = $latest?->kind === 'signed' ? $latest : null;
                $inputs = $latest === null ? $this->prefilled($business) : $latest->payload['inputs'];
                $unavailable = null;
                try {
                    $facts = $this->facts($business, $application, $signed !== null);
                } catch (CommandRejection $rejection) {
                    $facts = null;
                    $unavailable = $rejection->reason;
                }
                $evaluation = $facts === null ? null : $this->gates->evaluate($inputs, $facts, CarbonImmutable::now('UTC'));
                $person = array_find($business['mandate']['people'], fn (array $person): bool => $person['party_id'] === ($identity['party']['id'] ?? null));
                $permissions = $person['permissions'] ?? [];
                $representative = $person !== null && $person['party_id'] === $this->representative($business);

                return ['application_id' => $application->id, 'business_id' => $business['id'], 'title' => (string) ($application->draft['title'] ?? ''), 'sections' => ProspectusGates::SECTIONS,
                    'version' => $latest->version ?? 0, 'inputs' => $inputs,
                    'signed' => $signed === null ? null : ['version' => $signed->version, 'fingerprint' => (string) $signed->fingerprint,
                        'signed_at' => $signed->created_at->toIso8601String()],
                    'facts' => $facts, 'unavailable' => $unavailable, 'evaluation' => $evaluation, 'documents' => $this->documents($application->id),
                    'can_edit' => $signed === null && in_array('application.save', $permissions, true),
                    'can_sign' => ($evaluation['may_sign'] ?? false) && $latest !== null && $representative && in_array('application.sign', $permissions, true)];
            });
    }

    public function save(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedVersion, array $input, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $applicationId, $expectedVersion, $input, $requestId): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                $application = $this->application($business['id'], $applicationId);
                $normalized = ProspectusDraft::normalize($input);
                $permitted = ['identity_context_revision' => $contextRevision, 'expected_version' => $expectedVersion,
                    'prospectus_sha256' => hash('sha256', $this->json->encode($normalized['inputs']))];

                return $this->journal->execute('party:'.$partyId, $userId, 'prospectus.save', $requestId, 'application', $application->id, $permitted,
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null, fn (): bool => true);
                    },
                    function (string $operationId) use ($application, $partyId, $expectedVersion, $normalized): OperationResult {
                        if ($normalized['errors'] !== []) {
                            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: $normalized['errors']);
                        }
                        $version = $this->nextVersion($application, $expectedVersion);
                        $payload = ['inputs' => $normalized['inputs']];
                        $this->append($application, $version, 'draft', $partyId, $operationId, $payload, null);

                        return new OperationResult('PROSPECTUS_SAVED', ['application_id' => $application->id, 'version' => $version], $version);
                    });
            });
    }

    public function upload(int $userId, int $contextRevision, string $businessId, string $applicationId, string $kind, string $filename, string $content, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $applicationId, $kind, $filename, $content, $requestId): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                $application = $this->application($business['id'], $applicationId);

                return $this->journal->execute('party:'.$partyId, $userId, 'prospectus.document.upload', $requestId, 'application', $application->id,
                    ['identity_context_revision' => $contextRevision, 'kind' => $kind, 'content_sha256' => hash('sha256', $content)],
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null, fn (): bool => true);
                    },
                    function (string $operationId) use ($application, $partyId, $kind, $filename, $content): OperationResult {
                        $this->lock($application);
                        $signed = $this->latest($application->id);
                        if ($signed?->kind === 'signed') {
                            throw new CommandRejection('PROSPECTUS_SIGNED', 409, $signed->version);
                        }
                        $described = $this->documentRules->describe($kind, $filename, $content);
                        $document = new BusinessProspectusDocument;
                        $document->forceFill([...$described, 'business_id' => $application->business_id, 'business_application_id' => $application->id,
                            'kind' => $kind, 'content' => $content, 'actor_party_id' => $partyId, 'operation_id' => $operationId, 'created_at' => now('UTC')])->save();

                        return new OperationResult('PROSPECTUS_DOCUMENT_UPLOADED', ['application_id' => $application->id, 'document_id' => $document->id, 'kind' => $kind], 1);
                    });
            });
    }

    public function published(string $campaignId): ?array
    {
        $applicationId = BusinessCampaign::query()->whereKey($campaignId)->value('business_application_id');
        $signed = is_string($applicationId)
            ? BusinessProspectusVersion::query()->where('business_application_id', $applicationId)->where('kind', 'signed')->first() : null;
        $facts = $signed?->payload['document']['facts'] ?? null;
        if ($signed === null || $signed->fingerprint === null || $facts === null) {
            return null;
        }
        $inputs = $signed->payload['inputs'];
        $representative = array_values(array_filter($inputs['profiles'], fn (array $profile): bool => $profile['role'] === 'representative'))[0]['name'] ?? '';
        $cites = fn (?string $id, string $kind): bool => $id !== null && ($facts['evidence'][$id] ?? null) === $kind;

        return ['summary' => ['version' => $signed->version, 'fingerprint' => $signed->fingerprint, 'signed_at' => $signed->created_at->toIso8601String(),
            'signed_by' => $representative], 'inputs' => $inputs,
            'facts' => ['principal' => $facts['principal'], 'term_months' => $facts['term_months'], 'instalment' => $facts['instalment'],
                'verified_monthly_revenue' => $facts['verified_monthly_revenue'], 'latest_sealed_report_at' => $facts['latest_sealed_report_at']],
            'quoted_lines' => array_keys(array_filter($inputs['use_of_funds'], fn (array $line): bool => $cites($line['quote_document_id'], 'quote'))),
            'contracted_months' => array_column(array_filter($inputs['forecast'], fn (array $month): bool => $cites($month['contract_document_id'], 'contract')), 'month')];
    }

    public function sign(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedVersion, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.sign', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $applicationId, $expectedVersion, $requestId): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                if ($partyId !== $this->representative($business)) {
                    throw new CommandRejection('ACTION_FORBIDDEN', 403);
                }
                $application = $this->application($business['id'], $applicationId);

                return $this->journal->execute('party:'.$partyId, $userId, 'prospectus.sign', $requestId, 'application', $application->id,
                    ['identity_context_revision' => $contextRevision, 'expected_version' => $expectedVersion],
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'application.sign', null, fn (): bool => true);
                    },
                    function (string $operationId) use ($business, $application, $partyId, $expectedVersion): OperationResult {
                        $version = $this->nextVersion($application, $expectedVersion);
                        $draft = $this->latest($application->id) ?? throw new CommandRejection('PROSPECTUS_DRAFT_REQUIRED', 409);
                        $inputs = $draft->payload['inputs'];
                        $facts = $this->facts($business, $application, false);
                        $now = CarbonImmutable::now('UTC');
                        $evaluation = $this->gates->evaluate($inputs, $facts, $now);
                        if (! $evaluation['may_sign']) {
                            $failed = array_values(array_filter($evaluation['gates'], fn (array $gate): bool => ! $gate['passed'] && $gate['id'] !== 'P-G12'));
                            throw new CommandRejection('PROSPECTUS_GATES_FAILED', 422, $draft->version,
                                data: ['gates' => array_map(fn (array $gate): array => ['id' => $gate['id'], 'section' => $gate['section'], 'fix' => $gate['fix']], $failed)]);
                        }
                        $document = ['policy_version' => ProspectusGates::POLICY_VERSION, 'business_id' => $business['id'], 'application_id' => $application->id,
                            'version' => $version, 'draft_version' => $draft->version, 'inputs' => $inputs, 'facts' => [...$facts, 'signed' => true],
                            'evidence_sha256' => BusinessProspectusDocument::query()->where('business_application_id', $application->id)->orderBy('id')->pluck('sha256', 'id')->all(),
                            'signed_by_party_id' => $partyId,
                            'signed_by_name' => (string) (array_find($business['mandate']['people'], fn (array $person): bool => $person['party_id'] === $partyId)['name'] ?? ''),
                            'signed_at' => $now->format('Y-m-d\TH:i:s.u\Z')];
                        $fingerprint = hash('sha256', $this->json->encode($document));
                        $this->append($application, $version, 'signed', $partyId, $operationId, ['inputs' => $inputs, 'document' => $document], $fingerprint);

                        return new OperationResult('PROSPECTUS_SIGNED', ['application_id' => $application->id, 'version' => $version, 'fingerprint' => $fingerprint], $version);
                    });
            });
    }

    /**
     * The facts the gates judge the prospectus against, from the accepted application's current
     * release input. A refusal there (stale quote, changed authority, no current report) means the
     * prospectus cannot be judged, and comes back as that refusal.
     *
     * @param  Business  $business
     * @return Facts
     */
    private function facts(array $business, BusinessApplication $application, bool $signed): array
    {
        if ($application->status !== 'submitted' || $application->current_submission_id === null) {
            throw new CommandRejection('PROSPECTUS_NOT_AVAILABLE', 409);
        }

        return $this->accepted->withReleaseInput($business['id'], $application->id, function (array $input) use ($business, $application, $signed): array {
            $evidence = $input['public_evidence'];
            $months = (int) ($evidence['period']['months'] ?? 0);
            $revenue = $evidence['totals']['revenue']['amount'] ?? '0';
            $sealed = array_filter([$this->reports->investorSummary($input['report']['id'])['verified_on'] ?? null,
                ...array_column(array_column($this->monthly->forBusiness($business['id']), 'auditor'), 'sealed_on')]);
            $sealedAt = $sealed === [] ? null : CarbonImmutable::parse(max(array_map(fn (string $at): string => CarbonImmutable::parse($at)->utc()->toIso8601String(), $sealed)));

            return ['principal' => (string) $input['principal'], 'term_months' => (int) $input['quote']['term_months'],
                'instalment' => (string) ($input['quote']['schedule'][0]['amount']['amount'] ?? '0'),
                'verified_monthly_revenue' => $months < 1 ? '0' : (string) BigInteger::of($revenue)->dividedBy($months, RoundingMode::HalfUp),
                'latest_sealed_report_at' => $sealedAt?->toIso8601String(),
                'registered_names' => array_column($business['mandate']['people'], 'name'), 'required_people' => $this->requiredPeople($business),
                'evidence' => $this->evidence($application->id), 'signed' => $signed];
        });
    }

    /**
     * Blank inputs with a profile to write for each person the prospectus must introduce.
     *
     * @param  Business  $business
     * @return Inputs
     */
    private function prefilled(array $business): array
    {
        $inputs = ProspectusDraft::blank();
        foreach ($this->requiredPeople($business) as $person) {
            $inputs['profiles'][] = [...$person, 'text' => ''];
        }

        return $inputs;
    }

    /**
     * The Business's official representative: the first required signatory on the current mandate.
     * Only this person signs the prospectus (PRO-R04), and the prospectus introduces them as such.
     *
     * @param  Business  $business
     */
    private function representative(array $business): ?string
    {
        return $business['mandate']['required_signatories'][0] ?? null;
    }

    /**
     * The people PRO-R02 asks the prospectus to introduce, from the current mandate: the first required
     * signatory as the official representative, then every director and owner, each once under the
     * strongest role they hold.
     *
     * @param  Business  $business
     * @return list<array{name: string, role: string}>
     */
    private function requiredPeople(array $business): array
    {
        $representative = $this->representative($business);
        $people = [];
        foreach ($business['mandate']['people'] as $person) {
            $role = $person['party_id'] === $representative ? 'representative'
                : array_values(array_filter(array_map(fn (string $role): ?string => self::PROFILE_ROLES[$role] ?? null, $person['roles'])))[0] ?? null;
            if ($role !== null) {
                $people[] = ['name' => $person['name'], 'role' => $role];
            }
        }

        return $people;
    }

    /**
     * The evidence documents uploaded for this application, by id, each with its kind.
     *
     * @return array<string, string>
     */
    private function evidence(string $applicationId): array
    {
        /** @var array<string, string> */
        return BusinessProspectusDocument::query()->where('business_application_id', $applicationId)->pluck('kind', 'id')->all();
    }

    /**
     * The documents as the Listing Builder lists them, oldest first.
     *
     * @return list<array{id: string, kind: string, filename: string, media_type: string, size_bytes: int, uploaded_at: string}>
     */
    private function documents(string $applicationId): array
    {
        return array_values(array_map(fn (BusinessProspectusDocument $document): array => ['id' => $document->id, 'kind' => $document->kind,
            'filename' => $document->filename, 'media_type' => $document->media_type, 'size_bytes' => $document->size_bytes,
            'uploaded_at' => $document->created_at->toIso8601String()],
            BusinessProspectusDocument::query()->where('business_application_id', $applicationId)->orderBy('created_at')->orderBy('id')->get()->all()));
    }

    private function application(string $businessId, string $applicationId): BusinessApplication
    {
        return BusinessApplication::query()->where('business_id', $businessId)->whereKey($applicationId)->first()
            ?? throw new CommandRejection('APPLICATION_NOT_FOUND', 404);
    }

    private function latest(string $applicationId): ?BusinessProspectusVersion
    {
        return BusinessProspectusVersion::query()->where('business_application_id', $applicationId)->orderByDesc('version')->first();
    }

    /** Serializes saves, signatures and uploads for one application's prospectus. */
    private function lock(BusinessApplication $application): void
    {
        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['business-prospectus|'.$application->id]);
    }

    /** Locks the application's versions and returns the next number, refusing a stale or locked prospectus. */
    private function nextVersion(BusinessApplication $application, int $expectedVersion): int
    {
        $this->lock($application);
        $latest = $this->latest($application->id);
        if ($latest?->kind === 'signed') {
            throw new CommandRejection('PROSPECTUS_SIGNED', 409, $latest->version);
        }
        if (($latest->version ?? 0) !== $expectedVersion) {
            throw new CommandRejection('VERSION_CONFLICT', 409, $latest->version ?? 0);
        }

        return $expectedVersion + 1;
    }

    /** @param array{inputs: Inputs, document?: array<string, mixed>} $payload */
    private function append(BusinessApplication $application, int $version, string $kind, string $partyId, string $operationId, array $payload, ?string $fingerprint): void
    {
        $record = new BusinessProspectusVersion;
        $record->forceFill(['business_id' => $application->business_id, 'business_application_id' => $application->id, 'version' => $version,
            'kind' => $kind, 'actor_party_id' => $partyId, 'operation_id' => $operationId, 'payload' => $payload,
            'sha256' => hash('sha256', $this->json->encode($payload)), 'fingerprint' => $fingerprint, 'created_at' => now('UTC')])->save();
    }
}
