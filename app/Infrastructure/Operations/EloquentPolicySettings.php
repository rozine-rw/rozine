<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\PolicySettings;
use App\Domain\Operations\PolicyChange;
use Illuminate\Support\Facades\DB;

final class EloquentPolicySettings implements PolicySettings
{
    public function value(string $key): int
    {
        $value = DB::table('policy_settings')->where('key', $key)->value('value');

        return $value === null ? PolicyChange::SETTINGS[$key]['default'] : (int) $value;
    }

    public function values(): array
    {
        $stored = DB::table('policy_settings')->pluck('value', 'key');
        $values = [];
        foreach (PolicyChange::SETTINGS as $key => $setting) {
            $values[$key] = isset($stored[$key]) ? (int) $stored[$key] : $setting['default'];
        }

        return $values;
    }

    public function holdingCapPercent(string $partyId): int
    {
        $institution = $partyId !== '' && DB::table('institutional_investors')->where('party_id', $partyId)->exists();

        return $this->value($institution ? 'institution_cap_percent' : 'investor_cap_percent');
    }
}
