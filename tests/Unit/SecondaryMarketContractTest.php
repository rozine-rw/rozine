<?php

declare(strict_types=1);

use App\Domain\Secondary\HoldingLot;
use App\Domain\Secondary\PriceCap;
use App\Domain\Secondary\SecondaryListing;
use App\Domain\Secondary\SecondaryViolation;
use App\Domain\Secondary\SettlementQuote;
use App\Domain\Secondary\TradingWindow;
use Brick\Math\BigInteger;

function secondaryKigali(string $at): DateTimeImmutable
{
    return new DateTimeImmutable($at, new DateTimeZone('Africa/Kigali'));
}

function openWindow(): TradingWindow
{
    return new TradingWindow('current', false, false, ['2026-11-10', '2026-12-10', '2027-01-10'], null);
}

function tenUnitListing(): SecondaryListing
{
    return SecondaryListing::open('listing-1', 'seller', 'holding-1', '10', '12', '2700', BigInteger::of(2777), openWindow(), secondaryKigali('2026-10-01 10:00'));
}

it('caps the asking price at the remaining contractual payouts per unit, rounded down (SEC-R03)', function (): void {
    // 100 units at 11.1% over 6 months pay RWF 92,583 a month, about 925.83 per unit; three instalments are left.
    $cap = PriceCap::perUnit(array_fill(0, 3, ['principal' => '83333', 'return' => '9250']), '100');

    expect((string) $cap)->toBe('2777')
        ->and((string) PriceCap::requireWithin('2777', $cap))->toBe('2777')
        ->and((string) PriceCap::requireWithin('2700', $cap))->toBe('2700')
        ->and(fn () => PriceCap::requireWithin('2778', $cap))->toThrow(SecondaryViolation::class, 'PRICE_ABOVE_CAP');
});

it('refuses prices and quantities that are not positive whole numbers', function (string $value): void {
    expect(fn () => PriceCap::requireWithin($value, BigInteger::of(5000)))->toThrow(SecondaryViolation::class, 'INVALID_PRICE')
        ->and(fn () => SettlementQuote::of($value, '100'))->toThrow(SecondaryViolation::class, 'INVALID_UNITS');
})->with(['0', '-1', '1.5', '01', '', ' 2']);

it('charges the seller 2% of the sale price and the buyer nothing (SEC-R06)', function (): void {
    expect(SettlementQuote::of('4', '2700')->toArray())->toBe(['units' => '4', 'price' => '2700', 'gross' => '10800', 'buyer_pays' => '10800',
        'seller_fee' => '216', 'seller_receives' => '10584', 'terms_version' => 'SEC-R/2026-10'])
        ->and(SettlementQuote::of('1', '25')->toArray())->toMatchArray(['seller_fee' => '1', 'seller_receives' => '24'])
        ->and(SettlementQuote::of('1', '24')->toArray())->toMatchArray(['seller_fee' => '0', 'seller_receives' => '24']);
});

it('keeps a listing open for exactly seven days, then lets it expire at no cost (SEC-R02)', function (): void {
    $listing = tenUnitListing();

    expect($listing->expiresAt->setTimezone(new DateTimeZone('Africa/Kigali'))->format('Y-m-d H:i'))->toBe('2026-10-08 10:00')
        ->and($listing->at(secondaryKigali('2026-10-08 09:59:59'))->state)->toBe('open')
        ->and($listing->at(secondaryKigali('2026-10-08 10:00'))->state)->toBe('expired')
        ->and(fn () => $listing->reserve('r-1', 'buyer', '1', '2160', '0', openWindow(), secondaryKigali('2026-10-08 10:00')))
        ->toThrow(SecondaryViolation::class, 'LISTING_CLOSED');
});

it('only lists units the seller holds free, within the cap, in an open window', function (): void {
    $list = fn (string $units, string $price, TradingWindow $window): SecondaryListing => SecondaryListing::open('l', 'seller', 'h', $units, '12', $price,
        BigInteger::of(2777), $window, secondaryKigali('2026-10-01 10:00'));

    expect(fn () => $list('13', '2700', openWindow()))->toThrow(SecondaryViolation::class, 'UNITS_NOT_HELD')
        ->and(fn () => $list('12', '2778', openWindow()))->toThrow(SecondaryViolation::class, 'PRICE_ABOVE_CAP')
        ->and(fn () => $list('12', '2700', new TradingWindow('late', false, false, [], null)))->toThrow(SecondaryViolation::class, 'NOTE_NOT_TRADABLE')
        ->and($list('12', '1', openWindow())->remainingUnits)->toBe('12');
});

it('reserves units for five minutes, refusing the seller a cancel or reprice meanwhile (SEC-R05, R09)', function (): void {
    $reserved = tenUnitListing()->reserve('r-1', 'buyer', '4', '2160', '0', openWindow(), secondaryKigali('2026-10-02 12:00'));

    expect($reserved->reservation)->toMatchArray(['id' => 'r-1', 'buyer_party_id' => 'buyer', 'units' => '4'])
        ->and($reserved->reservation['expires_at']->setTimezone(new DateTimeZone('Africa/Kigali'))->format('H:i:s'))->toBe('12:05:00')
        ->and(fn () => $reserved->cancel(secondaryKigali('2026-10-02 12:04:59')))->toThrow(SecondaryViolation::class, 'LISTING_RESERVED')
        ->and(fn () => $reserved->reprice('2600', BigInteger::of(2777), openWindow(), secondaryKigali('2026-10-02 12:01')))->toThrow(SecondaryViolation::class, 'LISTING_RESERVED')
        ->and(fn () => $reserved->reserve('r-2', 'other', '1', '2160', '0', openWindow(), secondaryKigali('2026-10-02 12:01')))->toThrow(SecondaryViolation::class, 'LISTING_RESERVED')
        ->and($reserved->cancel(secondaryKigali('2026-10-02 12:05'))->cancellation)->toBe(['by' => 'seller', 'reason' => null])
        ->and(fn () => $reserved->settle('r-1', openWindow(), secondaryKigali('2026-10-02 12:05')))->toThrow(SecondaryViolation::class, 'RESERVATION_NOT_OPEN');
});

it('lets a buyer take part of a listing and leaves the rest listed until it sells (SEC-R04, R06)', function (): void {
    $first = tenUnitListing()->reserve('r-1', 'buyer', '4', '2160', '0', openWindow(), secondaryKigali('2026-10-02 12:00'))
        ->settle('r-1', openWindow(), secondaryKigali('2026-10-02 12:03'));

    expect([$first['listing']->state, $first['listing']->remainingUnits, $first['listing']->reservation])->toBe(['open', '6', null])
        ->and($first['quote']->toArray())->toMatchArray(['gross' => '10800', 'seller_fee' => '216', 'seller_receives' => '10584'])
        ->and(fn () => $first['listing']->settle('r-1', openWindow(), secondaryKigali('2026-10-02 12:04')))->toThrow(SecondaryViolation::class, 'RESERVATION_NOT_OPEN')
        ->and(fn () => $first['listing']->reserve('r-2', 'buyer', '7', '2160', '4', openWindow(), secondaryKigali('2026-10-02 12:10')))
        ->toThrow(SecondaryViolation::class, 'UNITS_NOT_LISTED');
    $rest = $first['listing']->reserve('r-2', 'other', '6', '2160', '0', openWindow(), secondaryKigali('2026-10-02 12:10'))
        ->settle('r-2', openWindow(), secondaryKigali('2026-10-02 12:11'));
    expect([$rest['listing']->state, $rest['listing']->remainingUnits, (string) $rest['quote']->gross])->toBe(['sold', '0', '16200'])
        ->and(fn () => $rest['listing']->cancel(secondaryKigali('2026-10-02 12:12')))->toThrow(SecondaryViolation::class, 'LISTING_CLOSED');
});

it('refuses a seller buying their own listing and a buyer going past half the raise (SEC-R01, INV-R01)', function (): void {
    $listing = tenUnitListing();
    $reserve = fn (string $buyer, string $held, string $units): SecondaryListing => $listing->reserve('r', $buyer, $units, '2160', $held, openWindow(),
        secondaryKigali('2026-10-02 12:00'));

    expect(fn () => $reserve('seller', '0', '1'))->toThrow(SecondaryViolation::class, 'OWN_LISTING')
        ->and(fn () => $reserve('buyer', '1078', '3'))->toThrow(SecondaryViolation::class, 'HOLDING_LIMIT_EXCEEDED')
        ->and($reserve('buyer', '1078', '2')->reservation['units'])->toBe('2')
        ->and((string) TradingWindow::buyerRoom('2161', '0'))->toBe('1080')
        ->and((string) TradingWindow::buyerRoom('2160', '1200'))->toBe('0');
});

it('pauses a note from 23:00 Kigali on its due date until that month\'s payouts post (SEC-R07)', function (): void {
    $window = openWindow();
    $posted = new TradingWindow('current', false, false, ['2026-11-10', '2026-12-10'], '2026-11-10');

    expect($window->pausedSince(secondaryKigali('2026-11-10 22:59:59')))->toBeNull()
        ->and($window->pausedSince(secondaryKigali('2026-11-10 23:10'))?->format(DATE_ATOM))->toBe('2026-11-10T23:00:00+02:00')
        ->and($window->pausedSince(secondaryKigali('2026-11-12 09:00')))->not->toBeNull()
        ->and(fn () => SecondaryListing::open('l', 'seller', 'h', '1', '1', '100', BigInteger::of(2777), $window, secondaryKigali('2026-11-10 23:10')))
        ->toThrow(SecondaryViolation::class, 'TRADING_PAUSED')
        ->and($posted->pausedSince(secondaryKigali('2026-11-12 09:00')))->toBeNull()
        ->and($posted->pausedSince(secondaryKigali('2026-12-10 23:00'))?->format('Y-m-d'))->toBe('2026-12-10');
});

it('refuses trading in a note that is not current, and during a market or note halt (SEC-R01, R08)', function (string $health, bool $market, bool $note, string $code): void {
    expect(fn () => (new TradingWindow($health, $market, $note, [], null))->requireOpen(secondaryKigali('2026-10-02 12:00')))
        ->toThrow(SecondaryViolation::class, $code);
})->with([
    'late' => ['late', false, false, 'NOTE_NOT_TRADABLE'],
    'defaulted' => ['defaulted', false, false, 'NOTE_NOT_TRADABLE'],
    'disputed' => ['disputed', false, false, 'NOTE_NOT_TRADABLE'],
    'market halt' => ['current', true, false, 'MARKET_HALTED'],
    'note halt' => ['current', false, true, 'NOTE_HALTED'],
]);

it('refuses an unknown note health', function (): void {
    expect(fn () => new TradingWindow('paused', false, false, [], null))->toThrow(SecondaryViolation::class, 'INVALID_NOTE_HEALTH');
});

it('releases a reservation and cancels the listing for good on a halt, and needs a reason for staff action (SEC-R08, R09)', function (): void {
    $reserved = tenUnitListing()->reserve('r-1', 'buyer', '4', '2160', '0', openWindow(), secondaryKigali('2026-10-02 12:00'));
    $halted = $reserved->halt('Suspected wash trading.', secondaryKigali('2026-10-02 12:01'));

    expect([$halted->state, $halted->reservation, $halted->cancellation])->toBe(['cancelled', null, ['by' => 'halt', 'reason' => 'Suspected wash trading.']])
        ->and($halted->at(secondaryKigali('2026-10-20 12:00'))->state)->toBe('cancelled')
        ->and(fn () => $halted->settle('r-1', openWindow(), secondaryKigali('2026-10-02 12:02')))->toThrow(SecondaryViolation::class, 'RESERVATION_NOT_OPEN')
        ->and(fn () => $reserved->halt(' ', secondaryKigali('2026-10-02 12:01')))->toThrow(SecondaryViolation::class, 'REASON_REQUIRED')
        ->and(fn () => tenUnitListing()->cancelByStaff('', secondaryKigali('2026-10-02 12:00')))->toThrow(SecondaryViolation::class, 'REASON_REQUIRED')
        ->and(tenUnitListing()->cancelByStaff('Misleading price.', secondaryKigali('2026-10-02 12:00'))->cancellation)->toBe(['by' => 'staff', 'reason' => 'Misleading price.'])
        ->and(tenUnitListing()->reprice('2600', BigInteger::of(2777), openWindow(), secondaryKigali('2026-10-02 12:00'))->price)->toBe('2600')
        ->and(fn () => tenUnitListing()->reprice('2800', BigInteger::of(2777), openWindow(), secondaryKigali('2026-10-02 12:00')))
        ->toThrow(SecondaryViolation::class, 'PRICE_ABOVE_CAP');
});

it('moves the lot\'s lowest ordinals to the buyer and keeps the rest with the seller', function (): void {
    $lot = HoldingLot::of([['first' => '1', 'last' => '3'], ['first' => '7', 'last' => '10']]);
    $split = $lot->split('5');

    expect((string) $lot->units)->toBe('7')
        ->and($split['moved']->ranges)->toBe([['first' => '1', 'last' => '3'], ['first' => '7', 'last' => '8']])
        ->and($split['kept']?->ranges)->toBe([['first' => '9', 'last' => '10']])
        ->and($lot->split('3')['kept']?->ranges)->toBe([['first' => '7', 'last' => '10']])
        ->and($lot->split('7')['kept'])->toBeNull()
        ->and(fn () => $lot->split('8'))->toThrow(SecondaryViolation::class, 'UNITS_NOT_HELD')
        ->and(fn () => HoldingLot::of([['first' => '3', 'last' => '1']]))->toThrow(SecondaryViolation::class, 'INVALID_ORDINALS')
        ->and(fn () => HoldingLot::of([['first' => '1', 'last' => '3'], ['first' => '3', 'last' => '4']]))->toThrow(SecondaryViolation::class, 'INVALID_ORDINALS')
        ->and(fn () => HoldingLot::of([]))->toThrow(SecondaryViolation::class, 'INVALID_ORDINALS');
});
