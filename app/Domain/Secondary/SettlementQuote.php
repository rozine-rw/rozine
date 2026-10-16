<?php

declare(strict_types=1);

namespace App\Domain\Secondary;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * What one settlement moves (SEC-R06): the buyer pays the units at the asked price and nothing
 * more; the seller receives that less the 2% seller fee, rounded half up to a whole franc, which
 * is Rozine's revenue. The three amounts always balance.
 */
final readonly class SettlementQuote
{
    private function __construct(
        public BigInteger $units,
        public BigInteger $price,
        public BigInteger $gross,
        public BigInteger $buyerPays,
        public BigInteger $sellerFee,
        public BigInteger $sellerReceives,
    ) {}

    public static function of(string $units, string $price): self
    {
        $count = Quantity::units($units);
        $ask = Quantity::francs($price);
        $gross = $count->multipliedBy($ask);
        $buyerFee = $gross->multipliedBy(SecondaryTerms::BUYER_FEE_BPS)->dividedBy(10000, RoundingMode::HalfUp);
        $sellerFee = $gross->multipliedBy(SecondaryTerms::SELLER_FEE_BPS)->dividedBy(10000, RoundingMode::HalfUp);

        return new self($count, $ask, $gross, $gross->plus($buyerFee), $sellerFee, $gross->minus($sellerFee));
    }

    /** @return array{units: string, price: string, gross: string, buyer_pays: string, seller_fee: string, seller_receives: string, terms_version: string} */
    public function toArray(): array
    {
        return ['units' => (string) $this->units, 'price' => (string) $this->price, 'gross' => (string) $this->gross, 'buyer_pays' => (string) $this->buyerPays,
            'seller_fee' => (string) $this->sellerFee, 'seller_receives' => (string) $this->sellerReceives, 'terms_version' => SecondaryTerms::VERSION];
    }
}
