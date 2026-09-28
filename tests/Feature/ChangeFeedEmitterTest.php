<?php

declare(strict_types=1);

use App\Application\Operations\ReadChanges;
use App\Models\InvestorWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/*
 * The C3 effects that emit a beacon change (S4-E), each in the effect's own transaction: a
 * replayed or refused command, and a provider event that moves nothing, emit nothing.
 */

/** @return list<string> */
function feedRows(): array
{
    return DB::table('change_feed')->orderBy('id')->get()
        ->map(fn (object $row): string => ($row->party_id ?? $row->business_id ?? $row->staff_queue).'|'.$row->topic.'|'.$row->subject.'@'.$row->revision)->all();
}

it('emits a wallet change when a deposit is recorded and each time its provider outcome applies', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $cursor = app(ReadChanges::class)->cursor($fixture['user']->id);
    $requestId = (string) Str::uuid();
    $recorded = InvestorWalletFixture::deposit($fixture, requestId: $requestId);
    InvestorWalletFixture::deposit($fixture, requestId: $requestId);
    InvestorWalletFixture::deposit($fixture, '1', (string) Str::uuid());
    $wallet = InvestorWallet::query()->sole()->id;
    $key = $fixture['party']->id.'|wallet|'.$wallet;

    expect(feedRows())->toBe([$key.'@1']);

    InvestorWalletFixture::settle($recorded['data']['intent_id'], 'pending', 'event-1');
    InvestorWalletFixture::settle($recorded['data']['intent_id'], 'pending', 'event-1');
    InvestorWalletFixture::settle($recorded['data']['intent_id'], 'succeeded', 'event-2');
    InvestorWalletFixture::settle($recorded['data']['intent_id'], 'failed', 'event-3');

    expect(feedRows())->toBe([$key.'@1', $key.'@2'])
        ->and(app(ReadChanges::class)->handle($fixture['user']->id, ['wallet'], $cursor)['changes'])
        ->toBe([['topic' => 'wallet', 'subject' => $wallet, 'revision' => 2]]);
});

it('rolls a wallet change back with the deposit that recorded it', function (): void {
    $fixture = InvestorWalletFixture::ready();

    expect(fn () => DB::transaction(function () use ($fixture): void {
        InvestorWalletFixture::deposit($fixture);
        throw new RuntimeException('outer rollback');
    }))->toThrow(RuntimeException::class, 'outer rollback')
        ->and(feedRows())->toBe([]);
});
