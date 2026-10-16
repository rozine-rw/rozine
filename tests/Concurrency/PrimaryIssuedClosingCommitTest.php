<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;

it('refuses a real outer commit with zero Holdings and preserves original committed cash', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $original = DB::table('ledger_entries')->orderBy('id')->get()->all();
    expect(DB::transactionLevel())->toBe(0);
    expect(fn () => DB::transaction(fn (): string => PrimaryHoldingFixture::issuedClosing($campaign)))
        ->toThrow(PDOException::class, 'An issued closing must issue a Holding for every funded commitment');
    expect(DB::transactionLevel())->toBe(0)->and(DB::table('disbursement_closings')->count())->toBe(0)
        ->and(DB::table('primary_holdings')->count())->toBe(0)->and(DB::table('ledger_entries')->orderBy('id')->get()->all())->toEqual($original);
    DB::transaction(function () use ($campaign, $commitments): void {
        $closing = PrimaryHoldingFixture::issuedClosing($campaign);
        foreach ($commitments as $commitment) {
            PrimaryHoldingFixture::insert($commitment->id, $closing);
            PrimaryHoldingFixture::issue($commitment->id, $closing);
        }
    });
    expect(DB::table('disbursement_closings')->count())->toBe(1)->and(DB::table('primary_holdings')->count())->toBe(2)
        ->and(DB::table('ledger_entries')->where('kind', 'primary_issue')->count())->toBe(2);
});
