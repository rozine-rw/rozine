<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * A send-back recorded before the structured address (24 Nov 2026) names the old District and
 * Registered address items and keeps the old one-line address as what was sent. The address backfill
 * moved the answers to Province, District, Sector, Cell and Street, so an untouched address read as
 * changed on the resubmission (QA #771). Both old items become the one Registered address item, and
 * what was sent is reshaped the same way the answers were, so the comparison is like for like.
 */
return new class extends Migration
{
    private const array OLD_KEYS = ['company.district', 'company.registered_address'];

    public function up(): void
    {
        /** @var array<string, list<string>> $provinces */
        $provinces = (array) config('rwanda.districts', []);

        DB::table('role_applications')->where('role', 'business')->whereNotIn('status', ['approved', 'rejected'])->orderBy('id')
            ->eachById(function (object $application) use ($provinces): void {
                /** @var array<string, mixed> $state */
                $state = json_decode(Crypt::decryptString($application->state), true, flags: JSON_THROW_ON_ERROR);
                $revision = $state['revision'] ?? null;
                if (! is_array($revision)) {
                    return;
                }
                $items = [];
                $notes = [];
                $merged = false;
                foreach ((array) ($revision['items'] ?? []) as $item) {
                    if (is_array($item) && in_array($item['key'] ?? null, self::OLD_KEYS, true)) {
                        $notes[] = trim((string) ($item['note'] ?? ''));
                        if (! $merged) {
                            $items[] = ['key' => 'company.address', 'label' => 'Registered address', 'note' => ''];
                            $merged = true;
                        }

                        continue;
                    }
                    $items[] = $item;
                }
                foreach ($items as $at => $item) {
                    if (is_array($item) && ($item['key'] ?? null) === 'company.address' && $notes !== []) {
                        $items[$at]['note'] = mb_substr(implode(' ', array_filter($notes, fn (string $note): bool => $note !== '')), 0, 500);
                    }
                }
                $company = $revision['before']['answers']['company'] ?? null;
                if (! $merged && (! is_array($company) || array_key_exists('street', $company))) {
                    return;
                }
                if (is_array($company) && ! array_key_exists('street', $company)) {
                    $district = is_string($company['district'] ?? null) ? $company['district'] : '';
                    $province = '';
                    foreach ($provinces as $name => $districts) {
                        $province = in_array($district, $districts, true) ? $name : $province;
                    }
                    $street = mb_substr(trim(is_string($company['registered_address'] ?? null) ? $company['registered_address'] : ''), 0, 160);
                    unset($company['registered_address']);
                    $revision['before']['answers']['company'] = [...$company, 'province' => $province, 'district' => $district, 'sector' => '', 'cell' => '', 'street' => $street];
                }
                $revision['items'] = $items;
                $state['revision'] = $revision;
                DB::table('role_applications')->where('id', $application->id)->update(['state' => Crypt::encryptString((string) json_encode($state))]);
            });
    }

    /** Data reshape only: the old keys are no longer read, so there is nothing to undo. */
    public function down(): void {}
};
