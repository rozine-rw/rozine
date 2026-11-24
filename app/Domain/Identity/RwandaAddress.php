<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * A registered address in Rwanda (Erastus, 24 Nov 2026): the province, a district of that province,
 * and the sector, cell and (where there is one) street as typed. Businesses and Auditors give one, and audit dispatch
 * matches a Business's district against the districts an Auditor accepts, so no coordinates are
 * captured or verified.
 *
 * @phpstan-type Address array{province: string, district: string, sector: string, cell: string, street: string}
 */
final class RwandaAddress
{
    public const array FIELDS = ['province', 'district', 'sector', 'cell', 'street'];

    /**
     * The address the input describes, with the reason each field cannot describe one, keyed under
     * the prefix the form names the fields with (`company.district`, `address.sector`).
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, list<string>>  $provinces  the districts of each province
     * @return array{0: Address, 1: array<string, list<string>>}
     */
    public static function fromInput(array $input, array $provinces, string $prefix): array
    {
        $text = fn (string $key, int $max): string => mb_substr(trim(is_string($input[$key] ?? null) ? $input[$key] : ''), 0, $max);
        $address = ['province' => $text('province', 40), 'district' => $text('district', 40), 'sector' => $text('sector', 80),
            'cell' => $text('cell', 80), 'street' => $text('street', 160)];
        $errors = [];
        if (! array_key_exists($address['province'], $provinces)) {
            $errors[$prefix.'province'] = ['Choose the province.'];
        }
        if (! in_array($address['district'], $provinces[$address['province']] ?? [], true)) {
            $errors[$prefix.'district'] = ['Choose a district in the selected province.'];
        }
        foreach (['sector' => 'Enter the sector name.', 'cell' => 'Enter the cell name.'] as $field => $message) {
            if ($address[$field] === '' || preg_match('/[\p{Cc}\p{Cf}]/u', $address[$field]) === 1) {
                $errors[$prefix.$field] = [$message];
            }
        }
        // Many rural premises have no street name, so the street may stay empty, as on the Business's contact form.
        if (preg_match('/[\p{Cc}\p{Cf}]/u', $address['street']) === 1) {
            $errors[$prefix.'street'] = ['Enter the street as written, for example KG 5 Ave.'];
        }

        return [$address, $errors];
    }

    /**
     * The province a district belongs to, or an empty string when it is not a Rwandan district.
     *
     * @param  array<string, list<string>>  $provinces
     */
    public static function provinceOf(string $district, array $provinces): string
    {
        foreach ($provinces as $province => $districts) {
            if (in_array($district, $districts, true)) {
                return $province;
            }
        }

        return '';
    }

    /**
     * One line for screens and e-mails: street, cell, sector, district.
     *
     * @param  array<string, mixed>  $address
     */
    public static function line(array $address): string
    {
        $parts = array_map(fn (string $field): string => is_string($address[$field] ?? null) ? trim($address[$field]) : '', ['street', 'cell', 'sector', 'district']);

        return implode(', ', array_values(array_filter($parts, fn (string $part): bool => $part !== '')));
    }
}
