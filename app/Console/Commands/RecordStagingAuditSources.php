<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Auditor\Contracts\AuditSourceFactsStore;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\UatMoneySimulation;
use App\Models\AuditAssignment;
use App\Models\AuditLocation;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Records the on-site capture a staging demo cannot make from a desk (Erastus, 17 Nov 2026): for
 * each accepted audit of a Business the UAT money simulation names, a synthetic check-in at the
 * Business's verified premises (a Kigali point when none is verified) and the required storefront
 * and inventory photos, timed a few minutes ago, as the accepted Auditor's isolated source facts.
 * The scheduler runs it every minute, so a newly accepted audit is ready to visit without anyone
 * running a command on the box. It does nothing wherever the simulation is off or fixtures are not
 * allowed, never touches any other Business, and never replaces facts already recorded.
 */
class RecordStagingAuditSources extends Command
{
    /** Central Kigali, used when the Business has no verified premises point. */
    public const array KIGALI = ['latitude' => '-1.9441', 'longitude' => '30.0619'];

    protected $hidden = true;

    protected $signature = 'staging:record-audit-sources';

    protected $description = 'Record synthetic check-in and photos for the UAT money simulation\'s accepted audits (UAT only)';

    public function handle(UatMoneySimulation $simulation, EnvironmentIsolation $isolation, AuditSourceFactsStore $store): int
    {
        if (! $simulation->active() || ! $isolation->canSeed()) {
            return self::SUCCESS;
        }
        $at = now('UTC')->subMinutes(5)->format('Y-m-d\TH:i:s\Z');
        $assignments = AuditAssignment::query()->where('status', 'accepted')->whereNotNull('party_id')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('audit_source_snapshots')
                ->whereColumn('audit_source_snapshots.assignment_id', 'audit_assignments.id')
                ->whereColumn('audit_source_snapshots.assignment_revision', 'audit_assignments.revision'))
            ->orderBy('id')->get(['id', 'business_id', 'party_id']);
        foreach ($assignments as $assignment) {
            $actorId = User::query()->where('party_id', $assignment->party_id)->orderBy('id')->value('id');
            if (! $simulation->covers($assignment->business_id) || ! is_int($actorId)) {
                continue;
            }
            $id = $store->recordSimulated($actorId, $assignment->id, self::facts($assignment->business_id, $at),
                'Staging demo: synthetic on-site check-in and photos for the UAT money simulation.');
            if ($id !== null) {
                $this->line($assignment->id.': check-in and photos recorded ('.$id.').');
            }
        }

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    public static function facts(string $businessId, string $at): array
    {
        $point = AuditLocation::query()->where('business_id', $businessId)->first()?->state['point'] ?? self::KIGALI;
        $position = ['latitude' => $point['latitude'], 'longitude' => $point['longitude'], 'accuracy_m' => 12];

        return ['declared_stock_rwf' => '38000000', 'declared_stock_units' => '190', 'declared_unit_label' => 'Sealed crates',
            'declared_sector_label' => 'Agriculture / Produce', 'declared_account_label' => 'Synthetic bank ending 4417',
            'check_in' => ['at' => $at, 'position' => $position, 'review_required' => false],
            'photos' => ['required' => [['id' => 'storefront', 'captured_at' => $at, 'position' => $position, 'title' => null],
                ['id' => 'inventory', 'captured_at' => $at, 'position' => $position, 'title' => null]], 'extra' => []],
            'proof_ids' => ['financial' => ['bank', 'momo'], 'inventory' => ['photo']]];
    }
}
