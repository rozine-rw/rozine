<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Audit dispatch matches districts, not a 30 km radius (Erastus, 24 Nov 2026). Each partner already
 * on file gets the districts and registered address from their approved application, or every
 * district when they were approved before the application asked, so nobody who was being offered
 * audits drops out of the pool. A verified partner who never changed their availability starts
 * accepting audits, as approval now does. Business applications still waiting in the old shape (one
 * district and a free-text address) get the province of that district and the address as the street,
 * leaving the sector and cell for Compliance to ask for.
 */
return new class extends Migration
{
    public function up(): void
    {
        /** @var array<string, list<string>> $provinces */
        $provinces = (array) config('rwanda.districts', []);
        $all = array_merge(...array_values($provinces));

        DB::table('auditor_profiles')->orderBy('id')->eachById(function (object $profile) use ($all): void {
            /** @var array<string, mixed> $state */
            $state = json_decode(Crypt::decryptString($profile->state), true, flags: JSON_THROW_ON_ERROR);
            if (array_key_exists('districts', $state)) {
                return;
            }
            $application = $this->answers((string) $profile->party_id, 'auditor');
            $chosen = array_values(array_intersect($all, (array) ($application['coverage']['districts'] ?? [])));
            $verified = ($state['certificate_id'] ?? null) !== null && ($state['standing']['status'] ?? null) === 'active';
            $toggled = DB::table('auditor_profile_versions')->where('auditor_profile_id', $profile->id)->where('command', 'availability.update')->exists();
            $state = [...$state, 'districts' => $chosen === [] ? $all : $chosen, 'max_jobs_per_week' => 4,
                'address' => is_array($application['address'] ?? null) ? $application['address'] : null,
                'accepting' => (bool) ($state['accepting'] ?? false) || ($verified && ! $toggled)];
            DB::table('auditor_profiles')->where('id', $profile->id)->update(['state' => Crypt::encryptString((string) json_encode($state))]);
        });

        DB::table('role_applications')->where('role', 'business')->whereNotIn('status', ['approved', 'rejected'])->orderBy('id')
            ->eachById(function (object $application) use ($provinces): void {
                /** @var array<string, mixed> $state */
                $state = json_decode(Crypt::decryptString($application->state), true, flags: JSON_THROW_ON_ERROR);
                $company = $state['answers']['company'] ?? null;
                if (! is_array($company) || array_key_exists('province', $company)) {
                    return;
                }
                $district = is_string($company['district'] ?? null) ? $company['district'] : '';
                $province = '';
                foreach ($provinces as $name => $districts) {
                    $province = in_array($district, $districts, true) ? $name : $province;
                }
                $street = mb_substr(trim(is_string($company['registered_address'] ?? null) ? $company['registered_address'] : ''), 0, 160);
                unset($company['registered_address']);
                $state['answers']['company'] = [...$company, 'province' => $province, 'district' => $district, 'sector' => '', 'cell' => '', 'street' => $street];
                DB::table('role_applications')->where('id', $application->id)->update(['state' => Crypt::encryptString((string) json_encode($state))]);
            });
    }

    /** Data backfill only: the new keys are read with defaults, so there is nothing to undo. */
    public function down(): void {}

    /** @return array<string, mixed> */
    private function answers(string $partyId, string $role): array
    {
        $row = DB::table('role_applications')->where('party_id', $partyId)->where('role', $role)->where('status', 'approved')->first(['state']);
        if ($row === null) {
            return [];
        }
        /** @var array{answers?: array<string, mixed>} $state */
        $state = json_decode(Crypt::decryptString($row->state), true, flags: JSON_THROW_ON_ERROR);

        return $state['answers'] ?? [];
    }
};
