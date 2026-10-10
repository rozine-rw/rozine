<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `InvestorProfileProps` (MVP-INVESTOR-SCR-11) for the web page. It shapes the account and the facts
 * `GetInvestorProfile` read and adds real routes only, with a link for each of the design's menu
 * rows. Personal information is read-only: name and email from the account, the approved identity
 * document already masked; phone and address are not collected, so they are null. The security
 * center reports two-factor sign-in and opens the account's security settings, where the password
 * and two-factor are managed. Linked accounts are the wallet's verified funding methods; there is
 * no link or unlink command yet, so neither is offered. No statement is generated yet, so
 * Statements shows its empty state. Rozine Plus, the help center, the terms and the privacy note
 * have no approved content yet and show their empty state. The verification link opens the
 * verification flow, or the verified page once the identity is verified.
 */
class InvestorProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{section: string, name: string, email: string, member_since: string, kyc: string, investor_type: string, methods: list<array<string, mixed>>, document: array{id_type: string, id_number: string}|null, two_factor: bool} $data */
        $data = $this->resource;
        $verified = $data['kyc'] === 'verified';
        $profile = fn (array $query = []): array => self::link('investor.profile', $query);

        return ['section' => $data['section'],
            'identity' => ['name' => $data['name'], 'email' => $data['email'], 'investor_type' => $data['investor_type'], 'kyc' => $data['kyc'],
                'member_since' => $data['member_since']],
            'personal' => ['name' => $data['name'], 'email' => $data['email'], 'phone' => null, 'id_type' => $data['document']['id_type'] ?? null,
                'id_number' => $data['document']['id_number'] ?? null, 'address' => null],
            'security' => ['two_factor' => $data['two_factor']],
            'linked' => ['accounts' => array_map(fn (array $method): array => [...$method, 'verified' => true, 'unlink' => null], $data['methods']), 'banks' => []],
            'statements' => ['annual' => null, 'monthly' => []],
            'links' => ['deals' => self::link('investor.deals'), 'portfolio' => self::link('investor.portfolio'), 'market' => self::link('investor.market'), 'cart' => self::link('investor.cart'), 'profile' => $profile(),
                'wallet' => $verified ? self::link('investor.wallet') : null, 'notifications' => null, 'launcher' => self::link('dashboard'),
                'overview' => $profile(), 'personal' => $profile(['section' => 'personal']), 'plan' => $profile(['section' => 'plan']),
                'security' => $profile(['section' => 'security']), 'linked' => $profile(['section' => 'linked']), 'statements' => $profile(['section' => 'statements']),
                'help' => $profile(['section' => 'help']), 'terms' => $profile(['section' => 'terms']), 'privacy' => $profile(['section' => 'privacy']),
                'automation' => $profile(['section' => 'automation']), 'verification' => self::link($verified ? 'investor.verified' : 'investor.verification'),
                'security_settings' => self::link('security.edit')],
            'actions' => ['link_account' => null, 'logout' => ['url' => route('logout', [], false), 'method' => 'post']]];
    }

    /**
     * @param  array<string, string>  $query
     * @return array{url: string, method: 'get'}
     */
    private static function link(string $name, array $query = []): array
    {
        return ['url' => route($name, $query, false), 'method' => 'get'];
    }
}
