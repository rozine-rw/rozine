<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessProfileProps`: the Profile tab. `registration` is the verified profile and mandate as
 * Business authority holds them; there is no contact, address or certificate record and no profile
 * command, so `company` and `save_company` are null and nothing is editable. Linked accounts and
 * the legal documents have no Business read yet, so those sections are null and left out of the
 * menu. Every Business read is refused unless its parties are verified, hence `verified`.
 */
class BusinessProfileResource extends JsonResource
{
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
            'provinces' => [], 'linked' => null, 'legal' => null,
            'links' => ['home' => BusinessHomeResource::link($request, 'business.show', $parameters), ...BusinessHomeResource::tabs($request, $parameters['business']),
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'back' => BusinessHomeResource::link($request, 'business.profile', $parameters),
                'sections' => ['company' => BusinessHomeResource::link($request, 'business.profile', [...$parameters, 'section' => 'company']),
                    'linked' => null, 'terms' => null, 'privacy' => null],
                'sign_out' => ['url' => route('logout', [], false), 'method' => 'post']],
            'actions' => ['save_company' => null]];
    }
}
