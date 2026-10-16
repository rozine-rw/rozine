<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

/**
 * The deterministic payout reconciliation (§10.8, §11.5), with RWF 0 tolerance. One authenticated
 * observation that matches the durable intent exactly and is final may be reconciled; anything
 * missing or contradicting stays unresolved, and a later observation never clears a conflict.
 */
final readonly class Reconciliation
{
    /** The intent facts an observation must repeat exactly. */
    public const array BOUND = ['operation_id', 'provider', 'provider_reference', 'environment', 'currency', 'amount', 'destination_sha256'];

    /**
     * @param  'matched_success'|'matched_failure'|'exception'|'open'  $decision
     * @param  list<string>  $causes
     */
    private function __construct(public string $decision, public array $causes) {}

    /**
     * The intent facts this observation contradicts or omits. A final observation must also carry
     * its authenticated effective instant.
     *
     * @param  array<string, string>  $intent  the durable intent's bound facts
     * @param  array<string, string|null>  $observed
     * @return list<string>
     */
    public static function mismatches(array $intent, array $observed): array
    {
        $causes = [];
        foreach (self::BOUND as $field) {
            if (! isset($intent[$field]) || $intent[$field] === '') {
                throw new DisbursementViolation('PAYOUT_INTENT_INCOMPLETE');
            }
            $value = $observed[$field] ?? null;
            if (! is_string($value) || ! hash_equals($intent[$field], $value)) {
                $causes[] = $field;
            }
        }
        $state = $observed['state'] ?? null;
        if (! is_string($state) || ! in_array($state, PayoutOutcome::STATES, true)) {
            $causes[] = 'state';
        } elseif (PayoutOutcome::isFinal($state) && ! self::instant($observed['effective_at'] ?? null)) {
            $causes[] = 'effective_at';
        }

        return $causes;
    }

    /**
     * The decision over every observation recorded for one intent.
     *
     * @param  list<array{state: string, disposition: string}>  $observations  in recorded order
     */
    public static function decide(array $observations): self
    {
        $blocking = [];
        $final = null;
        foreach ($observations as $observation) {
            if (! in_array($observation['disposition'], PayoutOutcome::DISPOSITIONS, true) || ! in_array($observation['state'], PayoutOutcome::STATES, true)) {
                throw new DisbursementViolation('PAYOUT_OBSERVATION_INVALID');
            }
            if (in_array($observation['disposition'], ['after_final', 'conflict', 'key_conflict', 'unverifiable'], true)) {
                $blocking[] = $observation['disposition'];
            }
            if ($observation['disposition'] === 'applied' && PayoutOutcome::isFinal($observation['state'])) {
                $final ??= $observation['state'];
            }
        }
        if ($blocking !== []) {
            return new self('exception', array_values(array_unique($blocking)));
        }

        return match ($final) {
            'succeeded' => new self('matched_success', []),
            'failed' => new self('matched_failure', []),
            default => new self('open', []),
        };
    }

    public function terminal(): bool
    {
        return $this->decision === 'matched_success' || $this->decision === 'matched_failure';
    }

    private static function instant(?string $value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})$/D', $value) === 1;
    }
}
