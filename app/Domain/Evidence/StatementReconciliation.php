<?php

declare(strict_types=1);

namespace App\Domain\Evidence;

use App\Domain\Operations\CommandRejection;
use Brick\Math\BigInteger;
use DateTimeImmutable;

/**
 * Reconciles factual, versioned transcriptions. The caller must separately establish source
 * authenticity, complete rail declarations and Auditor approval; these results are unverified.
 *
 * @phpstan-type Rail array{id: string, active_from: string, active_until: string|null}
 * @phpstan-type DrawRef array{rail_id: string, month: string, reference: string}
 * @phpstan-type Transaction array{reference: string, date: string, amount: string, classification: string, source_ids: list<string>, return_of: DrawRef|null, exception_id: string|null}
 * @phpstan-type Statement array{rail_id: string, month: string, opening_balance: string, closing_balance: string, source_ids: list<string>, transactions: list<Transaction>}
 * @phpstan-type Observation array{month: string, operating_inflow: string, operating_outflow: string, owner_draw: string, debt_service: string, verified: false, source_ids: list<string>, rail_ids: list<string>, classification_version: string}
 */
final class StatementReconciliation
{
    public const VERSION = 'statement-classification-1';

    /**
     * $months and $availableSources come from the authorized observation window and source catalog.
     * Exception authority is resolved upstream per economic draw, never from request booleans.
     *
     * @param  list<Rail>  $rails
     * @param  list<string>  $months
     * @param  list<Statement>  $statements
     * @param  list<string>  $availableSources
     * @param  array<string, string>  $approvedDrawExceptions  Map of month/rail/reference to approved resolution evidence ID.
     * @return list<Observation>
     */
    public function reconcile(array $rails, array $months, array $statements, array $availableSources, array $approvedDrawExceptions = []): array
    {
        if ($rails === [] || $months === [] || count($months) > 36 || count(array_unique($months)) !== count($months)) {
            throw new CommandRejection('STATEMENT_INVENTORY_REQUIRED', 422);
        }
        sort($months);
        $previous = null;
        foreach ($months as $month) {
            $current = $this->month($month);
            if ($previous !== null && $previous->modify('+1 month')->format('Y-m') !== $month) {
                throw new CommandRejection('STATEMENT_MONTH_GAP', 422);
            }
            $previous = $current;
        }
        $expected = [];
        $railIds = [];
        foreach ($rails as $rail) {
            $this->identifier($rail['id']);
            $this->month($rail['active_from']);
            if ($rail['active_until'] !== null) {
                $this->month($rail['active_until']);
            }
            if (isset($railIds[$rail['id']]) || ($rail['active_until'] !== null && $rail['active_until'] < $rail['active_from'])) {
                throw new CommandRejection('STATEMENT_RAIL_CONFLICT', 422);
            }
            $railIds[$rail['id']] = true;
            foreach ($months as $month) {
                if ($month >= $rail['active_from'] && ($rail['active_until'] === null || $month <= $rail['active_until'])) {
                    $expected[$month.'/'.$rail['id']] = true;
                }
            }
        }
        $indexed = [];
        foreach ($statements as $statement) {
            $key = $statement['month'].'/'.$statement['rail_id'];
            if (! isset($expected[$key]) || isset($indexed[$key])) {
                throw new CommandRejection('STATEMENT_COVERAGE_CONFLICT', 422);
            }
            $this->sources($statement['source_ids'], $availableSources);
            $indexed[$key] = $statement;
        }
        if (count($indexed) !== count($expected)) {
            throw new CommandRejection('STATEMENT_RAIL_MONTH_REQUIRED', 422);
        }
        $observations = [];
        $closing = [];
        foreach ($months as $month) {
            $inflow = $outflow = $draws = $debt = BigInteger::zero();
            $sources = $active = $drawEntries = $returns = [];
            foreach ($rails as $rail) {
                $statement = $indexed[$month.'/'.$rail['id']] ?? null;
                if ($statement === null) {
                    continue;
                }
                $active[] = $rail['id'];
                $sources = array_merge($sources, $statement['source_ids']);
                $opening = $this->amount($statement['opening_balance']);
                $ending = $this->amount($statement['closing_balance']);
                if (isset($closing[$rail['id']]) && ! $opening->isEqualTo($closing[$rail['id']])) {
                    throw new CommandRejection('STATEMENT_BALANCE_CONTINUITY_REQUIRED', 422);
                }
                $balance = $opening;
                $seen = [];
                foreach ($statement['transactions'] as $transaction) {
                    $this->identifier($transaction['reference']);
                    $this->sources($transaction['source_ids'], $statement['source_ids']);
                    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $transaction['date']);
                    if ($date === false || $date->format('Y-m-d') !== $transaction['date'] || $date->format('Y-m') !== $month) {
                        throw new CommandRejection('STATEMENT_TRANSACTION_DATE_INVALID', 422);
                    }
                    $return = $transaction['return_of'];
                    $fingerprint = [$transaction['date'], $transaction['amount'], $transaction['classification'],
                        $return === null ? null : [$return['month'], $return['rail_id'], $return['reference']], $transaction['exception_id']];
                    if (isset($seen[$transaction['reference']])) {
                        if ($seen[$transaction['reference']] !== $fingerprint) {
                            throw new CommandRejection('STATEMENT_DUPLICATE_CONFLICT', 422);
                        }

                        continue;
                    }
                    $seen[$transaction['reference']] = $fingerprint;
                    $amount = $this->amount($transaction['amount']);
                    $kind = $transaction['classification'];
                    if (! in_array($kind, ['operating_inflow', 'operating_outflow', 'financing', 'transfer', 'owner_draw', 'owner_return', 'debt_service'], true)
                        || ($amount->isNegative() && in_array($kind, ['operating_inflow', 'owner_return'], true))
                        || ($amount->isPositive() && in_array($kind, ['operating_outflow', 'owner_draw', 'debt_service'], true))
                        || ($return !== null && $kind !== 'owner_return') || ($transaction['exception_id'] !== null && $kind !== 'owner_draw')) {
                        throw new CommandRejection('STATEMENT_CLASSIFICATION_REQUIRED', 422);
                    }
                    $balance = $balance->plus($amount);
                    $drawKey = $month.'/'.$rail['id'].'/'.$transaction['reference'];
                    if ($kind === 'owner_draw') {
                        $exception = $transaction['exception_id'];
                        if ($exception !== null) {
                            if (($approvedDrawExceptions[$drawKey] ?? null) !== $exception) {
                                throw new CommandRejection('DRAW_EXCEPTION_REVIEW_REQUIRED', 422);
                            }
                            $this->sources([$exception], $availableSources);
                            $sources[] = $exception;
                        }
                        $drawEntries[$drawKey] = ['amount' => $amount->abs(), 'exempt' => $exception !== null];
                        $draws = $draws->plus($exception === null ? $amount->abs() : 0);
                    } elseif ($kind === 'owner_return') {
                        if ($return === null || $this->month($return['month'])->format('Y-m') > $month) {
                            throw new CommandRejection('OWNER_RETURN_REFERENCE_REQUIRED', 422);
                        }
                        $this->identifier($return['rail_id']);
                        $this->identifier($return['reference']);
                        if ($return['month'] === $month) {
                            $returns[] = ['key' => $month.'/'.$return['rail_id'].'/'.$return['reference'], 'amount' => $amount];
                        }
                    } elseif ($kind === 'operating_inflow') {
                        $inflow = $inflow->plus($amount);
                    } elseif ($kind === 'operating_outflow') {
                        $outflow = $outflow->plus($amount->abs());
                    } elseif ($kind === 'debt_service') {
                        $debt = $debt->plus($amount->abs());
                    }
                }
                if (! $balance->isEqualTo($ending)) {
                    throw new CommandRejection('STATEMENT_RECONCILIATION_DIFFERENCE', 422);
                }
                $closing[$rail['id']] = $ending;
            }
            if ($active === []) {
                throw new CommandRejection('STATEMENT_RAIL_MONTH_REQUIRED', 422);
            }
            $returned = [];
            foreach ($returns as $return) {
                $draw = $drawEntries[$return['key']] ?? throw new CommandRejection('OWNER_RETURN_REFERENCE_REQUIRED', 422);
                $returned[$return['key']] = ($returned[$return['key']] ?? BigInteger::zero())->plus($return['amount']);
                if ($returned[$return['key']]->isGreaterThan($draw['amount'])) {
                    throw new CommandRejection('OWNER_RETURN_EXCEEDS_DRAW', 422);
                }
                if (! $draw['exempt']) {
                    $draws = $draws->minus($return['amount']);
                }
            }
            $sources = array_values(array_unique($sources));
            sort($sources);
            sort($active);
            $observations[] = ['month' => $month, 'operating_inflow' => (string) $inflow, 'operating_outflow' => (string) $outflow,
                'owner_draw' => (string) $draws, 'debt_service' => (string) $debt, 'verified' => false,
                'source_ids' => $sources, 'rail_ids' => $active, 'classification_version' => self::VERSION];
        }

        return $observations;
    }

    private function identifier(string $value): void
    {
        if (preg_match('/^[a-zA-Z0-9_-]{1,128}$/D', $value) !== 1) {
            throw new CommandRejection('STATEMENT_REFERENCE_INVALID', 422);
        }
    }

    private function month(string $month): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m', $month);
        if ($date === false || $date->format('Y-m') !== $month) {
            throw new CommandRejection('STATEMENT_MONTH_INVALID', 422);
        }

        return $date;
    }

    private function amount(string $amount): BigInteger
    {
        if (preg_match('/^(0|-?[1-9][0-9]{0,63})$/D', $amount) !== 1) {
            throw new CommandRejection('STATEMENT_AMOUNT_INVALID', 422);
        }

        return BigInteger::of($amount);
    }

    /**
     * @param  list<string>  $sources
     * @param  list<string>  $available
     */
    private function sources(array $sources, array $available): void
    {
        if ($sources === [] || array_diff($sources, $available) !== []) {
            throw new CommandRejection('STATEMENT_ORIGINAL_REQUIRED', 422);
        }
    }
}
