<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessWalletProps` (business-servicing-v1) for both transports. It shapes already computed,
 * authorized facts and adds real routes only: no amount is calculated here, no provider fact is
 * present, and a destination with no live route yet is null, as Repayments, Reports and Profile
 * are on the bearer transport.
 */
class BusinessWalletResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        $context = (int) $data['identity_context_revision'];
        $business = (string) $data['business']['id'];
        $wallet = fn (array $query = []): array => self::link($request, 'business.wallet.show', ['business' => $business, ...$query]);
        $movement = $data['history']['movement'];
        $range = array_filter($data['history']['range'], fn (?string $day): bool => $day !== null);
        $home = ['url' => $request->routeIs('api.*') ? route('api.v1.business.index', [], false) : route('business.show', ['business' => $business], false), 'method' => 'get'];

        return ['contract_version' => 'business-servicing-v1', 'identity_context_revision' => $context, 'server_time' => now()->toIso8601String(),
            'allowed_actions' => $request->routeIs('api.*') && ! $request->user()?->tokenCan('business:command') ? [] : $data['allowed_actions'],
            'business' => $data['business'], 'wallet' => $data['wallet'], 'funding' => $data['funding'],
            'deposits' => array_map(fn (array $deposit): array => self::deposit($request, $business, $context, $deposit), $data['deposits']),
            'withdrawals' => array_map(fn (array $withdrawal): array => self::withdrawal($request, $business, $context, $withdrawal), $data['withdrawals']),
            'history' => ['movement' => $movement,
                'filters' => array_map(fn (string $key): array => ['key' => $key, 'active' => $key === $movement, 'link' => $wallet(['movement' => $key, ...$range])], ['external', 'internal']),
                'items' => array_map(fn (array $item): array => [...$item, 'link' => $wallet(['receipt' => $item['id']])], $data['history']['items']),
                'pagination' => ['next' => $data['history']['next_before'] === null ? null : $wallet(['movement' => $movement, ...$range, 'before' => $data['history']['next_before']])],
                'range' => ['from' => $range['from'] ?? null, 'to' => $range['to'] ?? null, 'base' => $wallet(['movement' => $movement])]],
            'exports' => ['pdf' => self::link($request, 'business.wallet.statement', ['business' => $business, 'format' => 'pdf', ...$range]),
                'csv' => self::link($request, 'business.wallet.statement', ['business' => $business, 'format' => 'csv', ...$range])],
            'receipt' => self::receipt($request, $business, $context, $data['receipt']), 'bases' => (object) [],
            'shell_links' => ['home' => $home, 'launcher' => ['url' => route($request->routeIs('api.*') ? 'api.v1.identity.show' : 'dashboard', [], false), 'method' => 'get'],
                ...BusinessHomeResource::tabs($request, $business)],
            'links' => ['close' => $home, 'deposit' => $wallet(['kind' => 'deposit']), 'withdraw' => $wallet(['kind' => 'withdraw']),
                'operation' => self::lookup($request, $business, $context), 'withdrawal_operation' => self::lookup($request, $business, $context, null, 'business.wallet.withdraw'),
                'repayments' => $request->routeIs('api.*') ? null : self::link($request, 'business.repayments.show', ['business' => $business])],
            'actions' => ['deposit' => ['url' => route(self::prefix($request).'business.wallet.deposit', ['business' => $business], false), 'method' => 'post'],
                'withdraw' => ['url' => route(self::prefix($request).'business.wallet.withdraw', ['business' => $business], false), 'method' => 'post']]];
    }

    /**
     * A recorded deposit intent with its links: the intent receipt resolves through the operation
     * lookup by its own request; a credit receipt opens on the wallet.
     *
     * @param  array<string, mixed>  $deposit
     * @return array<string, mixed>
     */
    public static function deposit(Request $request, string $business, int $context, array $deposit): array
    {
        return [...$deposit, 'intent_receipt' => [...$deposit['intent_receipt'], 'link' => self::lookup($request, $business, $context, $deposit['request_id'])],
            'credit_receipt' => $deposit['credit_receipt'] === null ? null
                : [...$deposit['credit_receipt'], 'link' => self::link($request, 'business.wallet.show', ['business' => $business, 'receipt' => $deposit['credit_receipt']['receipt_id']])],
            'link' => self::link($request, 'business.wallet.show', ['business' => $business, 'receipt' => $deposit['id']])];
    }

    /**
     * A recorded withdrawal with its links: the request receipt resolves through the operation lookup
     * by its own request; the withdrawal and its ending receipt open on the wallet.
     *
     * @param  array<string, mixed>  $withdrawal
     * @return array<string, mixed>
     */
    public static function withdrawal(Request $request, string $business, int $context, array $withdrawal): array
    {
        $link = self::link($request, 'business.wallet.show', ['business' => $business, 'receipt' => $withdrawal['id']]);

        return [...$withdrawal,
            'request_receipt' => [...$withdrawal['request_receipt'], 'link' => self::lookup($request, $business, $context, $withdrawal['request_id'], 'business.wallet.withdraw')],
            'settled_receipt' => $withdrawal['settled_receipt'] === null ? null : [...$withdrawal['settled_receipt'], 'link' => $link], 'link' => $link];
    }

    /**
     * The operation lookup. Without a request id its url keeps the literal `{request_id}` token.
     *
     * @return array{url: string, method: 'get'}
     */
    public static function lookup(Request $request, string $business, int $context, ?string $requestId = null, string $command = 'business.wallet.deposit'): array
    {
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $url = route(self::prefix($request).'business.wallet.operations.show',
            ['business' => $business, 'request_id' => $requestId ?? $placeholder, 'command' => $command, 'identity_context_revision' => $context], false);

        return ['url' => $requestId === null ? str_replace($placeholder, '{request_id}', $url) : $url, 'method' => 'get'];
    }

    /**
     * @param  array<string, mixed>|null  $receipt
     * @return array<string, mixed>|null
     */
    private static function receipt(Request $request, string $business, int $context, ?array $receipt): ?array
    {
        if ($receipt === null) {
            return null;
        }
        if ($receipt['type'] === 'withdrawal') {
            return self::withdrawal($request, $business, $context, $receipt['withdrawal']);
        }
        $link = self::link($request, 'business.wallet.show', ['business' => $business, 'receipt' => $receipt['type'] === 'deposit' ? $receipt['deposit']['id'] : $receipt['entry']['id']]);

        return $receipt['type'] === 'deposit' ? self::deposit($request, $business, $context, $receipt['deposit'])
            : [...$receipt['entry'], 'link' => $link, 'receipt' => [...$receipt['receipt'], 'link' => $link]];
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
