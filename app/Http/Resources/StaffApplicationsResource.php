<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\LoanCapacity;
use Brick\Math\BigRational;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffApplicationsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $route = ($request->routeIs('api.*') ? 'api.v1.' : '').'staff.applications.index';
        $filters = ['tab' => $page['tab'], 'search' => $page['search'], 'limit' => $page['limit']];
        $position = array_filter([...$filters, 'before' => $page['before']], fn (mixed $value): bool => $value !== null);
        $link = fn (array $query): array => ['url' => route($route, $query, false), 'method' => 'get'];
        $review = null;
        if ($page['selected'] !== null) {
            $release = (new StaffApplicationReleaseResource($page['release_page']))->resolve($request)['release'];
            $review = [...$page['selected'], 'state' => $release['state'] === 'released' ? 'approved' : 'submitted',
                'underwriting_basis' => $page['underwriting_basis'],
                'factors' => [], 'audit' => ['state' => null, 'sealed_at' => null], 'reviewer' => null, 'trail' => [],
                'links' => ['close' => $link($position), 'business' => null], 'actions' => (object) [], 'release' => $release];
        }
        $name = $request->user()->name;
        /** @var list<string> $roles */
        $roles = $page['roles'];
        /** @var list<string> $permissions */
        $permissions = $page['permissions'];

        return ['contract_version' => 'staff-applications-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()->getAuthIdentifier(), 'name' => $name, 'email' => $request->user()->email,
                'initials' => StaffViewerResource::initials($name), 'role' => in_array('superadmin', $roles, true) ? 'superadmin' : 'approver'],
            'nav' => (new StaffNavigationResource($permissions))->resolve($request),
            'badges' => ['applications' => $page['counts']['pending'], 'disbursements' => null], 'policy' => self::policy(),
            'search' => $page['search'], 'active_tab' => $page['tab'], 'tabs' => array_map(fn (string $tab): array => ['key' => $tab, 'count' => $page['counts'][$tab], 'link' => $link([...$filters, 'tab' => $tab])], ['pending', 'approved']),
            'applications' => array_map(function (array $entry) use ($link, $position): array {
                unset($entry['capacity'], $entry['use_of_funds']);

                return [...$entry, 'link' => $link([...$position, 'application' => $entry['id']]), 'approve_link' => null];
            }, $page['entries']), 'review' => $review, 'stage' => null,
            'pagination' => ['next' => $page['next_cursor'] === null ? null : $link([...$filters, 'before' => $page['next_cursor']])],
            'links' => ['operation' => StaffApplicationReleaseResource::lookup($request), 'changes' => $request->routeIs('api.*') ? null
                : ['url' => route('staff.changes.index', ['topics' => 'staff_queue', 'after' => (string) $page['changes_cursor']], false), 'method' => 'get']]];
    }

    /**
     * The underwriting rules this queue is decided under, read from the policy the engine applies:
     * the coverage offers are sized to, the coverage below which an application is rejected, and the
     * flat return band.
     *
     * @return list<array{key: string, value: string}>
     */
    public static function policy(): array
    {
        $coverage = fn (string $ratio): string => BigRational::of($ratio)->toScale(2).'×';

        return [['key' => 'target_dscr', 'value' => $coverage(LoanCapacity::TARGET_COVERAGE)],
            ['key' => 'audit_routing', 'value' => '≥ '.$coverage(LoanCapacity::MINIMUM_COVERAGE)],
            ['key' => 'rate_band', 'value' => FlatReturnPricing::MINIMUM_RETURN_PERCENT.'–'.FlatReturnPricing::MAXIMUM_RETURN_PERCENT.'%']];
    }
}
