<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

/**
 * An account an Investor asks to link for deposits and withdrawals (BRS v2.0 KYC-R02, LED-R03/R04):
 * an MTN MoMo or Airtel Money number, or a bank account. It is shown only masked; the full number
 * is kept encrypted. Cards are not a payout rail in the MVP.
 */
final readonly class PayoutAccount
{
    /** The banks an account can be linked at, by code. */
    public const array BANKS = [
        'bk' => 'Bank of Kigali',
        'im' => 'I&M Bank Rwanda',
        'equity' => 'Equity Bank Rwanda',
        'ecobank' => 'Ecobank Rwanda',
        'access' => 'Access Bank Rwanda',
        'ncba' => 'NCBA Bank Rwanda',
        'bpr' => 'BPR Bank Rwanda',
        'gtbank' => 'GT Bank Rwanda',
    ];

    private const array NETWORKS = ['mtn' => 'MTN MoMo', 'airtel' => 'Airtel Money'];

    private function __construct(public string $kind, public string $label, public string $masked, public string $reference) {}

    /**
     * The account the form describes, or the reason each field cannot describe one.
     *
     * @param  array{type?: mixed, network?: mixed, bank?: mixed, number?: mixed}  $input
     * @return self|array<string, list<string>>
     */
    public static function fromInput(array $input): self|array
    {
        $number = is_string($input['number'] ?? null) ? $input['number'] : '';
        if (($input['type'] ?? null) === 'bank') {
            $bank = is_string($input['bank'] ?? null) ? $input['bank'] : '';
            if (! array_key_exists($bank, self::BANKS)) {
                return ['bank' => ['Choose your bank.']];
            }
            if (preg_match('/\A[0-9]{8,20}\z/', $number) !== 1) {
                return ['number' => ['Enter 8 to 20 digits of the account number.']];
            }

            return new self('bank', self::BANKS[$bank], 'Account ····'.substr($number, -4), $bank.':'.$number);
        }
        $network = is_string($input['network'] ?? null) ? $input['network'] : '';
        if (($input['type'] ?? null) !== 'mobile' || ! array_key_exists($network, self::NETWORKS)) {
            return ['network' => ['Choose MTN MoMo or Airtel Money.']];
        }
        if (preg_match('/\A07[0-9]{8}\z/', $number) !== 1) {
            return ['number' => ['Enter a full 10-digit mobile number starting with 07.']];
        }

        return new self($network, self::NETWORKS[$network], '+250 '.substr($number, 1, 3).' ···· '.substr($number, -3), $number);
    }

    /** The same account as one already linked, whatever its label. */
    public function sameAs(string $kind, string $reference): bool
    {
        return $this->kind === $kind && hash_equals($this->reference, $reference);
    }
}
