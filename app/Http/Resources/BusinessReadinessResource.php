<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Business\ReadinessStage;
use Brick\Math\BigInteger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessReadinessProps`: the Readiness tab (BRS v3.0 Section 20, APP-B01). It names the stage
 * the Business's verified monthly revenue has settled in and the RDY-R05 status line beside it.
 * Ready also needs every due requirement Met, the three signals on target and the stage's core
 * syllabus passed (RDY-R05); none of those is measured yet, so the status is always `building`.
 * `pending` says when the newest readings point to another stage that has not settled yet
 * (`window` readings in a row move it), and `next` how far the stage above is. The months are the
 * published reports the VMR averages, newest first. Readiness is shown only inside the Business app.
 *
 * @phpstan-import-type Stage from ReadinessStage
 */
class BusinessReadinessResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{business_id: string, readiness: Stage} $data */
        $data = $this->resource;
        $business = $data['business_id'];
        $readiness = $data['readiness'];
        $vmr = $readiness['vmr'];
        $next = $readiness['next'];
        $stage = $readiness['stage'];
        $pointing = $readiness['months_pointing'];

        return ['readiness' => ['stage' => $stage, 'status' => 'building', 'vmr' => $vmr === null ? null : self::money($vmr),
            'reports' => $readiness['reports'], 'reports_needed' => $readiness['reports_needed'], 'window' => ReadinessStage::WINDOW,
            'pending' => $stage === null || $pointing === 0 ? null : ['direction' => $readiness['indicated'] > $stage ? 'up' : 'down',
                'stage' => $readiness['indicated'], 'months' => $pointing],
            'next' => $next === null || $vmr === null ? null : ['stage' => $next['stage'], 'threshold' => self::money($next['threshold']),
                'gap' => self::money((string) BigInteger::of($next['threshold'])->minus($vmr)->max(BigInteger::zero()))],
            'thresholds' => array_map(fn (int $number, string $threshold): array => ['stage' => $number, 'from' => self::money($threshold)],
                array_keys(ReadinessStage::THRESHOLDS), ReadinessStage::THRESHOLDS),
            'months' => array_map(fn (array $month): array => ['period' => $month['period'], 'revenue' => self::money($month['revenue'])], $readiness['months'])],
            'links' => ['home' => BusinessHomeResource::link($request, 'business.show', ['business' => $business]), ...BusinessHomeResource::tabs($request, $business),
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get']]];
    }

    /** @return array{currency: 'RWF', amount: string} */
    private static function money(string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => $amount];
    }
}
