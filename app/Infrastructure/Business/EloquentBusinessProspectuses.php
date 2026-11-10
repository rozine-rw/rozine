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
use App\Domain\Business\ProspectusDraft;
use App\Domain\Business\ProspectusGates;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessApplication;
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
        private ProspectusGates $gates) {}

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
                $representative = in_array($person['party_id'] ?? null, $business['mandate']['required_signatories'], true);

                return ['application_id' => $application->id, 'business_id' => $business['id'], 'title' => (string) ($application->draft['title'] ?? ''), 'sections' => ProspectusGates::SECTIONS,
                    'version' => $latest->version ?? 0, 'inputs' => $inputs,
                    'signed' => $signed === null ? null : ['version' => $signed->version, 'fingerprint' => (string) $signed->fingerprint,
                        'signed_at' => $signed->created_at->toIso8601String()],
                    'facts' => $facts, 'unavailable' => $unavailable, 'evaluation' => $evaluation,
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

    public function sign(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedVersion, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.sign', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $applicationId, $expectedVersion, $requestId): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                if (! in_array($partyId, $business['mandate']['required_signatories'], true)) {
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
                            'signed_by_party_id' => $partyId, 'signed_at' => $now->format('Y-m-d\TH:i:s.u\Z')];
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

        return $this->accepted->withReleaseInput($business['id'], $application->id, function (array $input) use ($business, $signed): array {
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
                'registered_names' => array_column($business['mandate']['people'], 'name'), 'signed' => $signed];
        });
    }

    /**
     * Blank inputs with one profile for each person PRO-R02 asks to introduce: the first required
     * signatory as the official representative, then every director and owner.
     *
     * @param  Business  $business
     * @return Inputs
     */
    private function prefilled(array $business): array
    {
        $inputs = ProspectusDraft::blank();
        $representative = $business['mandate']['required_signatories'][0] ?? null;
        foreach ($business['mandate']['people'] as $person) {
            $role = $person['party_id'] === $representative ? 'representative'
                : array_values(array_filter(array_map(fn (string $role): ?string => self::PROFILE_ROLES[$role] ?? null, $person['roles'])))[0] ?? null;
            if ($role !== null) {
                $inputs['profiles'][] = ['name' => $person['name'], 'role' => $role, 'text' => ''];
            }
        }

        return $inputs;
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

    /** Locks the application's versions and returns the next number, refusing a stale or locked prospectus. */
    private function nextVersion(BusinessApplication $application, int $expectedVersion): int
    {
        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['business-prospectus|'.$application->id]);
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
