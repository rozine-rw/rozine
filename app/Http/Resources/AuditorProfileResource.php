<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Auditor Profile page (auditor-filing-v1, MVP-AUDITOR-SCR profile): the accreditation facts
 * and `allowed_actions` exactly as `AccreditationView` presents them, with the links and command
 * targets the page may use. Jobs done counts the partner's filed reports and the open-offers
 * badge comes from their work as Jobs reads it. Facts without a record yet stay null — the
 * partner's firm, professional body and start year, quality score and on-time share — so the page
 * leaves them out rather than showing a stand-in. Every section of the design's menu is linked:
 * contact carries the account's own email, security whether two-factor sign-in is on, and legal
 * the engagement terms the partner accepted; earnings, the payout account, telemetry and the
 * academy have no read yet and show an empty state.
 *
 * @phpstan-type Accreditation array{contract_version: string, identity_context_revision: int, server_time: string, standing: array<string, mixed>, accreditation: array{submission: array{status: string, id?: string}}&array<string, mixed>, availability: array<string, mixed>, allowed_actions: list<string>}
 *
 * @phpstan-import-type Summary from \App\Application\Auditor\GetAuditEngagementSummary
 * @phpstan-import-type AuditApplication from \App\Application\Business\Contracts\BusinessApplicationStore
 *
 * @phpstan-type Page array{accreditation: Accreditation, certificate_id: string|null, name: string, email: string, two_factor: bool, section: string, engagement: Summary, jobs: array{data: list<AuditApplication>, next_cursor: string|null}, filed: int}
 */
class AuditorProfileResource extends JsonResource
{
    /** The design's Profile sections (Auditor.dc.html L3528–3536), in its menu order. */
    public const SECTIONS = ['earnings', 'contact', 'accreditation', 'payout', 'availability', 'telemetry', 'security', 'learn', 'legal'];

    /** Where a request for no section, or an unknown one, lands. */
    private const DEFAULT_SECTION = 'accreditation';

    /** A syntactically valid request ID, swapped for the literal token the page fills in. */
    private const REQUEST_ID_PLACEHOLDER = '00000000-0000-0000-0000-000000000000';

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $data = $page['accreditation'];
        $prefix = $request->routeIs('api.*') ? 'api.v1.auditor.' : 'auditor.';
        $submission = $data['accreditation']['submission'];

        return [
            'contract_version' => $data['contract_version'],
            'identity_context_revision' => $data['identity_context_revision'],
            'server_time' => $data['server_time'],
            'allowed_actions' => $data['allowed_actions'],
            'engagement' => (new AuditorEngagementSummaryResource($page['engagement']))->resolve($request),
            'section' => in_array($page['section'], self::SECTIONS, true) ? $page['section'] : self::DEFAULT_SECTION,
            'auditor' => ['name' => $page['name'], 'firm' => null, 'accreditation' => null, 'avatar_url' => null, 'since_year' => null],
            'quality_score' => null,
            'on_time_pct' => null,
            'jobs_done' => $page['filed'],
            'contact' => ['email' => $page['email'], 'phone' => null, 'address' => null],
            'security' => ['two_factor' => $page['two_factor']],
            'accreditation' => $data['accreditation'],
            'standing' => $data['standing'],
            'availability' => [...$data['availability'], 'update' => self::action($prefix.'availability.update')],
            'actions' => [
                'submit' => self::action($prefix.'accreditation.submit'),
                'renew' => self::action($prefix.'accreditation.renew'),
                'withdraw' => self::action($prefix.'accreditation.withdraw'),
            ],
            'open_jobs' => AuditorJobsResource::openJobs($page['jobs']),
            'links' => [
                'home' => self::link('auditor.home'),
                'jobs' => self::link('auditor.jobs.index'),
                'portfolio' => self::link('auditor.portfolio.index'),
                'profile' => self::link('auditor.profile'),
                'launcher' => self::link('dashboard'),
                'sections' => array_combine(self::SECTIONS, array_map(fn (string $section): array => $section === self::DEFAULT_SECTION
                    ? self::link('auditor.profile') : self::link('auditor.profile', ['section' => $section]), self::SECTIONS)),
                'security_settings' => self::link('security.edit'),
                'operation' => self::operationLink($prefix),
                'certificate' => $page['certificate_id'] === null ? null
                    : self::link($prefix.'accreditation.certificates.show', ['certificate' => $page['certificate_id']]),
                'submitted_certificate' => $submission['status'] === 'pending' && isset($submission['id'])
                    ? self::link($prefix.'accreditation.certificates.show', ['certificate' => $submission['id']]) : null,
            ],
        ];
    }

    /**
     * The Profile section a completed command continues to.
     *
     * @return array{url: string, method: 'get'}
     */
    public static function next(string $section): array
    {
        return self::link('auditor.profile', $section === self::DEFAULT_SECTION ? [] : ['section' => $section]);
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'get'}
     */
    private static function link(string $name, array $parameters = []): array
    {
        return ['url' => route($name, $parameters, false), 'method' => 'get'];
    }

    /** @return array{url: string, method: 'post'} */
    private static function action(string $name): array
    {
        return ['url' => route($name, [], false), 'method' => 'post'];
    }

    /**
     * The lookup for a recorded accreditation or availability command, with the literal token the
     * page fills in; Home shares it for its dispatch switch.
     *
     * @return array{url: string, method: 'get'}
     */
    public static function operationLink(string $prefix): array
    {
        $url = route($prefix.'operations.show', ['request_id' => self::REQUEST_ID_PLACEHOLDER], false);

        return ['url' => str_replace(self::REQUEST_ID_PLACEHOLDER, '{request_id}', $url), 'method' => 'get'];
    }
}
