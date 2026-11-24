<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Auditor\ConfirmAuditStepUp;
use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\CosignAuditReport;
use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\ReviewAuditPublication;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Auditor\SealAuditReport;
use App\Application\Auditor\StartAuditReport;
use App\Models\AuditReport;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\AuditSigningKey;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Expectation;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;

final class AuditSealingFixture
{
    /** @return array<string, mixed> */
    public static function ready(int $signatories = 1, string $kind = 'flash', bool $findings = false, ?int $requiredSignatories = null, bool $recordConsent = true): array
    {
        $fixture = BusinessQuoteFixture::ready($signatories, match ($kind) {
            'flash' => 'flash', 'late' => 'arrears', default => 'routine',
        }, $requiredSignatories, $recordConsent);
        $acceptance = BusinessQuoteFixture::acceptance($fixture);
        for ($index = 0; $index < ($requiredSignatories ?? $signatories); $index++) {
            BusinessQuoteFixture::submit($fixture, $acceptance, $index);
        }
        $facts = AuditSourceFactsFixture::facts();
        $facts['photos']['required'][1]['captured_at'] = $facts['check_in']['at'];
        $facts['photos']['required'][1]['position'] = $facts['check_in']['position'];
        AuditSourceFactsFixture::record($fixture['audit']['staff'], $fixture['assignment']->refresh(), facts: $facts);
        $user = $fixture['audit']['partners'][0]['user'];
        $started = app(StartAuditReport::class)->handle($user->id, 1, $fixture['assignment']->id, $fixture['assignment']->refresh()->revision,
            $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid());
        $report = AuditReport::query()->whereKey($started['data']['audit_id'])->firstOrFail();
        $steps = match ($kind) {
            'late' => ['review' => [], 'check_in' => [], 'available' => ['cash_available' => $findings ? '0' : '450000', 'financial_proofs' => ['bank', 'momo']],
                'reason' => ['late_reason' => 'customers_owe', 'late_reason_note' => '']],
            'flash' => ['review' => [], 'check_in' => [], ...self::baseline($report), 'photos' => ['titles' => ['extra-1' => 'Stock room']],
                'ledger' => ['observed_stock' => $findings ? '37000000' : '38000000', 'reconciled' => true]],
            default => ['statements' => [], 'count' => ['cash' => $findings ? '108000001' : '108000000', 'stock_units' => '190',
                'operational_status' => 'active', 'financial_proofs' => ['bank', 'momo'], 'inventory_proofs' => ['photo']],
                'cash_sales' => ['undeposited_cash_sales' => '15000'], 'costs' => ['costs' => ['stock' => '400000', 'staff' => '250000', 'premises' => '80000', 'transport' => '30000', 'taxes' => '20000', 'other' => '0']],
                'drawings' => ['owner_drawings' => '0'], 'loans' => ['loans' => []],
                'differences' => ['explanations' => $findings ? [['cause' => 'entry_error', 'amount' => '1']] : []],
                'staff' => ['staff_count' => '6', 'rssb_checked' => true], 'expiries' => ['tax_clearance_expires_on' => '2027-03-31', 'rssb_clearance_expires_on' => '2027-06-30'],
                'loan_use' => ['loan_use' => 'as_planned', 'loan_use_note' => ''], 'changes' => ['changes' => ['new_debt' => false, 'director_change' => false, 'ownership_change' => false, 'legal_claim' => false]],
                'photos' => ['titles' => ['extra-1' => 'Stock room']]],
        };
        if ($findings) {
            $steps['seal'] = ['note' => 'Observed difference retained for review.'];
        }
        foreach ($steps as $step => $fields) {
            $saved = app(SaveAuditReportStep::class)->handle($user->id, 1, $report->id, $report->refresh()->revision, $step, $fields, (string) Str::uuid());
            if ($saved['code'] !== 'AUDIT_STEP_SAVED') {
                throw new RuntimeException('Synthetic sealing procedure did not advance: '.$saved['code']);
            }
        }

        return self::prepare([...$fixture, 'user' => $user, 'report' => $report]);
    }

    /**
     * The steps a business's first Flash visit adds as its baseline (BRS v3 VIS-R02); none for a
     * Flash report that is not the baseline.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function baseline(AuditReport $report): array
    {
        return ($report->binding['baseline'] ?? false) !== true ? [] : [
            'history' => ['history_checked' => true],
            'premises' => ['premises_tenure' => 'rented', 'assets' => [['item' => 'Delivery motorbike', 'value' => '1800000']]],
            'people' => ['owners_confirmed' => true, 'management_confirmed' => true],
            'registration' => ['registration_confirmed' => true, 'tax_clearance_expires_on' => '2027-03-31'],
            'trading' => ['trading_since' => '2021-06'],
        ];
    }

    /** @param array<string, mixed> $fixture
     * @return array<string, mixed>
     */
    public static function prepare(array $fixture): array
    {
        ['user' => $user, 'report' => $report] = $fixture;
        $user->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now()->subMinute()])->save();
        $key = AuditSigningKey::factory()->create();
        $page = app(GetAuditProcedure::class)->handle($user->id, 1, $report->id);
        $preview = $page['seal'];
        $ids = array_values(array_unique([$page['sources']['verification']['id'], $page['sources']['source_facts']['source']['id'],
            ...array_column($page['sources']['documents'], 'id'), ...array_column($page['sources']['ledger_documents'] ?? [], 'id')]));
        sort($ids);

        return [...$fixture, 'user' => $user, 'report' => $report->refresh(), 'key' => $key,
            'code' => (new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP'),
            'fields' => ['digest' => $preview['digest'], 'procedure_version' => $preview['payload']['procedure_version'],
                'findings_version' => $preview['findings_version'], 'evidence_version' => $preview['evidence_version'],
                'evidence_ids' => $ids, 'note' => $report->draft['note']]];
    }

    /** @param array<string, mixed> $fixture
     * @return array{proof: string, expires_at: string}
     */
    public static function proof(array $fixture): array
    {
        return app(ConfirmAuditStepUp::class)->handle($fixture['user']->id, 1, $fixture['report']->id,
            $fixture['report']->revision, $fixture['fields']['digest'], $fixture['code']);
    }

    /** @param array<string, mixed> $fixture
     * @param  array<string, mixed>|null  $fields
     * @return array<string, mixed>
     */
    public static function seal(array $fixture, ?string $proof = null, ?string $request = null, ?array $fields = null): array
    {
        return app(SealAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id,
            $fixture['report']->revision, $fields ?? $fixture['fields'], $proof ?? self::proof($fixture)['proof'], $request ?? (string) Str::uuid());
    }

    /**
     * Seals the report as it was sealed before its kind had a 24-hour review window: no publication
     * policy in the signed payload, so the publication opens under the legacy all-signers rule.
     *
     * @param  array<string, mixed>  $fixture
     * @return array<string, mixed>
     */
    public static function sealLegacy(array $fixture): array
    {
        $store = app(AuditReportPublicationStore::class);
        $event = 'eloquent.creating: '.AuditReportSeal::class;
        Event::listen($event, function (AuditReportSeal $seal): void {
            $payload = array_diff_key($seal->payload, ['publication_policy' => true]);
            $signature = app(AuditReportCryptography::class)->sign($payload);
            $seal->forceFill(['payload' => $payload, 'audit_signing_key_id' => $signature['key_id'], 'jws' => $signature['jws']]);
        });
        $legacy = Mockery::mock(AuditReportPublicationStore::class);
        $legacy->shouldReceive('open');
        $expectation = $legacy->mockery_findExpectation('open', []);
        if (! $expectation instanceof Expectation) {
            throw new RuntimeException('The legacy publication boundary was not registered.');
        }
        $expectation->once()->andReturnUsing(function (string $reportId, string $digest, array $payload) use ($store): void {
            $store->open($reportId, $digest, array_diff_key($payload, ['publication_policy' => true]));
        });
        app()->instance(AuditReportPublicationStore::class, $legacy);
        try {
            return self::seal($fixture);
        } finally {
            Event::forget($event);
            app()->instance(AuditReportPublicationStore::class, $store);
        }
    }

    /** @param array<string, mixed> $fixture
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function cosign(array $fixture, int $signer = 0, int $revision = 1, array $overrides = []): array
    {
        $values = [...['userId' => $fixture['audit']['authority']['users'][$signer]->id, 'contextRevision' => 1,
            'businessId' => $fixture['audit']['business'], 'reportId' => $fixture['report']->id, 'expectedRevision' => $revision,
            'reportRevision' => $fixture['report']->revision + 1, 'mandateVersion' => 1, 'digest' => $fixture['fields']['digest'],
            'accepted' => true, 'note' => '', 'requestId' => (string) Str::uuid()], ...$overrides];

        return app(CosignAuditReport::class)->handle(...$values);
    }

    /**
     * Publishes a sealed report the way BRS v3.0 AUD-R04 does: the Business does not co-sign, so
     * the report stands once its 24-hour dispute window ends. The clock moves to the window's end
     * only for the auto-approval and then returns, so the caller's own clock is unchanged.
     *
     * @param  array<string, mixed>  $fixture
     * @return array{status: string, code: string, published_at: string}
     */
    public static function publish(array $fixture): array
    {
        $publication = AuditReportPublication::query()->where('audit_report_id', $fixture['report']->id)->firstOrFail();
        $now = Date::hasTestNow() ? Date::now() : null;
        Date::setTestNow($publication->due_at);
        try {
            $result = app(ReviewAuditPublication::class)->advanceDue(100);
        } finally {
            Date::setTestNow($now);
        }
        if ($result['published'] < 1 || $publication->refresh()->status !== 'published') {
            throw new RuntimeException('Synthetic publication did not publish: '.json_encode($result['blocked']));
        }

        return ['status' => 'completed', 'code' => 'REPORT_PUBLISHED', 'published_at' => $publication->published_at->toIso8601String()];
    }
}
