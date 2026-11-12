<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Brick\Math\BigInteger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessReadinessProps`: the Readiness tab (BRS v3.0 Section 20, APP-B01). It names the stage
 * the Business's verified monthly revenue has settled in and the RDY-R05 status line beside it.
 * Ready also needs every due requirement Met, the three signals on target and the stage's core
 * syllabus passed (RDY-R05); not all of those are measured yet, so the status is always `building`.
 * `pending` says when the newest readings point to another stage that has not settled yet
 * (`window` readings in a row move it), and `next` how far the stage above is. The months are the
 * published reports the VMR averages, newest first. Readiness is shown only inside the Business app.
 *
 * Every requirement of RDY-R03 is listed in BRS order: a triggered one with its status, the day
 * its grace ends and the days left, and one not yet triggered with the stage or headcount it
 * applies from. Ready is lost while any requirement is Overdue.
 *
 * `guidance` is the RDY-R06 line for each signal that has a reading, Record Gap then Forecast
 * Error: the value and target in percent, the verdict, the months averaged, the forecasts' bias
 * and one action. Cash Lead waits for collected instalments. `syllabus` counts the stage's core
 * Academy modules passed (RDY-R07), opened from `links.academy`. `milestones` lists the bond-track
 * and RSE Investment Clinic conditions (RDY-R08), each met, not met or pending while unmeasured.
 *
 * @phpstan-import-type Stage from \App\Domain\Business\ReadinessStage
 * @phpstan-import-type Listed from \App\Domain\Business\ReadinessRequirements
 * @phpstan-import-type Line from \App\Domain\Business\ReadinessSignals
 * @phpstan-import-type Track from \App\Domain\Business\ReadinessMilestones
 */
class BusinessReadinessResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{business_id: string, readiness: Stage, requirements: list<Listed>, guidance: list<Line>, syllabus: array{passed: int, total: int},
         *     milestones: array{bond: Track, clinic: Track}} $data */
        $data = $this->resource;
        $business = $data['business_id'];
        $readiness = $data['readiness'];
        $vmr = $readiness['vmr'];
        $next = $readiness['next'];
        $stage = $readiness['stage'];
        $pointing = $readiness['months_pointing'];

        return ['readiness' => ['stage' => $stage, 'status' => 'building', 'vmr' => $vmr === null ? null : self::money($vmr),
            'reports' => $readiness['reports'], 'reports_needed' => $readiness['reports_needed'], 'window' => $readiness['window'],
            'pending' => $stage === null || $pointing === 0 ? null : ['direction' => $readiness['indicated'] > $stage ? 'up' : 'down',
                'stage' => $readiness['indicated'], 'months' => $pointing],
            'next' => $next === null || $vmr === null ? null : ['stage' => $next['stage'], 'threshold' => self::money($next['threshold']),
                'gap' => self::money((string) BigInteger::max(BigInteger::of($next['threshold'])->minus($vmr), BigInteger::zero()))],
            'thresholds' => array_map(fn (array $row): array => ['stage' => $row['stage'], 'from' => self::money($row['from'])], $readiness['thresholds']),
            'months' => array_map(fn (array $month): array => ['period' => $month['period'], 'revenue' => self::money($month['revenue'])], $readiness['months'])],
            'requirements' => $data['requirements'],
            'guidance' => $data['guidance'],
            'syllabus' => $data['syllabus'],
            'milestones' => $data['milestones'],
            'links' => ['home' => BusinessHomeResource::link($request, 'business.show', ['business' => $business]), ...BusinessHomeResource::tabs($request, $business),
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'],
                'academy' => BusinessHomeResource::link($request, 'business.academy', ['business' => $business])]];
    }

    /** @return array{currency: 'RWF', amount: string} */
    private static function money(string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => $amount];
    }
}
