<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Domain\Wallet\WalletMoney;

/**
 * What a servicing note may be paid now, at one servicing revision. Each option carries the exact
 * total the Business must pay and the instalments it settles; an absent option is not payable.
 */
final readonly class ServicingQuote
{
    /** @param array<'due_now'|'next_instalment', array{total: WalletMoney, instalment_indexes: list<int>}> $options */
    public function __construct(public string $noteId, public string $title, public int $revision, public array $options) {}
}
