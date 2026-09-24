<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Evidence\IngestStatement;
use Illuminate\Support\Str;

/** @phpstan-import-type ApplicationFixture from BusinessApplicationFixture */
final class StatementFixture
{
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
