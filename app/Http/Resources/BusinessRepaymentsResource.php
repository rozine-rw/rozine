<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessRepaymentsProps` (business-servicing-v1). Where servicing runs (UAT's money simulation),
 * a repaying note fills the sheet: servicing, schedule, Pay with the wallet's balance and recent
 * repayments; no late-fee ladder is sent, so none is shown. Otherwise no servicing domain is
 * bound, so no note of this Business has a schedule, an amount due or a payable option, and
 * `repayment.pay` is refused before anything is debited. The sheet therefore carries no note,
 * servicing, schedule, ladder or pay panel, and no Pay action; it opens on its empty state over
 * the Business's real Home, with the wallet's deposit panel as its only way on. `issued` lists each
 * issued Note's schedule as it was issued (principal and return per instalment, no paid amounts,
 * fees or current claim), so the Business sees what it will repay before servicing opens.
 * `visit_ready` is the D−14 visit-ready checklist for the next instalment (BRS v3 VIS-R03), or null.
 */
class BusinessRepaymentsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        $home = (new BusinessHomeResource($data))->toArray($request);
        $business = (string) $data['entry']['business_id'];
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $operation = route('business.repayments.operations.show', ['business' => $business, 'request_id' => $placeholder, 'command' => 'repayment.pay',
            'identity_context_revision' => $data['identity_context_revision']], false);

        $lookup = fn (string $requestId): array => ['url' => route('business.repayments.operations.show', ['business' => $business, 'request_id' => $requestId,
            'command' => 'repayment.pay', 'identity_context_revision' => $data['identity_context_revision']], false), 'method' => 'get'];
        /** @var array<string, mixed>|null $statement */
        $statement = $data['statement'] ?? null;
        $servicing = $statement === null ? ['allowed_actions' => [], 'note' => null, 'servicing' => null, 'schedule' => [], 'ladder' => null, 'pay' => null, 'recent' => [], 'pay_action' => null]
            : self::servicing($statement, (array) $data['wallet']['available'], route('business.repayments.pay', ['business' => $business], false), $lookup);

        return ['contract_version' => 'business-servicing-v1', 'identity_context_revision' => $data['identity_context_revision'], 'server_time' => now()->toIso8601String(),
            'allowed_actions' => $servicing['allowed_actions'], 'note' => $servicing['note'], 'servicing' => $servicing['servicing'], 'schedule' => $servicing['schedule'],
            'ladder' => null, 'pay' => $servicing['pay'], 'receipt' => null, 'recent' => $servicing['recent'],
            'issued' => $data['issued'] ?? [], 'visit_ready' => isset($data['visit_ready'])
                ? [...$data['visit_ready'], 'tick' => ['url' => route('business.repayments.visit-ready.tick', ['business' => $business], false), 'method' => 'post']] : null, 'bases' => (object) [], 'home' => $home,
            'shell_links' => ['home' => $home['links']['home'], 'launcher' => $home['links']['launcher'], 'reports' => $home['links']['reports'], 'readiness' => $home['links']['readiness'], 'market' => $home['links']['market'], 'profile' => $home['links']['profile']],
            'links' => ['close' => $home['links']['home'], 'top_up' => $home['links']['deposit'], 'operation' => ['url' => str_replace($placeholder, '{request_id}', $operation), 'method' => 'get']],
            'actions' => ['pay' => $servicing['pay_action']]];
    }

    /**
     * The repaying note's sheet: its servicing, schedule, what may be paid from the wallet now and
     * its recent repayments, each with its receipt lookup.
     *
     * @param  array<string, mixed>  $statement
     * @param  array<string, mixed>  $available
     * @param  \Closure(string): array{url: string, method: string}  $lookup
     * @return array{allowed_actions: list<string>, note: mixed, servicing: mixed, schedule: mixed, pay: array<string, mixed>, recent: list<array<string, mixed>>, pay_action: array{url: string, method: string}|null}
     */
    private static function servicing(array $statement, array $available, string $pay, \Closure $lookup): array
    {
        /** @var list<array{key: string, amounts: array{total: array{amount: string}}}> $options */
        $options = $statement['options'];
        /** @var list<array<string, mixed>> $recent */
        $recent = $statement['recent'];
        $sufficient = ['due_now' => false, 'next_instalment' => false];
        foreach ($options as $option) {
            $sufficient[$option['key']] = (int) $available['amount'] >= (int) $option['amounts']['total']['amount'];
        }
        /** @var array{revision: int} $servicing */
        $servicing = $statement['servicing'];

        return ['allowed_actions' => $options === [] ? [] : ['repayment.pay'], 'note' => $statement['note'], 'servicing' => $servicing,
            'schedule' => $statement['schedule'], 'pay' => ['options' => $options, 'funding' => ['available' => $available, 'sufficient' => $sufficient,
                'revision' => $servicing['revision']]],
            'recent' => array_map(fn (array $row): array => [...array_diff_key($row, ['request_id' => true]), 'link' => $lookup((string) $row['request_id'])], $recent),
            'pay_action' => $options === [] ? null : ['url' => $pay, 'method' => 'post']];
    }
}
