<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The superadmin's staging mail testers in the Admin console frame, beside the other console
 * sections this viewer may open. The server's own recipients are shown, never edited.
 *
 * @phpstan-type Page array{
 *     testers: list<array{id: string, email: string, added_by: string, added_at: string}>,
 *     server_recipients: list<string>, search: string, permissions: list<string>
 * }
 */
class StaffStagingMailTestersResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $name = (string) $request->user()?->name;
        $link = fn (string $route, array $parameters = [], string $method = 'get'): array => ['url' => route('staff.staging-mail-testers.'.$route, $parameters, false), 'method' => $method];

        return ['contract_version' => 'staff-staging-mail-testers-v1', 'server_time' => now()->toIso8601String(),
            // Only superadmin holds staging.mail.testers.manage.
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => mb_strtoupper(mb_substr($name, 0, 1)), 'role' => 'superadmin'],
            'nav' => [...StaffNavigation::links($request, $page['permissions']), 'mail_testers' => $link('index')],
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'],
            'server_recipients' => $page['server_recipients'],
            'testers' => array_map(fn (array $tester): array => [...$tester, 'remove' => $link('remove', ['tester' => $tester['id']], 'post')], $page['testers']),
            'add' => $link('store', [], 'post')];
    }
}
