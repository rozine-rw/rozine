<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessAuditPrepProps`: "Get ready for your audit" drawn over the Business's real Home, for
 * the monthly audit its open routine assignment states. The window is open for as long as that
 * audit is, so `window_open` is always true here. The checklist is the page's own and nothing on
 * it is a command: the Audit Partner runs the audit.
 *
 * @phpstan-import-type AuditWindow from \App\Application\Business\Contracts\BusinessAuditWindows
 */
class BusinessAuditPrepResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        /** @var AuditWindow $audit */
        $audit = $data['audit_window'];
        $home = (new BusinessHomeResource($data))->toArray($request);

        return ['home' => $home, 'audit' => ['period' => $audit['period'], 'window_open' => true, 'days_left' => $audit['days_left'],
            'seal_by' => $audit['seal_by'], 'cosign_by' => $audit['cosign_by'], 'first' => $audit['first'], 'reassigned' => $audit['reassigned']],
            'links' => ['close' => $home['links']['home']]];
    }
}
