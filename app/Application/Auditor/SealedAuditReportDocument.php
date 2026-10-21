<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportRegisterStore;
use DateTimeImmutable;
use DateTimeZone;

/**
 * A sealed audit report as a plain PDF for staff to file: the Business, the period, the Audit
 * Partner and licence, when it was sealed and its digest, each finding the seal recorded and the
 * partner's note. Every figure is the seal's own; nothing is recomputed but the weighted variance.
 *
 * @phpstan-import-type SealedReport from AuditReportRegisterStore
 */
final readonly class SealedAuditReportDocument
{
    private const int LINES_PER_PAGE = 58;

    /** How each sealed measure reads. */
    private const array MEASURES = ['stock_value' => 'Stock value (RWF)', 'cash' => 'Cash (RWF)', 'stock_units' => 'Stock units',
        'operational_status' => 'Operational status'];

    /** @param  SealedReport  $report */
    public function __construct(private array $report) {}

    public function filename(): string
    {
        return 'rozine-audit-report-'.$this->report['report_id'].'.pdf';
    }

    /** @return list<string> */
    public function lines(): array
    {
        $report = $this->report;
        $lines = ['Rozine - Sealed Audit Report', '', 'Business:      '.$report['business'].' ('.$report['district'].')',
            'Audit:         '.($report['kind'] === 'monthly' ? 'Monthly verification' : 'Flash Audit').($report['period'] === null ? '' : ', '.$this->month($report['period'])),
            'Audit Partner: '.$report['auditor'].', licence '.$report['licence'],
            'Sealed:        '.(new DateTimeImmutable($report['sealed_at']))->setTimezone(new DateTimeZone('Africa/Kigali'))->format('j M Y H:i').' (Kigali)',
            'Digest:        '.$report['digest'], 'Report:        '.$report['report_id'], '', 'Findings', str_repeat('-', 99),
            sprintf('%-24s %22s %22s %22s', 'Measure', 'Reported', 'Observed', 'Difference')];
        foreach ($report['findings'] as $finding) {
            $lines[] = sprintf('%-24s %22s %22s %22s', self::MEASURES[$finding['measure']] ?? $finding['measure'],
                $finding['reported'] ?? '-', $finding['observed'], $finding['difference'] ?? '-');
        }
        if ($report['findings'] === []) {
            $lines[] = 'The seal recorded no findings.';
        }
        $lines[] = str_repeat('-', 99);
        $lines[] = 'Weighted variance: '.($report['variance_pct'] === null ? 'not applicable' : $report['variance_pct'].'%');
        $lines[] = '';
        $lines[] = 'Audit Partner note';
        foreach (explode("\n", wordwrap(trim($report['note']) === '' ? 'No note.' : trim($report['note']), 99, "\n", true)) as $line) {
            $lines[] = $line;
        }

        return $lines;
    }

    public function pdf(): string
    {
        $pages = array_chunk($this->lines(), self::LINES_PER_PAGE);
        $count = count($pages);
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '', '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>'];
        $kids = [];
        foreach ($pages as $index => $page) {
            $content = "BT /F1 8.5 Tf 40 800 Td 13 TL\n";
            foreach ([...$page, '', 'Page '.($index + 1).' of '.$count] as $line) {
                $content .= '('.self::text($line).") Tj T*\n";
            }
            $content .= 'ET';
            $objects[] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.count($objects).' 0 R >>';
            $kids[] = count($objects).' 0 R';
        }
        $objects[1] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$count.' >>';
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf.'trailer'."\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }

    private function month(string $period): string
    {
        return (new DateTimeImmutable($period.'-01'))->format('F Y');
    }

    /** One line in the font's Windows-1252 encoding, with PDF string delimiters escaped. */
    private static function text(string $line): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], (string) mb_convert_encoding($line, 'Windows-1252', 'UTF-8'));
    }
}
