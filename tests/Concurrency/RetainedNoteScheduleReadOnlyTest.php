<?php

declare(strict_types=1);

use App\Application\Primary\GetRetainedNoteSchedule;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryIssuanceFixture;

it('reads the complete retained schedule inside a PostgreSQL enforced read only transaction', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close(units: ['1', '1079', '1080']);
    expect(DB::transactionLevel())->toBe(0);
    DB::transaction(function () use ($campaign): void {
        DB::statement('SET TRANSACTION READ ONLY');
        expect(DB::selectOne('SHOW transaction_read_only')->transaction_read_only)->toBe('on');
        $snapshot = app(GetRetainedNoteSchedule::class)->find($campaign->campaignId);
        expect($snapshot->principal)->toBe($campaign->principal)->and($snapshot->holdings)->toHaveCount(3)
            ->and(DB::transactionLevel())->toBe(1);
    });
    expect(DB::transactionLevel())->toBe(0);
});
