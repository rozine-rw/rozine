<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Auditor\WithCurrentAuditAssignment;
use App\Application\Business\Contracts\BusinessApplicationStore;
use App\Application\Business\Contracts\BusinessCreditFactsStore;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Evidence\WithBusinessStatementVerification;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Business\ApplicationDraft;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Underwriting\ApplicationUnderwriting;
use App\Domain\Underwriting\ExactFinancialValue;
use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\UnderwritingViolation;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
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
    public function save(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $fields, string $step, string $requestId): array
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
                        if (! in_array($step, ['business', 'raise'], true)) {
                            throw new CommandRejection('APPLICATION_STEP_INVALID', 422, $application->revision,
                                ['step' => ['A draft may resume at the Business or Raise step.']]);
                        }
                        $application->forceFill(['draft' => $fields, 'step' => $step, 'revision' => $expectedRevision + 1, 'current_quote_id' => null,
                            'mandate_version' => $business['mandate_version']])->save();
                        $this->recordVersion($application, $userId, $partyId, $business['mandate_version']);

                        return new OperationResult('APPLICATION_SAVED', ['application' => $this->snapshot($application)], $application->revision);
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

                            return new OperationResult('APPLICATION_SAVED', ['application' => $this->snapshot($application), 'quote' => $this->projectQuote($quote)], $application->revision);
                        });
                });
            });
    }

    /** @return array<string, mixed> */
    public function evaluate(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, ?string $acceptedPrincipal, string $requestId): array
    {
        return $this->verifications->handle($userId, $contextRevision, $businessId, 'application.evaluate', null,
            function (array $business, array $identity, ?array $verification) use ($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $acceptedPrincipal, $requestId): array {
                return $this->creditFacts->withCurrent($businessId, function (?array $credit) use ($business, $identity, $verification, $userId, $contextRevision, $applicationId, $businessId, $expectedRevision, $acceptedPrincipal, $requestId): array {
                    $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
                    $application = $this->application($businessId, $applicationId);

                    return $this->journal->execute('party:'.$partyId, $userId, 'application.evaluate', $requestId, 'application', $applicationId,
                        ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'accepted_principal' => $acceptedPrincipal],
                        function (): void {}, function () use ($application, $business, $verification, $credit, $userId, $partyId, $expectedRevision, $acceptedPrincipal): OperationResult {
                            $this->drafts->assertEditable($application->status, $application->revision, $expectedRevision);
                            $draft = $this->drafts->normalize($application->draft);
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

                            return new OperationResult('APPLICATION_EVALUATED', ['application' => $this->snapshot($application), 'quote' => $this->projectQuote($quote)], $application->revision);
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
                    $quote = $this->currentQuote($this->application($businessId, $applicationId), $business, $verification, $credit);

                    return $quote === null ? null : $this->projectQuote($quote);
                }));
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
            'rate_basis' => ['band' => $result['scorecard']['band'], 'floor_pct' => '10.0', 'cap_pct' => '15.0',
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
            'snapshot' => [...$this->snapshot($application), ...($application->current_quote_id === null ? [] : ['quote_id' => $application->current_quote_id])], 'actor_user_id' => $userId, 'actor_party_id' => $partyId,
            'mandate_version' => $mandateVersion, 'policy_version' => 'engineering-2026-09-23.4'])->save();
    }

    /** @return Application */
    private function snapshot(BusinessApplication $application): array
    {
        return ['id' => $application->id, 'business_id' => $application->business_id, 'revision' => $application->revision,
            'status' => $application->status, 'step' => $application->step, 'draft' => $application->draft, 'mandate_version' => $application->mandate_version];
    }
}
