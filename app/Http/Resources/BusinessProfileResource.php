<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessProfileProps`: the Profile tab, with every section of the design's menu. `registration`
 * is the verified profile and mandate as Business authority holds them. `company` is the contact
 * details and registered address the mandate keeps current: the latest saved version, or until the
 * first save an empty e-mail and phone with the verified district and its province. No RDB
 * certificate is held, so `company.certificate` is null; the people stay on `registration`.
 * `save_company` is offered only to a holder of `business.profile.update`. `team` is the mandate's
 * people with their roles and permissions, for Permissions & roles; there is no invite, removal or
 * ownership-transfer command, so none is offered. `security` reports whether two-factor sign-in is
 * on, managed on the account's security settings. Linked accounts lists the Business's own verified
 * accounts; there is no link or unlink command for a Business yet, so none is offered and only Rozine
 * support changes them. The legal documents have no Business read yet, so `legal` is null and its
 * section, like the support center, shows an empty state. Every Business read is refused unless its
 * parties are verified, hence `verified`.
 */
class BusinessProfileResource extends JsonResource
{
    /** The design's menu order (Business.dc.html L5022–5028); `GetBusinessProfile::SECTIONS` routes the same list. */
    private const array SECTIONS = ['company', 'security', 'permissions', 'linked', 'support', 'terms', 'privacy'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        $business = $data['business'];
        $profile = $business['profile'];
        $mandate = $business['mandate'];
        $parameters = ['business' => (string) $business['id']];

        return ['business' => ['name' => $profile['name'], 'address_line' => $profile['district'], 'verified' => true, 'rating' => $data['rating']],
            'section' => $data['section'], 'landing' => $data['landing'], 'company' => $this->company($data['contact'], $profile, count($mandate['required_signatories'])),
            'registration' => ['name' => $profile['name'], 'company_code' => $profile['company_code'], 'industry' => $profile['industry'],
                'district' => $profile['district'], 'established_year' => $profile['established_year'],
                'people' => array_map(fn (array $person): array => ['name' => $person['name'], 'roles' => $person['roles'],
                    'signatory' => in_array($person['party_id'], $mandate['required_signatories'], true)], $mandate['people']),
                'signatories_required' => count($mandate['required_signatories'])],
            'team' => array_map(fn (array $person): array => ['name' => $person['name'], 'roles' => $person['roles'], 'permissions' => $person['permissions'],
                'signatory' => in_array($person['party_id'], $mandate['required_signatories'], true)], $mandate['people']),
            'security' => ['two_factor' => $data['two_factor'], 'login_alerts' => $data['login_alerts'],
                'update_login_alerts' => ['url' => route('login-alerts.update', [], false), 'method' => 'put']],
            'provinces' => self::provinces(), 'legal' => null,
            'linked' => ['add' => null, 'accounts' => array_map(fn (array $method): array => ['id' => $method['id'],
                'kind' => $method['kind'] === 'bank' ? 'bank' : 'mobile_money', 'name' => $method['label'], 'detail' => $method['masked'], 'unlink' => null], $data['methods'])],
            'links' => ['home' => BusinessHomeResource::link($request, 'business.show', $parameters), ...BusinessHomeResource::tabs($request, $parameters['business']),
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'back' => BusinessHomeResource::link($request, 'business.profile', $parameters),
                'sections' => array_combine(self::SECTIONS, array_map(fn (string $section): array => BusinessHomeResource::link($request, 'business.profile',
                    [...$parameters, 'section' => $section]), self::SECTIONS)),
                'security_settings' => ['url' => route('security.edit', [], false), 'method' => 'get'],
                'sign_out' => ['url' => route('logout', [], false), 'method' => 'post']],
            'actions' => ['save_company' => $data['can_update_contact'] ? ['url' => route('business.profile.company', $parameters, false), 'method' => 'put'] : null]];
    }

    /**
     * @param  array{email: string, phone: string, province: string, district: string, sector: string, cell: string, street: string}|null  $contact
     * @param  array{name: string, district: string}  $profile
     * @return array<string, mixed>
     */
    private function company(?array $contact, array $profile, int $signatoriesRequired): array
    {
        $province = '';
        $district = '';
        foreach (self::districts() as $name => $districts) {
            foreach ($districts as $candidate) {
                if (strcasecmp($candidate, trim($profile['district'])) === 0) {
                    [$province, $district] = [$name, $candidate];
                }
            }
        }
        $details = $contact ?? ['email' => '', 'phone' => '', 'province' => $province, 'district' => $district, 'sector' => '', 'cell' => '', 'street' => ''];

        return ['name' => $profile['name'], 'email' => $details['email'], 'phone' => $details['phone'],
            'address' => ['province' => $details['province'], 'district' => $details['district'], 'sector' => $details['sector'],
                'cell' => $details['cell'], 'street' => $details['street']],
            'certificate' => null, 'signatories' => [], 'signatories_required' => $signatoriesRequired];
    }

    /** @return list<array{value: string, label: string, districts: list<array{value: string, label: string}>}> */
    private static function provinces(): array
    {
        $provinces = [];
        foreach (self::districts() as $name => $districts) {
            $provinces[] = ['value' => $name, 'label' => $name,
                'districts' => array_map(fn (string $district): array => ['value' => $district, 'label' => $district], $districts)];
        }

        return $provinces;
    }

    /** @return array<string, list<string>> */
    private static function districts(): array
    {
        /** @var array<string, list<string>> $districts */
        $districts = config('rwanda.districts');

        return $districts;
    }
}
