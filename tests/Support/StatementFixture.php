<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Evidence\IngestStatement;
use App\Application\Evidence\RecordStatementTranscription;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type ApplicationFixture from BusinessApplicationFixture
 * @phpstan-import-type Rail from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type Statement from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type Transaction from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type DrawRef from \App\Domain\Evidence\StatementReconciliation
 *
 * @phpstan-type TranscriptionInput array{rails: list<Rail>, months: list<string>, statements: list<Statement>}
 */
final class StatementFixture
{
    /** @return TranscriptionInput */
    public static function transcription(string $documentId): array
    {
        $input = self::reconciliation();
        $statements = array_values(array_map(fn (array $statement): array => [...$statement, 'source_ids' => [$documentId],
            'transactions' => array_values(array_map(fn (array $transaction): array => [...$transaction, 'source_ids' => [$documentId]], $statement['transactions']))], $input['statements']));

        return ['rails' => $input['rails'], 'months' => $input['months'], 'statements' => $statements];
    }

    /**
     * @param  ApplicationFixture  $fixture
     * @param  TranscriptionInput  $input
     * @return array<string, mixed>
     */
    public static function transcribe(array $fixture, array $input, int $revision = 1, ?string $requestId = null, int $actor = 0): array
    {
        return app(RecordStatementTranscription::class)->handle($fixture['authority']['users'][$actor]->id, 1, $fixture['business']->id,
            $revision, $input['rails'], $input['months'], $input['statements'], $requestId ?? (string) Str::uuid());
    }

    /**
     * @return array{rails: array{Rail, Rail}, months: array{string}, sources: array{string, string}, statements: array{
     *   array{rail_id: string, month: string, opening_balance: string, closing_balance: string, source_ids: list<string>,
     *     transactions: array{Transaction, Transaction, Transaction, Transaction, Transaction, Transaction, Transaction}},
     *   array{rail_id: string, month: string, opening_balance: string, closing_balance: string, source_ids: list<string>,
     *     transactions: array{Transaction, Transaction}}
     * }}
     */
    public static function reconciliation(): array
    {
        return [
            'rails' => [
                ['id' => 'bank-a', 'active_from' => '2026-08', 'active_until' => null],
                ['id' => 'momo-b', 'active_from' => '2026-08', 'active_until' => null],
            ],
            'months' => ['2026-08'],
            'sources' => ['original-a', 'original-b'],
            'statements' => [
                ['rail_id' => 'bank-a', 'month' => '2026-08', 'opening_balance' => '1000', 'closing_balance' => '2950',
                    'source_ids' => ['original-a'], 'transactions' => [
                        self::transaction('sales', '1000', 'operating_inflow'),
                        self::transaction('cost', '-200', 'operating_outflow'),
                        self::transaction('loan', '2000', 'financing'),
                        self::transaction('transfer-out', '-500', 'transfer'),
                        self::transaction('draw', '-300', 'owner_draw'),
                        self::transaction('returned', '100', 'owner_return', returnOf: ['rail_id' => 'bank-a', 'month' => '2026-08', 'reference' => 'draw']),
                        self::transaction('debt', '-150', 'debt_service'),
                    ]],
                ['rail_id' => 'momo-b', 'month' => '2026-08', 'opening_balance' => '0', 'closing_balance' => '400',
                    'source_ids' => ['original-b'], 'transactions' => [
                        self::transaction('transfer-in', '500', 'transfer', source: 'original-b'),
                        self::transaction('momo-cost', '-100', 'operating_outflow', source: 'original-b'),
                    ]],
            ],
        ];
    }

    /**
     * @param  DrawRef|null  $returnOf
     * @return Transaction
     */
    public static function transaction(string $reference, string $amount, string $classification, string $date = '2026-08-15', string $source = 'original-a', ?array $returnOf = null, ?string $exceptionId = null): array
    {
        return ['reference' => $reference, 'date' => $date, 'amount' => $amount, 'classification' => $classification,
            'source_ids' => [$source], 'return_of' => $returnOf, 'exception_id' => $exceptionId];
    }

    public static function csv(string $amount = '100'): string
    {
        return "date,reference,amount\n2026-08-01,SYNTHETIC-ONLY,{$amount}\n";
    }

    public static function pdf(string $text = 'Synthetic statement fixture only'): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT /F1 12 Tf 20 20 Td ({$escaped}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 100] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream).">>\nstream\n".$stream."\nendstream",
        ];
        $pdf = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $start = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$start}\n%%EOF\n";
    }

    /**
     * @param  ApplicationFixture  $fixture
     * @return array<string, mixed>
     */
    public static function ingest(array $fixture, int $revision = 0, ?string $requestId = null, string $amount = '100', int $actor = 0): array
    {
        return app(IngestStatement::class)->handle($fixture['authority']['users'][$actor]->id, 1, $fixture['business']->id,
            $revision, 'synthetic-statement.csv', self::csv($amount), $requestId ?? (string) Str::uuid());
    }
}
