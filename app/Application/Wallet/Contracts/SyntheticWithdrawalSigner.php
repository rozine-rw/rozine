<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/** Signs a synthetic withdrawal outcome for the local, testing and UAT hooks. No live provider has one. */
interface SyntheticWithdrawalSigner
{
    /**
     * @param  array<string, string>  $fields
     * @return array<string, string>
     */
    public function sign(array $fields): array;
}
