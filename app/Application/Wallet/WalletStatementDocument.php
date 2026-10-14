<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use Carbon\CarbonImmutable;

/**
 * Renders a wallet statement (design wallet history "Export": Download PDF / Download Excel) as
 * CSV or as a plain one-font PDF. It only lays out the recorded rows the store returned; it adds
 * no amount, balance or status of its own. Text a Business controls (a deal name) is neutralised
 * so a spreadsheet never reads it as a formula.
 *
 * @phpstan-type StatementRow array{occurred_at: string, movement: string, kind: string, deal_name: string|null, counterparty: string|null, direction: string, amount: array{currency: string, amount: string}, reference: string}
 * @phpstan-type Statement array{generated_at: string, from: string|null, to: string|null, rows: list<StatementRow>}
 */
final readonly class WalletStatementDocument
{
    private const int LINES_PER_PAGE = 58;

    /** @param Statement $statement */
    public function __construct(private array $statement) {}

    public function csv(): string
    {
        $lines = [['Date', 'Description', 'Type', 'Amount (RWF)', 'Direction', 'Reference']];
        foreach ($this->statement['rows'] as $row) {
            $lines[] = [$this->date($row['occurred_at']), $this->description($row), $this->type($row), $row['amount']['amount'], $row['direction'], $row['reference']];
        }

        return "\u{FEFF}".implode("\r\n", array_map(fn (array $cells): string => implode(',', array_map($this->cell(...), $cells)), $lines))."\r\n";
    }

    public function pdf(): string
    {
        $lines = ['Rozine Wallet - Transaction Statement', 'Generated '.$this->date($this->statement['generated_at']).'   Period '.$this->period(), '',
            ...$this->columns('Date', 'Type', 'Amount', 'Reference', 'Description'), str_repeat('-', 99)];
        foreach ($this->statement['rows'] as $row) {
            array_push($lines, ...$this->columns($this->date($row['occurred_at']), $this->type($row), $this->amount($row), $row['reference'], $this->description($row)));
        }
        if ($this->statement['rows'] === []) {
            $lines[] = 'No wallet movements were recorded in this period.';
        }
        $pages = array_chunk($lines, self::LINES_PER_PAGE);
        $count = count($pages);
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '', '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>'];
        $kids = [];
        foreach ($pages as $index => $page) {
            $content = "BT /F1 8.5 Tf 40 800 Td 13 TL\n";
            foreach ([...$page, '', 'Page '.($index + 1).' of '.$count] as $line) {
                $content .= '('.$this->pdfText($line).") Tj T*\n";
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

    /** @param StatementRow $row */
    private function description(array $row): string
    {
        return match ($row['kind']) {
            'deposit' => 'Deposit from '.$row['counterparty'],
            'withdrawal' => 'Withdrawal to '.$row['counterparty'],
            'hold' => 'Held for '.$row['deal_name'],
            'hold_release' => 'Hold released - '.$row['deal_name'],
            'commitment' => 'Committed to '.$row['deal_name'],
            default => 'Refund - '.$row['deal_name'],
        };
    }

    /** @param StatementRow $row */
    private function type(array $row): string
    {
        return $row['movement'] === 'external' ? 'Cash' : 'Internal';
    }

    /** @param StatementRow $row */
    private function amount(array $row): string
    {
        $sign = match ($row['direction']) {
            'in' => '+',
            'out' => '-',
            default => '',
        };

        return $sign.'RWF '.number_format((float) $row['amount']['amount'], 0, '.', ',');
    }

    private function date(string $instant): string
    {
        return CarbonImmutable::parse($instant)->setTimezone((string) config('app.timezone'))->format('Y-m-d H:i');
    }

    private function period(): string
    {
        return ($this->statement['from'] ?? 'start').' to '.($this->statement['to'] ?? 'today');
    }

    /**
     * One row: the fixed columns first, then the description, wrapped (never cut) beneath itself.
     *
     * @return list<string>
     */
    private function columns(string $date, string $type, string $amount, string $reference, string $description): array
    {
        $prefix = str_pad($date, 17).str_pad($type, 9).str_pad($amount, 17, ' ', STR_PAD_LEFT).'  '.str_pad($reference, 16);
        $wrapped = explode("\n", wordwrap($this->winAnsi($description), 99 - strlen($prefix), "\n", true));

        return array_map(fn (string $part, int $index): string => ($index === 0 ? $prefix : str_repeat(' ', strlen($prefix))).$part, $wrapped, array_keys($wrapped));
    }

    private function cell(string $value): string
    {
        $value = preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;

        return '"'.str_replace('"', '""', $value).'"';
    }

    /** Single-byte text for the PDF's one font, so wrapping never splits a character. */
    private function winAnsi(string $text): string
    {
        return (string) iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
    }

    private function pdfText(string $line): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $line);
    }
}
