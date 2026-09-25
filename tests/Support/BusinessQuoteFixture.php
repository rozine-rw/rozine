<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\EvaluateBusinessApplication;
use App\Application\Business\GetBusinessApplicationQuote;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Evidence\IngestStatement;
use App\Application\Evidence\RecordStatementTranscription;
use App\Models\AuditAssignment;
use App\Models\BusinessApplication;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type Fixture from AuditAssignmentFixture as AuditFixture
 *
 * @phpstan-type Fixture array{audit: AuditFixture, application: BusinessApplication, assignment: AuditAssignment}
 */
final class BusinessQuoteFixture
{
    /** @return Fixture */
    public static function make(bool $credit = true): array
    {
        $audit = AuditAssignmentFixture::make(1);
        $owner = $audit['authority']['users'][0];
        $created = app(CreateBusinessApplication::class)->handle($owner->id, 1, $audit['business'], 0, (string) Str::uuid());
        $application = BusinessApplication::query()->whereKey($created['data']['application']['id'])->firstOrFail();
        app(SaveBusinessApplication::class)->handle($owner->id, 1, $audit['business'], $application->id, 1,
            BusinessApplicationFixture::fields('12000000'), 'raise', (string) Str::uuid());
        $first = now('UTC')->toImmutable()->startOfMonth()->subMonths(36);
        $csv = "date,reference,amount\n";
        for ($index = 0; $index < 36; $index++) {
            $day = $first->addMonths($index)->format('Y-m-d');
            $csv .= "{$day},sales,4000000\n{$day},costs,-1000000\n";
        }
        $original = app(IngestStatement::class)->handle($owner->id, 1, $audit['business'], 0, 'synthetic-quote-history.csv', $csv, (string) Str::uuid());
        $source = $original['data']['document_id'];
        $months = $statements = [];
        for ($index = 0; $index < 36; $index++) {
            $date = $first->addMonths($index);
            $month = $date->format('Y-m');
            $months[] = $month;
            $statements[] = ['rail_id' => 'bank-a', 'month' => $month, 'opening_balance' => (string) ($index * 3000000),
                'closing_balance' => (string) (($index + 1) * 3000000), 'source_ids' => [$source], 'transactions' => [
                    StatementFixture::transaction('sales', '4000000', 'operating_inflow', $date->format('Y-m-d'), $source),
                    StatementFixture::transaction('costs', '-1000000', 'operating_outflow', $date->format('Y-m-d'), $source),
                ]];
        }
        $transcribed = app(RecordStatementTranscription::class)->handle($owner->id, 1, $audit['business'], 1,
            [['id' => 'bank-a', 'active_from' => $first->format('Y-m'), 'active_until' => null]], $months, $statements, (string) Str::uuid());
        $assignment = AuditAssignmentFixture::request($audit);
        AuditAssignmentFixture::respond($audit['partners'][0]['user'], $assignment);
        $review = StatementFixture::review([$source => hash('sha256', $csv)]);
        $review['recurring_owner_draw'] = '0';
        AuditAssignmentFixture::verifyStatements($audit, $assignment->refresh(), $transcribed['data']['transcription']['id'], $review);
        if ($credit) {
            BusinessCreditFactsFixture::record($audit['staff'], $audit['business']);
        }

        return ['audit' => $audit, 'application' => $application->refresh(), 'assignment' => $assignment];
    }

    /**
     * @param  Fixture  $fixture
     * @return array<string, mixed>
     */
    public static function evaluate(array $fixture, int $revision = 2, ?string $accepted = null, ?string $request = null): array
    {
        return app(EvaluateBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
            $fixture['application']->id, $revision, $accepted, $request ?? (string) Str::uuid());
    }

    /**
     * @param  Fixture  $fixture
     * @return array<string, mixed>|null
     */
    public static function quote(array $fixture): ?array
    {
        return app(GetBusinessApplicationQuote::class)->handle($fixture['audit']['authority']['users'][0]->id, 1,
            $fixture['audit']['business'], $fixture['application']->id);
    }
}
