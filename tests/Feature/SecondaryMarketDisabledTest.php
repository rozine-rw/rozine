<?php

declare(strict_types=1);

use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Domain\Operations\CommandRejection;

it('keeps secondary trading switched off until Phase 3 acceptance', function (Closure $command): void {
    try {
        $command(app(SecondaryMarket::class));
        $this->fail('The command was not refused.');
    } catch (CommandRejection $rejection) {
        expect([$rejection->reason, $rejection->status])->toBe(['SECONDARY_TRADING_DISABLED', 503]);
    }
})->with([
    'list' => [fn (SecondaryMarket $market): array => $market->list(1, 1, 'holding', '1', '100', 'request')],
    'reserve' => [fn (SecondaryMarket $market): array => $market->reserve(1, 1, 'listing', '1', 'request')],
    'settle' => [fn (SecondaryMarket $market): array => $market->settle(1, 1, 'listing', 'reservation', 'request')],
    'amend' => [fn (SecondaryMarket $market): array => $market->amend(1, 1, 'listing', null, 'request')],
    'halt' => [fn (SecondaryMarket $market): array => $market->halt(1, null, 'Reason.', 'request')],
]);
