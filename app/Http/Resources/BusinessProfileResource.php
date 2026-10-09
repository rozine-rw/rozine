<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessProfileProps`: the Profile tab, with every section of the design's menu. `registration`
 * is the verified profile and mandate as Business authority holds them; there is no contact,
 * address or certificate record and no profile command, so `company` and `save_company` are null
 * and nothing is editable. `team` is the mandate's people with their roles and permissions, for
 * Permissions & roles; there is no invite, removal or ownership-transfer command, so none is
 * offered. `security` reports whether two-factor sign-in is on, managed on the account's security
 * settings. Linked accounts and the legal documents have no Business read yet, so `linked` and
 * `legal` are null and their sections, like the support center, show an empty state. Every
 * Business read is refused unless its parties are verified, hence `verified`.
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
            'section' => $data['section'], 'landing' => $data['landing'], 'company' => null,
            'registration' => ['name' => $profile['name'], 'company_code' => $profile['company_code'], 'industry' => $profile['industry'],
                'district' => $profile['district'], 'established_year' => $profile['established_year'],
                'people' => array_map(fn (array $person): array => ['name' => $person['name'], 'roles' => $person['roles'],
                    'signatory' => in_array($person['party_id'], $mandate['required_signatories'], true)], $mandate['people']),
                'signatories_required' => count($mandate['required_signatories'])],
            'team' => array_map(fn (array $person): array => ['name' => $person['name'], 'roles' => $person['roles'], 'permissions' => $person['permissions'],
                'signatory' => in_array($person['party_id'], $mandate['required_signatories'], true)], $mandate['people']),
            'security' => ['two_factor' => $data['two_factor']],
            'provinces' => [], 'linked' => null, 'legal' => null,
            'links' => ['home' => BusinessHomeResource::link($request, 'business.show', $parameters), ...BusinessHomeResource::tabs($request, $parameters['business']),
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'back' => BusinessHomeResource::link($request, 'business.profile', $parameters),
                'sections' => array_combine(self::SECTIONS, array_map(fn (string $section): array => BusinessHomeResource::link($request, 'business.profile',
                    [...$parameters, 'section' => $section]), self::SECTIONS)),
                'security_settings' => ['url' => route('security.edit', [], false), 'method' => 'get'],
                'sign_out' => ['url' => route('logout', [], false), 'method' => 'post']],
            'actions' => ['save_company' => null]];
    }
}
