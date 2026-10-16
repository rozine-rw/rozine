<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Wallet\WalletStatementDocument;
use App\Models\LedgerEntry;
use App\Models\WalletDepositCredit;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The wallet statement (design wallet history "Export": Download PDF / Download Excel): every
 * credited deposit and internal transfer of the Investor's own wallet, newest first, with the
 * reference its receipt carries, as a private CSV or PDF attachment.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->travel(1)->seconds();
    app(PrimaryCheckout::class)->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->title = app(PrimaryCampaignSource::class)->presentation($this->campaign->id)['title'];
    $this->credit = WalletDepositCredit::query()->sole();
    $this->hold = LedgerEntry::query()->where('kind', 'primary_hold')->sole();
});

it('downloads the statement as a spreadsheet, newest first, with each receipt reference', function (): void {
    $response = $this->actingAs($this->investor['user'])->get(route('investor.wallet.statement', ['format' => 'csv']))->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'attachment; filename=rozine-wallet-statement.csv');
    $zone = (string) config('app.timezone');

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->getContent())->toBe("\u{FEFF}".implode("\r\n", [
            '"Date","Description","Type","Amount (RWF)","Direction","Reference"',
            '"'.$this->hold->created_at->setTimezone($zone)->format('Y-m-d H:i').'","Held for '.$this->title.'","Internal","15000","internal","RZW-'.strtoupper(substr($this->hold->id, -10)).'"',
            '"'.$this->credit->created_at->setTimezone($zone)->format('Y-m-d H:i').'","Deposit from MTN MoMo","Cash","10000000","in","RZC-'.strtoupper(substr($this->credit->id, -10)).'"',
        ])."\r\n");

    $other = PrimaryReservationFixture::investor();
    expect(substr_count((string) $this->actingAs($other['user'])->get(route('investor.wallet.statement', ['format' => 'csv']))->getContent(), "\r\n"))->toBe(2);
});

it('downloads the statement as a PDF whose cross-reference table points at its objects', function (): void {
    $response = $this->actingAs($this->investor['user'])->get(route('investor.wallet.statement', ['format' => 'pdf']))->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Disposition', 'attachment; filename=rozine-wallet-statement.pdf');
    $pdf = (string) $response->getContent();
    $start = (int) Str::between($pdf, "startxref\n", "\n%%EOF");
    preg_match_all('/^(\d{10}) 00000 n $/m', $pdf, $offsets);

    expect($pdf)->toStartWith('%PDF-1.4')->toContain('(Rozine Wallet - Transaction Statement) Tj')->toContain('Held for '.$this->title)
        ->toContain('+RWF 10,000,000')->toContain('(Page 1 of 1) Tj')
        ->and(substr($pdf, $start, 4))->toBe('xref')
        ->and(array_map(fn (string $offset, int $index): string => substr($pdf, (int) $offset, strlen(($index + 1).' 0 obj')), $offsets[1], array_keys($offsets[1])))
        ->toBe(array_map(fn (int $index): string => ($index + 1).' 0 obj', array_keys($offsets[1])));
});

it('limits the statement to the requested days and refuses a reversed range', function (): void {
    $today = now()->setTimezone((string) config('app.timezone'))->toDateString();
    $tomorrow = now()->setTimezone((string) config('app.timezone'))->addDay()->toDateString();
    $this->actingAs($this->investor['user']);

    expect(substr_count((string) $this->get(route('investor.wallet.statement', ['format' => 'csv', 'from' => $today, 'to' => $today]))->getContent(), "\r\n"))->toBe(3)
        ->and((string) $this->get(route('investor.wallet.statement', ['format' => 'pdf', 'from' => $tomorrow]))->getContent())
        ->toContain('No wallet movements were recorded in this period.')->toContain('Period '.$tomorrow.' to today');

    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $this->getJson(route('api.v1.investor.wallet.statement', ['format' => 'csv', 'from' => $tomorrow, 'to' => $today]))->assertUnprocessable()->assertJsonValidationErrors('to');
    $this->get(route('api.v1.investor.wallet.statement', ['format' => 'csv']))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $this->getJson(route('api.v1.investor.wallet'))->assertOk()->assertJsonPath('data.exports.pdf.url', '/api/v1/investor/wallet/statement.pdf');

    Sanctum::actingAs($this->investor['user'], ['investor:command']);
    $this->getJson(route('api.v1.investor.wallet.statement', ['format' => 'csv']))->assertForbidden();
});

it('links the statement from the wallet page', function (): void {
    $this->actingAs($this->investor['user'])->get(route('investor.wallet'))->assertOk()
        ->assertInertia(fn ($page) => $page->where('exports.csv.url', '/investor/wallet/statement.csv')->where('exports.pdf.url', '/investor/wallet/statement.pdf'));
});

it('keeps a deal name from being read as a spreadsheet formula', function (): void {
    $document = new WalletStatementDocument(['generated_at' => '2026-10-14T05:00:00+00:00', 'from' => null, 'to' => null, 'rows' => [
        ['occurred_at' => '2026-10-14T05:00:00+00:00', 'movement' => 'internal', 'kind' => 'commitment_refund', 'deal_name' => '=HYPERLINK("x") (Ltd)',
            'counterparty' => null, 'direction' => 'internal', 'amount' => ['currency' => 'RWF', 'amount' => '5000'], 'reference' => 'RZW-0000000001']]]);

    expect($document->csv())->toContain('"Refund - =HYPERLINK(""x"") (Ltd)"')
        ->and((new WalletStatementDocument(['generated_at' => '2026-10-14T05:00:00+00:00', 'from' => null, 'to' => null, 'rows' => [
            ['occurred_at' => '2026-10-14T05:00:00+00:00', 'movement' => 'external', 'kind' => 'deposit', 'deal_name' => null,
                'counterparty' => '@bank', 'direction' => 'out', 'amount' => ['currency' => 'RWF', 'amount' => '5000'], 'reference' => '-RZC']]]))->csv())
        ->toContain('"Deposit from @bank"')->toContain('"\'-RZC"')
        ->and($document->pdf())->toContain('Refund - =HYPERLINK\("x"\) \(Ltd\)');

    $long = new WalletStatementDocument(['generated_at' => '2026-10-14T05:00:00+00:00', 'from' => null, 'to' => null, 'rows' => [
        ['occurred_at' => '2026-10-14T05:00:00+00:00', 'movement' => 'internal', 'kind' => 'hold', 'deal_name' => 'Café Kigali equipment and working capital raise',
            'counterparty' => null, 'direction' => 'internal', 'amount' => ['currency' => 'RWF', 'amount' => '5000'], 'reference' => 'RZW-0000000002']]]);
    expect($long->pdf())->toContain("(2026-10-14 05:00 Internal         RWF 5,000  RZW-0000000002  Held for Caf\xE9 Kigali equipment and) Tj")
        ->toContain('working capital raise) Tj');
});
