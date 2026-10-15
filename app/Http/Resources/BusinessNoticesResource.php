<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A Business's notices for its bell panel, in the same shape as the Investor's: each notice's kind
 * and facts for the client to word, the link to the screen that already holds its record, and the
 * action that marks it read.
 *
 * @phpstan-import-type Notice from \App\Application\Identity\Contracts\PartyNotices
 */
class BusinessNoticesResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{unread: int, notices: list<Notice>, business_id: string} $inbox */
        $inbox = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $business = $inbox['business_id'];

        return ['unread' => $inbox['unread'],
            'notices' => array_map(fn (array $notice): array => [...$notice,
                'link' => ['url' => match ($notice['kind']) {
                    'business.application_approved' => route($prefix.'business.applications.publish.show',
                        ['business' => $business, 'application' => (string) $notice['facts']['application_id']], false),
                    'business.report_sealed', 'business.report_review_closing' => route($prefix.'business.audit-reports.show',
                        ['business' => $business, 'report' => (string) $notice['facts']['report_id']], false),
                    'business.raise_expired', 'business.raise_funded', 'business.disbursement_sent' => route($prefix.'business.campaigns.show', ['business' => $business, 'campaign' => (string) $notice['facts']['campaign_id']], false),
                    'support.complaint_answered', 'support.complaint_resolved' => route('business.profile', ['business' => $business, 'section' => 'support'], false),
                    default => route($prefix.'business.wallet.show', ['business' => $business], false),
                }, 'method' => 'get'],
                'read_action' => $notice['read'] ? null
                    : ['url' => route($prefix.'business.notices.read', ['business' => $business, 'notice' => $notice['id']], false), 'method' => 'post'],
            ], $inbox['notices']),
            'actions' => ['read_all' => $inbox['unread'] === 0 ? null : ['url' => route($prefix.'business.notices.read_all', ['business' => $business], false), 'method' => 'post']]];
    }
}
