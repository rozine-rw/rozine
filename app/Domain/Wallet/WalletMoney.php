<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

use Brick\Math\BigInteger;

/**
 * Exact, non-negative whole Rwandan francs. Investor input must be a canonical positive integer of
 * at most twelve digits; stored values may be zero. There is no float path anywhere.
 */
final readonly class WalletMoney
{
    private function __construct(private BigInteger $value) {}

    /** A deposit amount as the Investor sent it, or null when it is not a canonical positive amount. */
    public static function fromInput(string $amount): ?self
    {
        return preg_match('/^[1-9][0-9]{0,11}$/D', $amount) === 1 ? new self(BigInteger::of($amount)) : null;
    }

    /** A value this system recorded. Anything else is an integrity failure. */
    public static function of(string $amount): self
    {
        if (preg_match('/^(0|[1-9][0-9]{0,19})$/D', $amount) !== 1) {
            throw new WalletViolation('WALLET_MONEY_INVALID');
        }

        return new self(BigInteger::of($amount));
    }

    public static function zero(): self
    {
        return new self(BigInteger::zero());
    }

    public function plus(self $other): self
    {
        return new self($this->value->plus($other->value));
    }

    /** Never negative: a subtraction that would go below zero is an integrity failure. */
    public function minus(self $other): self
    {
        $result = $this->value->minus($other->value);
        if ($result->isNegative()) {
            throw new WalletViolation('WALLET_MONEY_NEGATIVE');
        }

        return new self($result);
    }

    public function compareTo(self $other): int
    {
        return $this->value->compareTo($other->value);
    }

    public function isZero(): bool
    {
        return $this->value->isZero();
    }

    public function amount(): string
    {
        return (string) $this->value;
    }

    /** @return array{currency: 'RWF', amount: string} */
    public function money(): array
    {
        return ['currency' => 'RWF', 'amount' => $this->amount()];
    }
}
