<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/** Signs a synthetic provider message for the local and testing hooks. No live provider has one. */
interface SyntheticEventSigner
{
    /**
     * @param  array<string, string>  $fields
     * @return array<string, string>
     */
    public function sign(array $fields): array;
}
