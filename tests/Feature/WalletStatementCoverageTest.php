<?php

declare(strict_types=1);

use App\Application\Wallet\WalletStatementDocument;

/*
 * The wallet statement's wording for every internal movement of a deal, and the sign it gives
 * money leaving the wallet.
 */

/**
 * One statement row in the shape the statement store returns.
 *
 * @return array<string, mixed>
 */
$statementRow = fn (string $kind, string $direction, string $movement, ?string $deal, ?string $counterparty, string $reference): array => [
    'occurred_at' => '2026-10-14T05:00:00+00:00', 'movement' => $movement, 'kind' => $kind, 'deal_name' => $deal, 'counterparty' => $counterparty,
    'direction' => $direction, 'amount' => ['currency' => 'RWF', 'amount' => '5000'], 'reference' => $reference];

it('describes a released hold and a commitment by their deal', function () use ($statementRow): void {
    $document = new WalletStatementDocument(['generated_at' => '2026-10-14T05:00:00+00:00', 'from' => null, 'to' => null, 'rows' => [
        $statementRow('hold_release', 'internal', 'internal', 'Kigali Bakery', null, 'RZW-0000000001'),
        $statementRow('commitment', 'internal', 'internal', 'Kigali Bakery', null, 'RZW-0000000002'),
    ]]);

    expect($document->csv())->toContain('"Hold released - Kigali Bakery","Internal","5000","internal","RZW-0000000001"')
        ->toContain('"Committed to Kigali Bakery","Internal","5000","internal","RZW-0000000002"')
        ->and($document->pdf())->toContain('Hold released - Kigali Bakery) Tj')->toContain('Committed to Kigali Bakery) Tj');
});

it('signs money leaving the wallet as negative on the PDF', function () use ($statementRow): void {
    $pdf = (new WalletStatementDocument(['generated_at' => '2026-10-14T05:00:00+00:00', 'from' => '2026-10-01', 'to' => '2026-10-14', 'rows' => [
        $statementRow('withdrawal', 'out', 'external', null, 'MTN MoMo', 'RZD-0000000003'),
    ]]))->pdf();

    expect($pdf)->toContain('-RWF 5,000  RZD-0000000003  Withdrawal to MTN MoMo) Tj')->toContain('Period 2026-10-01 to 2026-10-14')
        ->not->toContain('+RWF 5,000');
});
