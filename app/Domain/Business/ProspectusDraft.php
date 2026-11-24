<?php

declare(strict_types=1);

namespace App\Domain\Business;

/**
 * Normalizes what a Business writes into its prospectus (BRS v3.0 PRO-R02) before a draft is saved.
 * A draft may be incomplete: whether it is ready to sign is for {@see ProspectusGates} to say. This
 * only refuses input that is not the right shape (an amount that is not whole francs, a month that
 * is not `YYYY-MM`) or that exceeds the storage ceilings, so a saved draft always evaluates.
 *
 * @phpstan-import-type Inputs from ProspectusGates
 */
final class ProspectusDraft
{
    public const int MAXIMUM_LINES = 20;

    public const int MAXIMUM_MONTHS = 60;

    public const int MAXIMUM_PROFILES = 20;

    public const int MAXIMUM_RISKS = 20;

    public const int LABEL_MAXIMUM = 120;

    public const int NAME_MAXIMUM = 160;

    /** A storage ceiling well above the P-G11 limits, so an over-long draft still saves and shows its fix. */
    public const int TEXT_CEILING = 20000;

    /**
     * @param  array<string, mixed>  $input
     * @return array{inputs: Inputs, errors: array<string, list<string>>}
     */
    public static function normalize(array $input): array
    {
        $errors = [];
        $text = function (mixed $value, string $field, int $maximum) use (&$errors): string {
            $text = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
            if ($value !== null && ! is_string($value)) {
                $errors[$field][] = 'Enter text.';
            } elseif (mb_strlen($text) > $maximum) {
                $errors[$field][] = "Keep this to {$maximum} characters.";
            }

            return $text;
        };
        $amount = function (mixed $value, string $field, bool $optional = false) use (&$errors): ?string {
            $amount = is_int($value) && $value >= 0 ? (string) $value : $value;
            if ($optional && ($amount === null || $amount === '')) {
                return null;
            }
            if (! is_string($amount) || ! preg_match('/^(0|[1-9][0-9]{0,14})$/D', $amount)) {
                $errors[$field][] = 'Enter a whole amount in francs.';

                return $optional ? null : '0';
            }

            return $amount;
        };
        $document = function (mixed $value, string $field) use (&$errors): ?string {
            if ($value === null || $value === '') {
                return null;
            }
            if (! is_string($value) || ! preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/D', strtolower($value))) {
                $errors[$field][] = 'Choose one of the documents you uploaded.';

                return null;
            }

            return strtolower($value);
        };
        $list = function (mixed $value, string $field, int $maximum) use (&$errors): array {
            if ($value === null) {
                return [];
            }
            if (! is_array($value) || ! array_is_list($value)) {
                $errors[$field][] = 'Send a list.';

                return [];
            }
            if (count($value) > $maximum) {
                $errors[$field][] = "Keep this to {$maximum} entries.";

                return [];
            }

            return array_map(fn (mixed $row): array => is_array($row) ? $row : [], $value);
        };

        $lines = [];
        foreach ($list($input['use_of_funds'] ?? null, 'use_of_funds', self::MAXIMUM_LINES) as $index => $row) {
            $lines[] = ['label' => $text($row['label'] ?? null, "use_of_funds.{$index}.label", self::LABEL_MAXIMUM),
                'amount' => (string) $amount($row['amount'] ?? null, "use_of_funds.{$index}.amount"),
                'quote_amount' => $amount($row['quote_amount'] ?? null, "use_of_funds.{$index}.quote_amount", true),
                'quote_document_id' => $document($row['quote_document_id'] ?? null, "use_of_funds.{$index}.quote_document_id")];
        }
        $forecast = [];
        foreach ($list($input['forecast'] ?? null, 'forecast', self::MAXIMUM_MONTHS) as $index => $row) {
            $month = $row['month'] ?? null;
            if (! is_string($month) || ! preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $month)) {
                $errors["forecast.{$index}.month"][] = 'Enter the month as YYYY-MM.';
                $month = '';
            }
            $forecast[] = ['month' => $month, 'revenue' => (string) $amount($row['revenue'] ?? null, "forecast.{$index}.revenue"),
                'costs' => (string) $amount($row['costs'] ?? null, "forecast.{$index}.costs"), 'contract_document_id' => $document($row['contract_document_id'] ?? null, "forecast.{$index}.contract_document_id")];
        }
        $profiles = [];
        foreach ($list($input['profiles'] ?? null, 'profiles', self::MAXIMUM_PROFILES) as $index => $row) {
            $role = $row['role'] ?? null;
            if (! in_array($role, ProspectusGates::PROFILE_ROLES, true)) {
                $errors["profiles.{$index}.role"][] = 'Choose director, owner or official representative.';
                $role = '';
            }
            $profiles[] = ['name' => $text($row['name'] ?? null, "profiles.{$index}.name", self::NAME_MAXIMUM), 'role' => $role,
                'text' => $text($row['text'] ?? null, "profiles.{$index}.text", self::TEXT_CEILING)];
        }
        $risks = [];
        foreach ($list($input['risks'] ?? null, 'risks', self::MAXIMUM_RISKS) as $index => $row) {
            $risks[] = ['text' => $text($row['text'] ?? null, "risks.{$index}.text", self::TEXT_CEILING), 'concentration' => ($row['concentration'] ?? false) === true];
        }
        $share = $input['largest_customer_share_percent'] ?? null;
        if (is_string($share) && preg_match('/^(0|[1-9][0-9]{0,2})$/D', $share)) {
            $share = (int) $share;
        }
        if ($share !== null && $share !== '' && (! is_int($share) || $share < 0 || $share > 100)) {
            $errors['largest_customer_share_percent'][] = 'Enter a whole percentage from 0 to 100.';
        }

        return ['inputs' => ['purpose' => $text($input['purpose'] ?? null, 'purpose', self::TEXT_CEILING), 'use_of_funds' => $lines,
            'forecast' => $forecast, 'assumptions' => $text($input['assumptions'] ?? null, 'assumptions', self::TEXT_CEILING),
            'downside' => $text($input['downside'] ?? null, 'downside', self::TEXT_CEILING), 'profiles' => $profiles,
            'largest_customer_share_percent' => is_int($share) && $share >= 0 && $share <= 100 ? $share : null, 'risks' => $risks], 'errors' => $errors];
    }

    /** @return Inputs */
    public static function blank(): array
    {
        return self::normalize([])['inputs'];
    }
}
