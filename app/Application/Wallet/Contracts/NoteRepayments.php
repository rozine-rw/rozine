<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/** Whether an issued note has been repaid in full, read from the repayments its Business made. */
interface NoteRepayments
{
    /** True once every instalment of the note has been paid; a note not issued yet is not repaid. */
    public function repaid(string $campaignId): bool;
}
