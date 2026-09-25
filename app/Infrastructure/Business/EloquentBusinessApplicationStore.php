<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Auditor\WithCurrentAuditAssignment;
use App\Application\Business\Contracts\BusinessApplicationStore;
use App\Application\Business\Contracts\BusinessCreditFactsStore;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Evidence\WithBusinessStatementVerification;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Identity\WithCurrentConsent;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Business\ApplicationAcceptance;
use App\Domain\Business\ApplicationDraft;
use App\Domain\Business\ApplicationEvidence;
use App\Domain\Identity\ConsentDocuments;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Underwriting\ApplicationUnderwriting;
use App\Domain\Underwriting\ExactFinancialValue;
use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\UnderwritingViolation;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessApplicationVersion;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @phpstan-import-type Fields from ApplicationDraft
 * @phpstan-import-type Application from BusinessApplicationStore
 * @phpstan-import-type AuditApplication from BusinessApplicationStore
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 * @phpstan-import-type Verification from \App\Application\Evidence\Contracts\StatementStore
 * @phpstan-import-type Snapshot from BusinessCreditFactsStore as CreditSnapshot
 * @phpstan-import-type Release from \App\Application\Identity\Contracts\ConsentCatalog
 * @phpstan-import-type Terms from \App\Domain\Business\MandateAuthority
 * @phpstan-import-type EvaluationExpectation from BusinessApplicationStore
 */
final class EloquentBusinessApplicationStore implements BusinessApplicationStore
{
    public function __construct(
        private WithBusinessAuthority $authority,
        private IdentityRepository $identities,
        private OperationJournal $journal,
        private ApplicationDraft $drafts,
        private WithCurrentAuditAssignment $assignments,
        private WithBusinessStatementVerification $verifications,
        private BusinessCreditFactsStore $creditFacts,
        private CanonicalJson $json,
        private ApplicationUnderwriting $underwriting,
        private WithCurrentConsent $consents,
        private ConsentDocuments $documents,
        private ApplicationAcceptance $acceptances,
        private ApplicationEvidence $applicationEvidence,
    ) {}

    /** @return array<string, mixed> */
    public function create(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.create', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $expectedRevision, $requestId): array {
                $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

                return $this->journal->execute('party:'.$partyId, $userId, 'application.create', $requestId, 'business', $businessId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision],
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'application.create', null, fn (): bool => true);
                    },
                    function () use ($userId, $partyId, $business, $expectedRevision): OperationResult {
                        if ($expectedRevision !== 0) {
                            throw new CommandRejection('VERSION_CONFLICT', 409, 0);
                        }
                        $existing = BusinessApplication::query()->where('business_id', $business['id'])->where('status', 'draft')->lockForUpdate()->first();
                        if ($existing !== null) {
                            return new OperationResult('APPLICATION_RESUMED', ['application' => $this->snapshot($existing)], $existing->revision);
                        }
                        $application = new BusinessApplication;
                        $application->forceFill(['business_id' => $business['id'], 'revision' => 1, 'status' => 'draft', 'step' => 'business',
                            'draft' => $this->drafts->empty(), 'mandate_version' => $business['mandate_version']])->save();
                        $this->recordVersion($application, $userId, $partyId, $business['mandate_version']);

                        return new OperationResult('APPLICATION_CREATED', ['application' => $this->snapshot($application)], 1);
                    });
            });
    }

    /**
     * @param  Fields  $fields
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $fields, ?string $step, string $requestId): array
    {
        if ($step === 'review') {
            return $this->advanceToReview($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $fields, $requestId);
        }

        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $fields, $step, $requestId): array {
                $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
                $application = $this->application($businessId, $applicationId);

                return $this->journal->execute('party:'.$partyId, $userId, 'application.save', $requestId, 'application', $applicationId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'draft' => $fields, 'step' => $step],
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null, fn (): bool => true);
                    },
                    function () use ($application, $business, $userId, $partyId, $expectedRevision, $fields, $step): OperationResult {
                        $this->drafts->assertEditable($application->status, $application->revision, $expectedRevision);
                        $fields = $this->drafts->normalize($fields);
                        if ($step !== null && ! in_array($step, ['business', 'raise'], true)) {
                            throw new CommandRejection('APPLICATION_STEP_INVALID', 422, $application->revision,
                                ['step' => ['A draft may resume at the Business or Raise step.']]);
                        }
                        $application->forceFill(['draft' => $fields, 'step' => $step ?? $application->step, 'revision' => $expectedRevision + 1, 'current_quote_id' => null,
                            'mandate_version' => $business['mandate_version']])->save();
                        $this->recordVersion($application, $userId, $partyId, $business['mandate_version']);

                        return new OperationResult('APPLICATION_SAVED', $this->consents->handle(
                            fn (?array $release): array => $this->reviewForQuote($application, $business, null, $release)), $application->revision);
                    });
            });
    }

    /**
     * @param  Fields  $fields
     * @return array<string, mixed>
     */
    private function advanceToReview(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $fields, string $requestId): array
    {
        return $this->verifications->handle($userId, $contextRevision, $businessId, 'application.save', null,
            function (array $business, array $identity, ?array $verification) use ($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $fields, $requestId): array {
                return $this->creditFacts->withCurrent($businessId, function (?array $credit) use ($business, $identity, $verification, $userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $fields, $requestId): array {
                    $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
                    $application = $this->application($businessId, $applicationId);

                    return $this->journal->execute('party:'.$partyId, $userId, 'application.save', $requestId, 'application', $applicationId,
                        ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'draft' => $fields, 'step' => 'review'],
                        function (): void {}, function () use ($application, $business, $verification, $credit, $userId, $partyId, $expectedRevision, $fields): OperationResult {
                            $this->drafts->assertEditable($application->status, $application->revision, $expectedRevision);
                            $normalized = $this->drafts->normalize($fields);
                            $quote = $this->currentQuote($application, $business, $verification, $credit);
                            if ($quote === null || ! $quote->payload['result']['eligible'] || $normalized !== $this->drafts->normalize($application->draft)) {
                                throw new CommandRejection('QUOTE_STALE', 409, $application->revision,
                                    ['step' => ['Save and evaluate the current request before continuing to Review.']]);
                            }
                            $errors = [];
                            if ($normalized['title'] === '') {
                                $errors['title'] = ['Add an application title.'];
                            }
                            if ($normalized['use_of_funds'] === []) {
                                $errors['use_of_funds'] = ['Select the intended use of funds.'];
                            }
                            if ($errors !== []) {
                                throw new CommandRejection('APPLICATION_INPUT_INVALID', 422, $application->revision, $errors);
                            }
                            $application->forceFill(['step' => 'review', 'revision' => $application->revision + 1])->save();
                            $this->recordVersion($application, $userId, $partyId, $business['mandate_version']);

                            return new OperationResult('APPLICATION_SAVED', $this->consents->handle(
                                fn (?array $release): array => $this->reviewForQuote($application, $business, $quote, $release)), $application->revision);
                        });
                });
            });
    }

    /**
     * @param  EvaluationExpectation|null  $expectation
     * @return array<string, mixed>
     */
    public function evaluate(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, ?string $acceptedPrincipal, string $requestId, ?array $expectation = null): array
    {
        return $this->verifications->handle($userId, $contextRevision, $businessId, 'application.evaluate', null,
            function (array $business, array $identity, ?array $verification) use ($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $acceptedPrincipal, $requestId, $expectation): array {
                return $this->creditFacts->withCurrent($businessId, function (?array $credit) use ($business, $identity, $verification, $userId, $contextRevision, $applicationId, $businessId, $expectedRevision, $acceptedPrincipal, $requestId, $expectation): array {
                    $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
                    $application = $this->application($businessId, $applicationId);

                    return $this->journal->execute('party:'.$partyId, $userId, 'application.evaluate', $requestId, 'application', $applicationId,
                        ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'accepted_principal' => $acceptedPrincipal,
                            ...($expectation === null ? [] : ['expectation' => $expectation])],
                        function (): void {}, function () use ($application, $business, $verification, $credit, $userId, $partyId, $expectedRevision, $acceptedPrincipal, $expectation): OperationResult {
                            $this->drafts->assertEditable($application->status, $application->revision, $expectedRevision);
                            $draft = $this->drafts->normalize($application->draft);
                            if ($expectation !== null && ($expectation['target'] !== $draft['target'] || $expectation['term_months'] !== $draft['term_months']
                                || $expectation['evidence_version'] !== $this->evidenceVersion($verification))) {
                                throw new CommandRejection('QUOTE_STALE', 409, $application->revision);
                            }
                            if ($draft['target'] === null || $draft['term_months'] === null || ExactFinancialValue::amount($draft['target'])->isLessThan(3000000)) {
                                throw new CommandRejection('APPLICATION_INPUT_INVALID', 422, $application->revision,
                                    ['target' => ['Request at least RWF 3,000,000 and select a supported term.']]);
                            }
                            $now = now('UTC')->toImmutable();
                            $calendar = ['last_complete_month' => $now->startOfMonth()->subMonth()->format('Y-m'),
                                'first_repayment_month' => $now->startOfMonth()->format('Y-m')];
                            $result = $this->calculate($draft['target'], $draft['term_months'], $verification, $credit, $acceptedPrincipal, $calendar);
                            $prior = BusinessApplicationQuote::query()->where('business_application_id', $application->id)->orderByDesc('revision')->first();
                            $quote = new BusinessApplicationQuote;
                            $quote->id = (string) Str::ulid();
                            $revision = ($prior->revision ?? 0) + 1;
                            $payload = ['quote_id' => $quote->id, 'quote_revision' => $revision,
                                'application_id' => $application->id, 'application_revision' => $application->revision,
                                'business_id' => $business['id'], 'mandate_version' => $business['mandate_version'],
                                'draft' => $draft, 'accepted_principal' => $acceptedPrincipal,
                                'evidence' => $this->evidenceBinding($verification), 'credit' => $this->creditBinding($credit),
                                'credit_source_reference' => $credit['source_reference'] ?? null,
                                'policy_version' => FlatReturnPricing::POLICY_VERSION, 'calculation_version' => ApplicationUnderwriting::VERSION,
                                'calendar' => $calendar, 'evaluated_at' => $now->format('Y-m-d\TH:i:s\Z'),
                                'actor_user_id' => $userId, 'actor_party_id' => $partyId, 'result' => $result];
                            $quote->forceFill(['business_application_id' => $application->id, 'revision' => $revision,
                                'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload))])->save();
                            $application->forceFill(['revision' => $application->revision + 1, 'current_quote_id' => $quote->id,
                                'mandate_version' => $business['mandate_version']])->save();
                            $this->recordVersion($application, $userId, $partyId, $business['mandate_version']);

                            return new OperationResult('APPLICATION_EVALUATED', $this->consents->handle(
                                fn (?array $release): array => $this->reviewForQuote($application, $business, $quote, $release)), $application->revision);
                        });
                });
            });
    }

    /** @return array<string, mixed>|null */
    public function quote(int $userId, int $contextRevision, string $businessId, string $applicationId): ?array
    {
        return $this->verifications->handle($userId, $contextRevision, $businessId, 'business.view', null,
            fn (array $business, array $identity, ?array $verification): ?array => $this->creditFacts->withCurrent($businessId,
                function (?array $credit) use ($business, $verification, $businessId, $applicationId): ?array {
                    $application = $this->application($businessId, $applicationId);
                    if ($application->status === 'submitted') {
                        return $this->submittedReview($application)['quote'];
                    }
                    $quote = $this->currentQuote($application, $business, $verification, $credit);

                    return $quote === null ? null : $this->projectQuote($quote);
                }));
    }

    /**
     * @param  array<string, mixed>  $acceptance
     * @return array<string, mixed>
     */
    public function submit(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $acceptance, string $requestId): array
    {
        return $this->verifications->handle($userId, $contextRevision, $businessId, 'application.sign', null,
            function (array $business, array $identity, ?array $verification) use ($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $acceptance, $requestId): array {
                $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
                if (! in_array($partyId, $business['mandate']['required_signatories'], true)) {
                    throw new CommandRejection('ACTION_FORBIDDEN', 403);
                }

                return $this->creditFacts->withCurrent($businessId, function (?array $credit) use ($business, $verification, $partyId, $userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $acceptance, $requestId): array {
                    return $this->consents->handle(function (?array $release) use ($business, $verification, $credit, $partyId, $userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $acceptance, $requestId): array {
                        $application = $this->application($businessId, $applicationId);

                        return $this->journal->execute('party:'.$partyId, $userId, 'application.submit', $requestId, 'application', $applicationId,
                            ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'acceptance' => $acceptance],
                            function (): void {}, function () use ($application, $business, $verification, $credit, $release, $partyId, $userId, $expectedRevision, $acceptance): OperationResult {
                                $this->drafts->assertEditable($application->status, $application->revision, $expectedRevision);
                                $accepted = $this->acceptances->normalize($acceptance);
                                $quote = $this->currentQuote($application, $business, $verification, $credit);
                                if ($application->step !== 'review' || $quote === null || ! $quote->payload['result']['eligible']
                                    || $accepted['quote_id'] !== $quote->id || $accepted['quote_revision'] !== $quote->revision
                                    || $accepted['mandate_version'] !== (string) $business['mandate_version']
                                    || $accepted['evidence_version'] !== $quote->payload['evidence']['sha256']
                                    || $accepted['accepted_principal'] !== $quote->payload['result']['capacity']['offer']['principal']['amount']) {
                                    throw new CommandRejection('QUOTE_STALE', 409, $application->revision);
                                }
                                if ($release === null) {
                                    throw new CommandRejection('CONSENT_DOCUMENTS_UNAVAILABLE', 409, $application->revision);
                                }
                                $this->documents->assertAccepted($release['documents'], $release['disclosures'], $accepted['documents'], $accepted['disclosures']);
                                $agreement = $this->agreement($application, $business, $quote, $release);
                                $bindingHash = hash('sha256', $this->json->encode($agreement));
                                $signatures = $this->signatures($application, $quote, $bindingHash);
                                foreach ($signatures as $signature) {
                                    if ($signature->actor_party_id === $partyId) {
                                        return new OperationResult('APPLICATION_SIGNATURE_RECORDED',
                                            $this->reviewData($application, $quote, $agreement, $signatures), $application->revision);
                                    }
                                }
                                $signature = new BusinessApplicationSignature;
                                $signature->id = (string) Str::ulid();
                                $payload = ['signature_id' => $signature->id, 'application_id' => $application->id,
                                    'quote_id' => $quote->id, 'consent_release_id' => $release['id'], 'binding_sha256' => $bindingHash,
                                    'agreement' => $agreement, 'actor_user_id' => $userId, 'actor_party_id' => $partyId,
                                    'signature_name' => $accepted['signature_name'], 'terms' => true, 'privacy' => true,
                                    'signed_at' => now('UTC')->format('Y-m-d\TH:i:s\Z')];
                                $signature->forceFill(['business_application_id' => $application->id, 'business_application_quote_id' => $quote->id,
                                    'consent_release_id' => $release['id'], 'actor_party_id' => $partyId, 'binding_sha256' => $bindingHash,
                                    'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload))])->save();
                                $signatures[] = $signature;
                                $application->revision++;
                                $review = $this->reviewData($application, $quote, $agreement, $signatures);
                                $complete = $review['acceptance']['signatures_complete'];
                                if ($complete) {
                                    $application->status = 'submitted';
                                    $application->step = 'submitted';
                                    $review['application'] = $this->snapshot($application);
                                    $review['submission'] = $this->submissionProjection($application);
                                    $submission = new BusinessApplicationSubmission;
                                    $submission->id = (string) Str::ulid();
                                    $payload = ['submission_id' => $submission->id, 'application_id' => $application->id,
                                        'application_revision' => $application->revision, 'quote_id' => $quote->id,
                                        'binding_sha256' => $bindingHash, 'agreement' => $agreement,
                                        'signatures' => array_map(fn (BusinessApplicationSignature $item): array => ['id' => $item->id, 'sha256' => $item->sha256], $signatures),
                                        'submitted_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'), 'review' => $review,
                                        'public_evidence' => $this->projectEvidence($business, $verification, $credit, $application, $quote)];
                                    $submission->forceFill(['business_application_id' => $application->id, 'business_application_quote_id' => $quote->id,
                                        'revision' => $application->revision, 'binding_sha256' => $bindingHash,
                                        'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload))])->save();
                                    $application->current_submission_id = $submission->id;
                                }
                                $application->save();
                                $this->recordVersion($application, $userId, $partyId, $business['mandate_version']);

                                return new OperationResult($complete ? 'APPLICATION_SUBMITTED' : 'APPLICATION_SIGNATURE_RECORDED', $review, $application->revision);
                            });
                    });
                });
            });
    }

    /** @return array<string, mixed> */
    public function review(int $userId, int $contextRevision, string $businessId, string $applicationId): array
    {
        return $this->verifications->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity, ?array $verification) use ($businessId, $applicationId): array {
                $application = $this->application($businessId, $applicationId);
                if ($application->status === 'submitted') {
                    return $this->submittedReview($application);
                }

                return $this->creditFacts->withCurrent($businessId, function (?array $credit) use ($application, $business, $verification): array {
                    return $this->consents->handle(function (?array $release) use ($application, $business, $verification, $credit): array {
                        $quote = $this->currentQuote($application, $business, $verification, $credit);

                        return $this->reviewForQuote($application, $business, $quote, $release);
                    });
                });
            });
    }

    /**
     * @param  Business  $business
     * @param  Release|null  $release
     * @return array<string, mixed>
     */
    private function reviewForQuote(BusinessApplication $application, array $business, ?BusinessApplicationQuote $quote, ?array $release): array
    {
        if ($quote === null || ! $quote->payload['result']['eligible'] || $release === null) {
            return ['application' => $this->snapshot($application), 'quote' => $quote === null ? null : $this->projectQuote($quote),
                'acceptance' => $this->projectAcceptance($business['mandate'], $business['mandate_version'], $release, []), 'submission' => null];
        }
        $agreement = $this->agreement($application, $business, $quote, $release);
        $signatures = $this->signatures($application, $quote, hash('sha256', $this->json->encode($agreement)));

        return $this->reviewData($application, $quote, $agreement, $signatures);
    }

    /** @return array<string, mixed> */
    public function page(int $userId, int $contextRevision, string $businessId, string $applicationId): array
    {
        return $this->verifications->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity, ?array $verification) use ($businessId, $applicationId, $contextRevision): array {
                return $this->creditFacts->withCurrent($businessId, function (?array $credit) use ($business, $identity, $verification, $businessId, $applicationId, $contextRevision): array {
                    return $this->consents->handle(function (?array $release) use ($business, $identity, $verification, $credit, $businessId, $applicationId, $contextRevision): array {
                        $application = $this->application($businessId, $applicationId);
                        $submitted = $application->status === 'submitted' ? $this->submittedSnapshot($application) : null;
                        $quote = $application->status === 'submitted' ? null : $this->currentQuote($application, $business, $verification, $credit);
                        $review = $submitted === null ? $this->reviewForQuote($application, $business, $quote, $release) : $submitted['review'];

                        return ['review' => $review, 'business_id' => $businessId, 'identity_context_revision' => $contextRevision,
                            'evidence' => $submitted === null ? $this->projectEvidence($business, $verification, $credit, $application, $quote) : $submitted['public_evidence'],
                            'allowed_actions' => $this->allowedActions($application, $business, $identity['party']['id'] ?? '', $review)];
                    });
                });
            });
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function projectOperation(int $userId, int $contextRevision, array $result): array
    {
        if ($result['status'] !== 'completed') {
            return $result;
        }
        $recorded = $result['data']['application'];
        $page = $this->page($userId, $contextRevision, $recorded['business_id'], $recorded['id']);
        $sameRevision = $recorded['revision'] === $page['review']['application']['revision'];
        $sameReview = ! isset($result['data']['acceptance']) || $this->json->encode($result['data']) === $this->json->encode($page['review']);

        return [...$result, 'allowed_actions' => $sameRevision && $sameReview ? $page['allowed_actions'] : []];
    }

    /**
     * @param  Business  $business
     * @param  array<string, mixed>  $review
     * @return list<string>
     */
    private function allowedActions(BusinessApplication $application, array $business, string $partyId, array $review): array
    {
        if ($application->status !== 'draft') {
            return [];
        }
        $permissions = [];
        foreach ($business['mandate']['people'] as $person) {
            if ($person['party_id'] === $partyId) {
                $permissions = $person['permissions'];
            }
        }
        $actions = array_values(array_intersect(['application.save', 'application.evaluate'], $permissions));
        if (in_array('application.sign', $permissions, true) && ($review['quote']['status'] ?? null) === 'ready'
            && $review['acceptance']['documents'] !== [] && $review['acceptance']['disclosures'] !== []) {
            foreach ($review['acceptance']['signers'] as $signer) {
                if ($signer['party_id'] === $partyId && $signer['state'] === 'pending') {
                    $actions[] = 'application.submit';
                }
            }
        }

        return $actions;
    }

    /**
     * @param  Business  $business
     * @param  Verification|null  $verification
     * @param  CreditSnapshot|null  $credit
     * @return array<string, mixed>
     */
    private function projectEvidence(array $business, ?array $verification, ?array $credit, BusinessApplication $application, ?BusinessApplicationQuote $quote): array
    {
        $current = $verification !== null && $verification['current'];
        $now = now('UTC')->toImmutable();
        $facts = $this->applicationEvidence->project($current, $verification['payload']['observations'] ?? [],
            [...($verification['payload']['review']['obligations'] ?? []), ...($credit['facts']['obligations'] ?? [])],
            $credit['facts']['history'] ?? null, $credit['facts']['restriction_active'] ?? false,
            $now->startOfMonth()->subMonth()->format('Y-m'), $now->format('Y-m'), $application->draft['term_months'] ?? 6,
            $verification['payload']['review']['recurring_owner_draw'] ?? '0');
        $scorecard = $quote?->payload['result']['scorecard'] ?? null;

        return ['version' => $this->evidenceVersion($verification), ...$facts,
            'business' => [...$business['profile'], 'officers' => array_map(fn (array $person): array => ['name' => $person['name'], 'role' => implode(', ', $person['roles'])], $business['mandate']['people'])],
            'verified' => ['registry' => $business['entity_kind'] === 'organization', 'statements' => $current],
            'rating' => ($scorecard['rating'] ?? null) === null ? null : ['score' => $scorecard['rating'], 'band' => strtolower($scorecard['band'])],
            'capacity' => $quote?->payload['result']['maximum_capacity']['offer']['principal'] ?? null];
    }

    /** @param Verification|null $verification */
    private function evidenceVersion(?array $verification): string
    {
        return $verification !== null && $verification['current'] ? $verification['sha256'] : 'unavailable';
    }

    /**
     * @param  Business  $business
     * @param  Release  $release
     * @return array<string, mixed>
     */
    private function agreement(BusinessApplication $application, array $business, BusinessApplicationQuote $quote, array $release): array
    {
        return ['application_id' => $application->id, 'quote_id' => $quote->id, 'quote_sha256' => $quote->sha256,
            'quote_revision' => $quote->revision, 'mandate_version' => $business['mandate_version'], 'mandate' => $business['mandate'],
            'release' => $release, 'policy_version' => ApplicationAcceptance::POLICY_VERSION,
            'listing_fee' => ['currency' => 'RWF', 'amount' => '0', 'basis' => 'CFG-01_MVP_WAIVER']];
    }

    /** @return list<BusinessApplicationSignature> */
    private function signatures(BusinessApplication $application, BusinessApplicationQuote $quote, string $bindingHash): array
    {
        $signatures = BusinessApplicationSignature::query()->where('business_application_id', $application->id)
            ->where('binding_sha256', $bindingHash)->orderBy('actor_party_id')->get()->values()->all();
        foreach ($signatures as $signature) {
            $payload = $signature->payload;
            if (! hash_equals($signature->sha256, hash('sha256', $this->json->encode($payload)))
                || $payload['signature_id'] !== $signature->id || $payload['application_id'] !== $application->id
                || $signature->business_application_quote_id !== $quote->id || $payload['quote_id'] !== $quote->id
                || $payload['consent_release_id'] !== $signature->consent_release_id
                || $payload['actor_party_id'] !== $signature->actor_party_id || $payload['binding_sha256'] !== $bindingHash
                || ! is_array($payload['agreement']) || hash('sha256', $this->json->encode($payload['agreement'])) !== $bindingHash) {
                throw new RuntimeException('APPLICATION_SIGNATURE_INTEGRITY_FAILED');
            }
        }

        return array_values($signatures);
    }

    /**
     * @param  array<string, mixed>  $agreement
     * @param  list<BusinessApplicationSignature>  $signatures
     * @return array<string, mixed>
     */
    private function reviewData(BusinessApplication $application, BusinessApplicationQuote $quote, array $agreement, array $signatures): array
    {
        return ['application' => $this->snapshot($application), 'quote' => $this->projectQuote($quote),
            'acceptance' => $this->projectAcceptance($agreement['mandate'], $agreement['mandate_version'], $agreement['release'], $signatures),
            'submission' => null];
    }

    /**
     * @param  Terms  $mandate
     * @param  Release|null  $release
     * @param  list<BusinessApplicationSignature>  $signatures
     * @return array<string, mixed>
     */
    private function projectAcceptance(array $mandate, int $mandateVersion, ?array $release, array $signatures): array
    {
        $required = $mandate['required_signatories'];
        $signed = [];
        foreach ($signatures as $signature) {
            $signed[$signature->actor_party_id] = $signature->payload['signed_at'];
        }
        $signers = [];
        foreach ($mandate['people'] as $person) {
            if (in_array($person['party_id'], $required, true)) {
                $signers[] = ['party_id' => $person['party_id'], 'name' => $person['name'], 'role' => 'Required signatory',
                    'state' => isset($signed[$person['party_id']]) ? 'signed' : 'pending', 'signed_at' => $signed[$person['party_id']] ?? null];
            }
        }

        return [
            'documents' => $release['documents'] ?? [], 'disclosures' => $release['disclosures'] ?? [],
            'fee_on_approval' => ['currency' => 'RWF', 'amount' => '0'],
            'mandate_version' => (string) $mandateVersion, 'signers' => $signers,
            'required_signatures' => count($required), 'signatures_complete' => array_diff($required, array_keys($signed)) === [],
        ];
    }

    /** @return array<string, mixed> */
    private function submissionProjection(BusinessApplication $application): array
    {
        return ['application_id' => $application->id, 'note_id' => null, 'timeline' => [
            ['stage' => 'submitted', 'state' => 'done'], ['stage' => 'under_review', 'state' => 'current'],
            ['stage' => 'approved', 'state' => 'pending'], ['stage' => 'published', 'state' => 'pending'],
        ]];
    }

    /** @return array<string, mixed> */
    private function submittedReview(BusinessApplication $application): array
    {
        return $this->submittedSnapshot($application)['review'];
    }

    /** @return array<string, mixed> */
    private function submittedSnapshot(BusinessApplication $application): array
    {
        $submission = BusinessApplicationSubmission::query()->where('business_application_id', $application->id)->whereKey($application->current_submission_id)->first();
        if ($submission === null || ! hash_equals($submission->sha256, hash('sha256', $this->json->encode($submission->payload)))) {
            throw new RuntimeException('APPLICATION_SUBMISSION_INTEGRITY_FAILED');
        }
        $payload = $submission->payload;
        if ($payload['submission_id'] !== $submission->id || $payload['application_id'] !== $application->id
            || $payload['application_revision'] !== $submission->revision || $payload['quote_id'] !== $submission->business_application_quote_id
            || $payload['binding_sha256'] !== $submission->binding_sha256
            || ! is_array($payload['agreement']) || hash('sha256', $this->json->encode($payload['agreement'])) !== $submission->binding_sha256) {
            throw new RuntimeException('APPLICATION_SUBMISSION_INTEGRITY_FAILED');
        }

        return $payload;
    }

    /**
     * @param  Verification|null  $verification
     * @param  CreditSnapshot|null  $credit
     * @param  array{last_complete_month: string, first_repayment_month: string}  $calendar
     * @return array<string, mixed>
     */
    private function calculate(string $requestedPrincipal, int $tenorMonths, ?array $verification, ?array $credit, ?string $acceptedPrincipal, array $calendar): array
    {
        if ($verification === null || ! $verification['current']) {
            return ['eligible' => false, 'code' => 'UNDERWRITING_EVIDENCE_REQUIRED'];
        }
        $verified = $verification['payload'];
        try {
            return $this->underwriting->evaluate(['requested_principal' => $requestedPrincipal, 'tenor_months' => $tenorMonths,
                'accepted_principal' => $acceptedPrincipal, 'months' => $verified['observations'], ...$calendar,
                'recurring_owner_draw' => $verified['review']['recurring_owner_draw'],
                'obligations' => [...$verified['review']['obligations'], ...($credit['facts']['obligations'] ?? [])],
                'history' => $credit['facts']['history'] ?? null, 'restriction_active' => $credit['facts']['restriction_active'] ?? false]);
        } catch (UnderwritingViolation $failure) {
            throw new CommandRejection($failure->reasonCode, 422, fieldErrors: [
                $acceptedPrincipal === null ? 'target' : 'accepted_principal' => ['Choose a permitted principal on the RWF 5,000 note grid.'],
            ]);
        }
    }

    /**
     * @param  Business  $business
     * @param  Verification|null  $verification
     * @param  CreditSnapshot|null  $credit
     */
    private function currentQuote(BusinessApplication $application, array $business, ?array $verification, ?array $credit): ?BusinessApplicationQuote
    {
        if ($application->current_quote_id === null) {
            return null;
        }
        $quote = BusinessApplicationQuote::query()->where('business_application_id', $application->id)->whereKey($application->current_quote_id)->first();
        if ($quote === null || ! hash_equals($quote->sha256, hash('sha256', $this->json->encode($quote->payload)))) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }
        $payload = $quote->payload;
        if ($payload['quote_id'] !== $quote->id || $payload['quote_revision'] !== $quote->revision || $payload['application_id'] !== $application->id || $payload['business_id'] !== $business['id']) {
            throw new RuntimeException('APPLICATION_QUOTE_INTEGRITY_FAILED');
        }
        if ($payload['draft'] !== $this->drafts->normalize($application->draft) || $payload['mandate_version'] !== $business['mandate_version']
            || $payload['evidence'] !== $this->evidenceBinding($verification) || $payload['credit'] !== $this->creditBinding($credit)
            || $payload['policy_version'] !== FlatReturnPricing::POLICY_VERSION || $payload['calculation_version'] !== ApplicationUnderwriting::VERSION
            || $payload['calendar']['first_repayment_month'] !== now('UTC')->format('Y-m')) {
            return null;
        }

        return $quote;
    }

    /**
     * @param  Verification|null  $verification
     * @return array{id: string, revision: int, sha256: string, current: bool}|null
     */
    private function evidenceBinding(?array $verification): ?array
    {
        return $verification === null ? null : ['id' => $verification['id'], 'revision' => $verification['revision'], 'sha256' => $verification['sha256'], 'current' => $verification['current']];
    }

    /**
     * @param  CreditSnapshot|null  $credit
     * @return array{id: string, revision: int, sha256: string}|null
     */
    private function creditBinding(?array $credit): ?array
    {
        return $credit === null ? null : ['id' => $credit['id'], 'revision' => $credit['revision'], 'sha256' => $credit['sha256']];
    }

    /** @return Application */
    public function get(int $userId, int $contextRevision, string $businessId, string $applicationId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            fn (): array => $this->snapshot($this->application($businessId, $applicationId)));
    }

    /** @return Application|null */
    public function current(int $userId, int $contextRevision, string $businessId): ?array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function () use ($businessId): ?array {
                $application = BusinessApplication::query()->where('business_id', $businessId)->where('status', 'draft')->first();

                return $application === null ? null : $this->snapshot($application);
            });
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        $permission = match ($command) {
            'create' => 'application.create',
            'save' => 'application.save',
            'evaluate' => 'application.evaluate',
            'submit' => 'application.sign',
            default => throw new CommandRejection('OPERATION_NOT_FOUND', 404),
        };
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

        return $this->journal->find('party:'.$partyId, 'application.'.$command, $requestId,
            function (string $type, string $id) use ($userId, $contextRevision, $permission, $partyId, $command): void {
                if ($type !== ($command === 'create' ? 'business' : 'application')) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $businessId = $type === 'business' ? $id : BusinessApplication::query()->whereKey($id)->value('business_id');
                if (! is_string($businessId)) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->authority->handle($userId, $contextRevision, $businessId, $permission, null,
                    function (array $business, array $identity) use ($partyId): void {
                        if (($identity['party']['id'] ?? null) !== $partyId) {
                            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                        }
                    });
            });
    }

    /** @return AuditApplication */
    public function audit(int $userId, int $contextRevision, string $assignmentId): array
    {
        return $this->assignments->handle($userId, $contextRevision, $assignmentId, function (array $work): array {
            $application = BusinessApplication::query()->where('business_id', $work['assignment']['business_id'])
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', ['submitted'])->orderByDesc('id')->first();

            return ['work' => $work, 'application' => $application === null ? null : ['id' => $application->id, 'revision' => $application->revision,
                'title' => $application->draft['title'], 'target' => $application->draft['target'], 'term_months' => $application->draft['term_months'],
                'use_of_funds' => $application->draft['use_of_funds']]];
        });
    }

    /** @return array<string, mixed> */
    private function projectQuote(BusinessApplicationQuote $quote): array
    {
        $payload = $quote->payload;
        $result = $payload['result'];
        if (! $result['eligible']) {
            return ['status' => 'refused', 'code' => $result['code'], 'message' => match ($result['code']) {
                'RESTRICTION_ACTIVE' => 'A current restriction prevents this offer.',
                'POLICY_INPUT_REQUIRED' => 'Required credit history is unavailable.',
                'DSCR_BELOW_CUTOFF' => 'The requested amount does not meet the affordability threshold.',
                'CAPACITY_BELOW_MINIMUM', 'EXPOSURE_LIMIT' => 'The available borrowing capacity is below the minimum offer.',
                default => 'Current verified evidence is required before an offer can be calculated.',
            }];
        }
        $offer = $result['capacity']['offer'];
        $instalments = array_values($offer['instalments']);

        return ['status' => 'ready', 'quote_id' => $quote->id, 'quote_revision' => $quote->revision,
            'policy_version' => $payload['policy_version'], 'evidence_version' => $payload['evidence']['sha256'],
            'calculation_version' => $payload['calculation_version'], 'mandate_version' => (string) $payload['mandate_version'],
            'requested_principal' => ['currency' => 'RWF', 'amount' => $payload['draft']['target']],
            'principal' => $offer['principal'], 'offered_principal' => $result['maximum_capacity']['offer']['principal'],
            'term_months' => $payload['draft']['term_months'], 'rate_pct' => $result['pricing']['percent'],
            'interest' => $offer['contractual_return'], 'total' => $offer['total'], 'units' => $offer['units'],
            'unit_price' => ['currency' => 'RWF', 'amount' => '5000'], 'reserve' => null,
            'schedule' => array_map(fn (int $index, array $amount): array => ['instalment' => $index + 1, 'amount' => $amount],
                array_keys($instalments), $instalments),
            'reason_codes' => $result['capacity']['reason_codes'],
            'rate_basis' => ['band' => strtolower($result['scorecard']['band']), 'floor_pct' => '10.0', 'cap_pct' => '15.0',
                'term_premium' => $result['pricing']['premium_percentage_points']]];
    }

    private function application(string $businessId, string $applicationId): BusinessApplication
    {
        return BusinessApplication::query()->where('business_id', $businessId)->whereKey($applicationId)->lockForUpdate()->first()
            ?? throw new CommandRejection('APPLICATION_NOT_FOUND', 404);
    }

    private function recordVersion(BusinessApplication $application, int $userId, string $partyId, int $mandateVersion): void
    {
        (new BusinessApplicationVersion)->forceFill(['business_application_id' => $application->id, 'revision' => $application->revision,
            'snapshot' => [...$this->snapshot($application), ...($application->current_quote_id === null ? [] : ['quote_id' => $application->current_quote_id]),
                ...($application->current_submission_id === null ? [] : ['submission_id' => $application->current_submission_id])], 'actor_user_id' => $userId, 'actor_party_id' => $partyId,
            'mandate_version' => $mandateVersion, 'policy_version' => 'engineering-2026-09-23.4'])->save();
    }

    /** @return Application */
    private function snapshot(BusinessApplication $application): array
    {
        return ['id' => $application->id, 'business_id' => $application->business_id, 'revision' => $application->revision,
            'status' => $application->status, 'step' => $application->step, 'draft' => $application->draft, 'mandate_version' => $application->mandate_version];
    }
}
