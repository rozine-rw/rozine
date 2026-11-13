<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use Closure;

/**
 * A Business's monthly submissions (BRS v3.0 VIS-R04), one per business and period, final once
 * sent. A submission is journaled under the acting Party with only a digest of the figures.
 *
 * @phpstan-import-type Figures from \App\Domain\Business\MonthlySubmission
 *
 * @phpstan-type Decided array{period: string, forecast_period: string, figures: Figures}
 * @phpstan-type Stored array{period: string, forecast_period: string, figures: Figures, submitted_at: string}
 */
interface MonthlySubmissionStore
{
    /**
     * The Business's submission for `$period`, or null when none was sent.
     *
     * @return Stored|null
     */
    public function find(string $businessId, string $period): ?array;

    /**
     * Every submission the Business has sent, newest period first. Read only behind the caller's
     * own Business authority.
     *
     * @return list<Stored>
     */
    public function forBusiness(string $businessId): array;

    /**
     * Sends a submission for a Business the person may view. `$decide` receives the Business id
     * under that authority and returns what to record, or refuses; a period already sent is refused.
     *
     * @param  array<string, mixed>  $input  the form as sent, digested into the journal
     * @param  Closure(string): Decided  $decide
     * @return array<string, mixed>
     */
    public function submit(int $userId, int $contextRevision, string $businessId, string $requestId, array $input, Closure $decide): array;
}
