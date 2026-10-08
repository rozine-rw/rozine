<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `C3InvestorWalletProps` (investor-primary-v1) for both transports. It shapes already computed,
 * authorized facts and adds real routes only: no amount is calculated here, no provider fact is
 * present, no `/preview/` route is emitted, and a destination with no live route yet is null.
 * Portfolio and Profile are web pages only, so the API carries their links as null.
 */
class InvestorWalletResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        $context = (int) $data['identity_context_revision'];
        $wallet = fn (array $query = []): array => self::link($request, 'investor.wallet', $query);
        $page = fn (string $name): ?array => $request->routeIs('api.*') ? null : self::link($request, $name);
        $movement = $data['history']['movement'];

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $context, 'server_time' => now()->toIso8601String(),
            'allowed_actions' => $data['allowed_actions'], 'wallet' => $data['wallet'], 'holds' => [], 'funding' => $data['funding'],
            'deposits' => array_map(fn (array $deposit): array => self::deposit($request, $context, $deposit), $data['deposits']),
            'history' => ['movement' => $movement,
                'filters' => array_map(fn (string $key): array => ['key' => $key, 'active' => $key === $movement, 'link' => $wallet(['movement' => $key])], ['external', 'internal']),
                'items' => array_map(fn (array $item): array => [...$item, 'link' => $wallet(['receipt' => $item['id']])], $data['history']['items']),
                'pagination' => ['next' => $data['history']['next_before'] === null ? null : $wallet(['movement' => $movement, 'before' => $data['history']['next_before']])]],
            'receipt' => self::receipt($request, $context, $data['receipt']), 'earnings' => null, 'exports' => null,
            'links' => ['deals' => self::link($request, 'investor.deals'), 'portfolio' => $page('investor.portfolio'), 'profile' => $page('investor.profile'),
                'wallet' => $wallet(), 'notifications' => null,
                'launcher' => self::link($request, $request->routeIs('api.*') ? 'identity.show' : 'dashboard'), 'close' => $wallet(),
                'deposit' => $wallet(['kind' => 'deposit']), 'link_account' => null, 'operation' => self::lookup($request, $context),
                'changes' => isset($data['changes_cursor']) ? self::link($request, 'changes.index', ['topics' => 'wallet', 'after' => (string) $data['changes_cursor']]) : null],
            'actions' => ['deposit' => ['url' => route(self::prefix($request).'investor.wallet.deposit', [], false), 'method' => 'post']]];
    }

    /**
     * A recorded deposit intent with its links: the intent receipt resolves through the operation
     * lookup by its own request; a credit receipt opens on the wallet.
     *
     * @param  array<string, mixed>  $deposit
     * @return array<string, mixed>
     */
    public static function deposit(Request $request, int $context, array $deposit): array
    {
        return [...$deposit, 'intent_receipt' => [...$deposit['intent_receipt'], 'link' => self::lookup($request, $context, $deposit['request_id'])],
            'credit_receipt' => $deposit['credit_receipt'] === null ? null
                : [...$deposit['credit_receipt'], 'link' => self::link($request, 'investor.wallet', ['receipt' => $deposit['credit_receipt']['receipt_id']])],
            'link' => self::link($request, 'investor.wallet', ['receipt' => $deposit['id']])];
    }

    /**
     * The operation lookup. Without a request id its url keeps the literal `{request_id}` token.
     *
     * @return array{url: string, method: 'get'}
     */
    public static function lookup(Request $request, int $context, ?string $requestId = null): array
    {
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $url = route(self::prefix($request).'investor.wallet.operations.show',
            ['request_id' => $requestId ?? $placeholder, 'command' => 'wallet.deposit', 'identity_context_revision' => $context], false);

        return ['url' => $requestId === null ? str_replace($placeholder, '{request_id}', $url) : $url, 'method' => 'get'];
    }

    /**
     * @param  array<string, mixed>|null  $receipt
     * @return array<string, mixed>|null
     */
    private static function receipt(Request $request, int $context, ?array $receipt): ?array
    {
        if ($receipt === null) {
            return null;
        }

        return $receipt['type'] === 'deposit' ? self::deposit($request, $context, $receipt['deposit'])
            : [...$receipt['entry'], 'link' => self::link($request, 'investor.wallet', ['receipt' => $receipt['entry']['id']]),
                'receipt' => [...$receipt['receipt'], 'link' => self::link($request, 'investor.wallet', ['receipt' => $receipt['entry']['id']])]];
    }

    /**
     * @param  array<string, string>  $query
     * @return array{url: string, method: 'get'}
     */
    private static function link(Request $request, string $name, array $query = []): array
    {
        return ['url' => route(self::prefix($request).$name, $query, false), 'method' => 'get'];
    }

    private static function prefix(Request $request): string
    {
        return $request->routeIs('api.*') ? 'api.v1.' : '';
    }
}
