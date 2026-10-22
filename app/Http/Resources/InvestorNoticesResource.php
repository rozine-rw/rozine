<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

/**
 * The account's notices for the bell panel. Each notice carries its kind and facts for the client
 * to word, and the link to the screen that already holds its record, plus the action that marks it
 * read. A kind with no screen on this transport has a null link. An Audit Partner reads the same
 * notices of their own Party behind the Auditor bell, with that app's read actions.
 *
 * @phpstan-import-type Inbox from \App\Application\Identity\Contracts\PartyNotices
 */
class InvestorNoticesResource extends JsonResource
{
    /** Where each kind opens, by route name and parameters; a parameter starting with `:` takes that notice fact. */
    private const array DESTINATIONS = [
        'verification.approved' => ['investor.deals', []],
        'verification.rejected' => ['investor.verification', []],
        'wallet.deposit_received' => ['investor.wallet', []],
        'wallet.withdrawal_sent' => ['investor.wallet', []],
        'wallet.withdrawal_returned' => ['investor.wallet', []],
        'primary.refunded' => ['investor.wallet', []],
        'primary.report_published' => ['investor.portfolio', []],
        'primary.raise_funded' => ['investor.portfolio', []],
        'primary.holding_issued' => ['investor.holdings.show', ['holding' => ':holding_id']],
        'security.password_changed' => ['investor.profile', ['section' => 'security']],
        'security.new_device' => ['investor.profile', ['section' => 'security']],
        'security.payout_account_linked' => ['investor.profile', ['section' => 'linked']],
        'security.payout_account_unlinked' => ['investor.profile', ['section' => 'linked']],
        'auditor.accreditation_approved' => ['auditor.profile', []],
        'auditor.accreditation_rejected' => ['auditor.profile', []],
        'auditor.job_offered' => ['auditor.jobs.index', []],
        'auditor.accreditation_renewal_due' => ['auditor.profile', []],
        'auditor.report_reminder' => ['auditor.jobs.show', ['assignment' => ':assignment_id']],
        'support.complaint_answered' => ['investor.profile', ['section' => 'help']],
        'support.complaint_resolved' => ['investor.profile', ['section' => 'help']],
    ];

    /** The route names of the bell's read actions. */
    protected const string NOTICES = 'investor.notices';

    /** Kinds that open somewhere else in this app than in the Investor app, by route name and parameters. */
    protected const array OWN_DESTINATIONS = [];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Inbox $inbox */
        $inbox = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';

        return ['unread' => $inbox['unread'],
            'notices' => array_map(fn (array $notice): array => [...$notice,
                'link' => $this->link($prefix, $this->destination($notice['kind'])[0],
                    array_map(fn (string $value): string => str_starts_with($value, ':') ? (string) $notice['facts'][substr($value, 1)] : $value,
                        $this->destination($notice['kind'])[1])),
                'read_action' => $notice['read'] ? null : ['url' => route($prefix.static::NOTICES.'.read', ['notice' => $notice['id']], false), 'method' => 'post'],
            ], $inbox['notices']),
            'actions' => ['read_all' => $inbox['unread'] === 0 ? null : ['url' => route($prefix.static::NOTICES.'.read_all', [], false), 'method' => 'post']]];
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function destination(string $kind): array
    {
        return static::OWN_DESTINATIONS[$kind] ?? self::DESTINATIONS[$kind];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'get'}|null
     */
    private function link(string $prefix, string $name, array $parameters): ?array
    {
        return Route::has($prefix.$name) ? ['url' => route($prefix.$name, $parameters, false), 'method' => 'get'] : null;
    }
}
