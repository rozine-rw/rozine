<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Auditor\ConfirmAuditStepUp;
use App\Application\Auditor\CosignAuditReport;
use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Auditor\SealAuditReport;
use App\Application\Auditor\StartAuditReport;
use App\Models\AuditReport;
use App\Models\AuditSigningKey;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;

final class AuditSealingFixture
{
    /** @return array<string, mixed> */
    public static function ready(int $signatories = 1, string $kind = 'flash', bool $findings = false, ?int $requiredSignatories = null, bool $recordConsent = true): array
    {
        $fixture = BusinessQuoteFixture::ready($signatories, $kind === 'flash' ? 'flash' : 'routine', $requiredSignatories, $recordConsent);
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
        $steps = $kind === 'flash'
            ? ['review' => [], 'check_in' => [], 'photos' => ['titles' => ['extra-1' => 'Stock room']],
                'ledger' => ['observed_stock' => $findings ? '37000000' : '38000000', 'reconciled' => true]]
            : ['statements' => [], 'count' => ['cash' => $findings ? '108000001' : '108000000', 'stock_units' => '190',
                'operational_status' => 'active', 'financial_proofs' => ['bank', 'momo'], 'inventory_proofs' => ['photo']],
                'photos' => ['titles' => ['extra-1' => 'Stock room']]];
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
}
