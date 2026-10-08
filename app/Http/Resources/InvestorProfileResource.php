<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `InvestorProfileProps` (MVP-INVESTOR-SCR-11) for the web page. It shapes the account and the facts
 * `GetInvestorProfile` read and adds real routes only. Linked accounts are the wallet's verified
 * funding methods; there is no link or unlink command yet, so neither is offered. No statement is
 * generated yet, so Statements shows its empty state. The verification row opens the verification
 * flow, or the verified page once the identity is verified. Terms, privacy and notifications have
 * no live page yet and are null. The gated Auto-Deploy explainer is offered: it runs nothing.
 */
class InvestorProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{section: string, name: string, email: string, member_since: string, kyc: string, investor_type: string, methods: list<array<string, mixed>>} $data */
        $data = $this->resource;
        $verified = $data['kyc'] === 'verified';
        $profile = fn (array $query = []): array => self::link('investor.profile', $query);

        return ['section' => $data['section'],
            'identity' => ['name' => $data['name'], 'email' => $data['email'], 'investor_type' => $data['investor_type'], 'kyc' => $data['kyc'],
                'member_since' => $data['member_since']],
            'linked' => ['accounts' => array_map(fn (array $method): array => [...$method, 'verified' => true, 'unlink' => null], $data['methods']), 'banks' => []],
            'statements' => ['annual' => null, 'monthly' => []],
            'links' => ['deals' => self::link('investor.deals'), 'portfolio' => self::link('investor.portfolio'), 'profile' => $profile(),
                'wallet' => $verified ? self::link('investor.wallet') : null, 'notifications' => null, 'launcher' => self::link('dashboard'),
                'overview' => $profile(), 'linked' => $profile(['section' => 'linked']), 'statements' => $profile(['section' => 'statements']),
                'automation' => $profile(['section' => 'automation']), 'verification' => self::link($verified ? 'investor.verified' : 'investor.verification'),
                'terms' => null, 'privacy' => null],
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
