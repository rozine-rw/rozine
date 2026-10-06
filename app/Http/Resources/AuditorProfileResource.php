<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Auditor Profile page (auditor-filing-v1, MVP-AUDITOR-SCR profile): the accreditation facts
 * and `allowed_actions` exactly as `AccreditationView` presents them, with the links and command
 * targets the page may use. Facts without a record yet stay null — the partner's firm,
 * professional body and start year, quality score and on-time share — and tabs whose routes do
 * not exist yet are null, so the page hides them rather than linking to nothing.
 *
 * @phpstan-type Accreditation array{contract_version: string, identity_context_revision: int, server_time: string, standing: array<string, mixed>, accreditation: array{submission: array{status: string, id?: string}}&array<string, mixed>, availability: array<string, mixed>, allowed_actions: list<string>}
 *
 * @phpstan-import-type Summary from \App\Application\Auditor\GetAuditEngagementSummary
 *
 * @phpstan-type Page array{accreditation: Accreditation, certificate_id: string|null, name: string, section: string, engagement: Summary}
 */
class AuditorProfileResource extends JsonResource
{
    /** The Profile sections the page shows; any other request lands on the first. */
    public const SECTIONS = ['accreditation', 'availability'];

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
            'section' => in_array($page['section'], self::SECTIONS, true) ? $page['section'] : self::SECTIONS[0],
            'auditor' => ['name' => $page['name'], 'firm' => null, 'accreditation' => null, 'avatar_url' => null, 'since_year' => null],
            'quality_score' => null,
            'on_time_pct' => null,
            'jobs_done' => 0,
            'accreditation' => $data['accreditation'],
            'standing' => $data['standing'],
            'availability' => [...$data['availability'], 'update' => self::action($prefix.'availability.update')],
            'actions' => [
                'submit' => self::action($prefix.'accreditation.submit'),
                'renew' => self::action($prefix.'accreditation.renew'),
                'withdraw' => self::action($prefix.'accreditation.withdraw'),
            ],
            'open_jobs' => 0,
            'links' => [
                'home' => self::link('auditor.home'),
                'jobs' => self::link('auditor.jobs.index'),
                'portfolio' => null,
                'profile' => self::link('auditor.profile'),
                'launcher' => self::link('dashboard'),
                'sections' => [
                    'accreditation' => self::link('auditor.profile'),
                    'availability' => self::link('auditor.profile', ['section' => 'availability']),
                ],
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
        return self::link('auditor.profile', $section === self::SECTIONS[0] ? [] : ['section' => $section]);
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

    /** @return array{url: string, method: 'get'} */
    private static function operationLink(string $prefix): array
    {
        $url = route($prefix.'operations.show', ['request_id' => self::REQUEST_ID_PLACEHOLDER], false);

        return ['url' => str_replace(self::REQUEST_ID_PLACEHOLDER, '{request_id}', $url), 'method' => 'get'];
    }
}
