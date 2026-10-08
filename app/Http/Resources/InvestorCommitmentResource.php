<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `C3InvestorCommitmentProps` (investor-primary-v1) for both transports. It shapes already
 * authorized facts and adds real routes only. No cancel action is offered yet: the server cannot
 * yet tell a raising campaign from a funded one here, so the cancel command alone decides.
 * A destination with no live route yet is null; Portfolio and Profile are web pages only, so the
 * API carries their links as null.
 */
class InvestorCommitmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{identity_context_revision: int, commitment: array<string, mixed>|null, refusal: array{code: string, status: int}|null} $data */
        $data = $this->resource;
        $context = $data['identity_context_revision'];
        $commitment = $data['commitment'];
        $wallet = self::link($request, 'investor.wallet');
        $page = fn (string $name): ?array => $request->routeIs('api.*') ? null : self::link($request, $name);

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $context, 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'commitment' => $commitment === null ? null : self::commitment($request, $context, $commitment),
            'refusal' => $data['refusal'],
            'links' => ['deals' => self::link($request, 'investor.deals'), 'portfolio' => $page('investor.portfolio'), 'profile' => $page('investor.profile'),
                'wallet' => $wallet, 'notifications' => null,
                'launcher' => self::link($request, $request->routeIs('api.*') ? 'identity.show' : 'dashboard'), 'close' => $wallet,
                'operation' => $commitment === null ? null : self::lookup($request, $context, $commitment)]];
    }

    /**
     * @param  array<string, mixed>  $commitment
     * @return array<string, mixed>
     */
    private static function commitment(Request $request, int $context, array $commitment): array
    {
        $link = self::link($request, 'investor.commitments.show', ['commitment' => (string) $commitment['id']]);
        /** @var array<string, mixed> $confirmation */
        $confirmation = $commitment['confirmation'];
        /** @var array<string, mixed>|null $refund */
        $refund = $commitment['refund'];
        $shown = $commitment;
        unset($shown['reservation_id']);

        return [...$shown, 'confirmation' => [...$confirmation, 'link' => $link],
            'refund' => $refund === null ? null : [...$refund, 'link' => self::lookup($request, $context, $commitment, (string) $refund['request_id'])],
            'actions' => ['cancel' => null], 'link' => $link];
    }

    /**
     * The `primary.cancel` lookup for this commitment's reservation. Without a request id its url
     * keeps the literal `{request_id}` token.
     *
     * @param  array<string, mixed>  $commitment
     * @return array{url: string, method: 'get'}
     */
    private static function lookup(Request $request, int $context, array $commitment, ?string $requestId = null): array
    {
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $url = route(self::prefix($request).'investor.primary.operations.show', ['campaign' => (string) $commitment['campaign_id'],
            'request_id' => $requestId ?? $placeholder, 'command' => 'primary.cancel', 'reservation' => (string) $commitment['reservation_id'],
            'identity_context_revision' => $context], false);

        return ['url' => $requestId === null ? str_replace($placeholder, '{request_id}', $url) : $url, 'method' => 'get'];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'get'}
     */
    private static function link(Request $request, string $name, array $parameters = []): array
    {
        return ['url' => route(self::prefix($request).$name, $parameters, false), 'method' => 'get'];
    }

    private static function prefix(Request $request): string
    {
        return $request->routeIs('api.*') ? 'api.v1.' : '';
    }
}
