<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessAcademyProps`: the Business Academy (BRS v3.0 RDY-R07). `core` is the core syllabus of
 * the Business's stage (`level`, Foundation's while it has no stage), each module with where it
 * stands: passed, open, or waiting until `retake_at` after a failed test. `preview` is the next
 * stage's core, cards only. Passing every core module counts toward Ready (RDY-R05).
 *
 * @phpstan-import-type Course from \App\Application\Business\GetBusinessAcademy
 */
class BusinessAcademyResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Course $data */
        $data = $this->resource;
        $business = $data['business_id'];
        $link = fn (array $module): array => [...$module, 'link' => BusinessHomeResource::link($request, 'business.academy.module',
            ['business' => $business, 'module' => $module['key']])];

        return ['stage' => $data['stage'], 'level' => $data['level'], 'pass_mark' => $data['pass_mark'], 'questions' => $data['questions'],
            'core' => array_map($link, $data['core']), 'preview' => array_map($link, $data['preview']),
            'links' => ['close' => BusinessHomeResource::link($request, 'business.readiness', ['business' => $business]),
                'home' => BusinessHomeResource::link($request, 'business.show', ['business' => $business]), ...BusinessHomeResource::tabs($request, $business),
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get']]];
    }
}
