<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** The Audit Partner's own notices for the Auditor bell, marked read through the Auditor app's routes. */
class AuditorNoticesResource extends InvestorNoticesResource
{
    protected const string NOTICES = 'auditor.notices';

    /** Security and support notices open the Audit Partner's own profile, never the Investor app's. */
    protected const array OWN_DESTINATIONS = [
        'security.password_changed' => ['auditor.profile', []],
        'security.new_device' => ['auditor.profile', []],
        'security.payout_account_linked' => ['auditor.profile', []],
        'security.payout_account_unlinked' => ['auditor.profile', []],
        'support.complaint_answered' => ['auditor.profile', []],
        'support.complaint_resolved' => ['auditor.profile', []],
    ];

    /**
     * A "New audit job" notice opens the job itself while the partner still holds it, and the Jobs
     * list once the job has gone to someone else or was withdrawn (#587).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $page = parent::toArray($request);
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        /** @var list<array{kind: string, facts: array<string, mixed>, link: array{url: string, method: 'get'}|null}> $notices */
        $notices = $page['notices'];
        $page['notices'] = array_map(fn (array $notice): array => $notice['kind'] === 'auditor.job_offered' && ($notice['facts']['held'] ?? false) === true
            && ($notice['facts']['outcome'] ?? null) !== 'expired'
            ? [...$notice, 'link' => $this->link($prefix, 'auditor.jobs.show', ['assignment' => (string) $notice['facts']['assignment_id']])] : $notice, $notices);

        return $page;
    }
}
