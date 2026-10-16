<?php

declare(strict_types=1);

namespace App\Domain\Business;

/**
 * A Business's contact details and registered address (Business.dc.html "Company information"): a
 * business e-mail, a ten-digit Rwandan phone number, and a district that belongs to the province
 * chosen with it, with the sector, cell and street as typed.
 *
 * @phpstan-type Contact array{email: string, phone: string, province: string, district: string, sector: string, cell: string, street: string}
 */
final readonly class BusinessContact
{
    public const array FIELDS = ['email', 'phone', 'province', 'district', 'sector', 'cell', 'street'];

    /** @param  Contact  $details */
    private function __construct(public array $details) {}

    /**
     * The contact the form describes, or the reason each field cannot describe one.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, list<string>>  $districts  the districts of each province
     * @return self|array<string, list<string>>
     */
    public static function fromInput(array $input, array $districts): self|array
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
        }
        $errors = [];
        if (mb_strlen($values['email']) > 180 || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = ['Enter a valid business e-mail address.'];
        }
        if (! preg_match('/^07[0-9]{8}$/D', $values['phone'])) {
            $errors['phone'] = ['Enter a ten-digit phone number starting with 07.'];
        }
        if (! in_array($values['district'], $districts[$values['province']] ?? [], true)) {
            $errors['district'] = ['Choose a district in the selected province.'];
        }
        foreach (['sector', 'cell'] as $field) {
            if ($values[$field] === '' || mb_strlen($values[$field]) > 80) {
                $errors[$field] = ['Enter the '.$field.' name.'];
            }
        }
        if (mb_strlen($values['street']) > 160) {
            $errors['street'] = ['Keep the street to 160 characters.'];
        }

        return $errors === [] ? new self([...$values, 'email' => strtolower($values['email'])]) : $errors;
    }
}
