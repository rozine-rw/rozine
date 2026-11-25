<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * The registered-address backfill (2026_11_24_120352) reshaped each open Business application's
 * answers into Province, District, Sector, Cell and Street, but not the copy of the answers kept
 * when Compliance sent it back. Staff then saw an untouched address as "Changed" (QA #771). This
 * reshapes that copy the same way, so an address the applicant left alone reads "Not changed".
 */
return new class extends Migration
{
    public function up(): void
    {
        /** @var array<string, list<string>> $provinces */
        $provinces = (array) config('rwanda.districts', []);

        DB::table('role_applications')->where('role', 'business')->whereNotIn('status', ['approved', 'rejected'])->orderBy('id')
            ->eachById(function (object $application) use ($provinces): void {
                /** @var array<string, mixed> $state */
                $state = json_decode(Crypt::decryptString($application->state), true, flags: JSON_THROW_ON_ERROR);
                $company = $state['revision']['before']['answers']['company'] ?? null;
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
                $state['revision']['before']['answers']['company'] = [...$company, 'province' => $province, 'district' => $district,
                    'sector' => '', 'cell' => '', 'street' => $street];
                DB::table('role_applications')->where('id', $application->id)->update(['state' => Crypt::encryptString((string) json_encode($state))]);
            });
    }

    /** Data reshaping only: the old single-line address is still readable from the street. */
    public function down(): void {}
};
