<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessAcademyModuleProps`: one Academy module (BRS v3.0 RDY-R07): its flashcards, then, for a
 * core module whose test is open, the 10 questions drawn for `attempt`, with no answer key. `last`
 * is the latest attempt's result. A preview module has cards only.
 *
 * @phpstan-import-type Lesson from \App\Application\Business\GetBusinessAcademy
 */
class BusinessAcademyModuleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Lesson $data */
        $data = $this->resource;
        $business = $data['business_id'];

        return ['identity_context_revision' => $data['identity_context_revision'], 'key' => $data['key'], 'role' => $data['role'],
            'level' => $data['level'], 'title' => $data['title'], 'summary' => $data['summary'], 'cards' => $data['cards'], 'status' => $data['status'],
            'attempt' => $data['attempt'], 'test' => $data['test'], 'last' => $data['last'], 'pass_mark' => $data['pass_mark'],
            'links' => ['submit' => ['url' => route('business.academy.submit', ['business' => $business, 'module' => $data['key']], false), 'method' => 'post'],
                'close' => BusinessHomeResource::link($request, 'business.academy', ['business' => $business]),
                'home' => BusinessHomeResource::link($request, 'business.show', ['business' => $business]), ...BusinessHomeResource::tabs($request, $business),
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get']]];
    }
}
