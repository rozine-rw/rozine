<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

/**
 * A deterministic failed closing (§11.3): cancel the unissued campaign, refund every commitment in
 * full without fee and release its original exposure, once, identified by its closing.
 */
final readonly class FailedClosing
{
    /**
     * @param  'approve_recheck'|'worker_recheck'|'reconciled_failure'  $cause
     * @param  list<string>  $causes
     */
    public function __construct(public string $closingId, public string $disbursementId, public string $cause, public array $causes) {}
}
