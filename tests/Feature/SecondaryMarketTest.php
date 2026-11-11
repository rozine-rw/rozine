<?php

declare(strict_types=1);

use App\Application\Secondary\Contracts\NoteUnitHolders;
use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Application\Secondary\Contracts\SecondaryNotes;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use App\Infrastructure\Secondary\UnavailableSecondaryMarket;
use App\Models\PrimaryHolding;
use App\Models\SecondaryListingRecord;
use App\Models\SecondaryReservationRecord;
use App\Models\SecondaryTransfer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;
use Tests\Support\StaffFixture;

/*
 * BRS v3.0 §12 on the shared ledger. One note is issued to two Investors (1,080 units each, a
 * 2,160-unit raise); a third Investor with RWF 1,000,000 settled buys on the market. The note's
 * first instalment falls due on 10 November 2026, so it trades until 23:00 Kigali that day.
 */
beforeEach(function (): void {
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-10 10:00:00', 'Africa/Kigali'));
    PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $this->holding = PrimaryHolding::query()->orderBy('id')->firstOrFail();
    $this->noteId = $this->holding->business_campaign_id;
    $this->seller = User::query()->where('party_id', $this->holding->party_id)->sole();
    $this->buyer = InvestorWalletFixture::funded('1000000');
    $this->market = app(EloquentSecondaryMarket::class);
    $this->flush = fn () => PrimaryHoldingFixture::flushDeferredChecks();
    $this->bucket = fn (string $partyId, string $kind): string => (string) DB::table('ledger_lines')
        ->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
        ->join('investor_wallets', 'investor_wallets.id', '=', 'ledger_accounts.wallet_id')
        ->where('investor_wallets.party_id', $partyId)->where('ledger_accounts.kind', $kind)
        ->selectRaw("coalesce(sum(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0)::text AS balance")->value('balance');
    $this->platform = fn (string $kind): string => (string) DB::table('ledger_lines')
        ->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')->whereNull('ledger_accounts.wallet_id')
        ->where('ledger_accounts.kind', $kind)
        ->selectRaw("coalesce(sum(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0)::text AS balance")->value('balance');
    $this->list = function (string $units = '10', string $price = '5000', ?string $lot = null, ?User $as = null): array {
        $result = $this->market->list(($as ?? $this->seller)->id, 1, $lot ?? $this->holding->id, $units, $price, (string) Str::uuid());
        ($this->flush)();

        return $result;
    };
    $this->reserve = function (string $listingId, string $units = '4', ?User $as = null): array {
        $result = $this->market->reserve(($as ?? $this->buyer['user'])->id, 1, $listingId, $units, (string) Str::uuid());
        ($this->flush)();

        return $result;
    };
    $this->compliance = function (): User {
        $user = User::factory()->withTwoFactor()->create();
        StaffFixture::provision($user, ['compliance'], 'Market surveillance duty.');

        return $user;
    };
});

it('keeps trading switched off unless it is turned on', function (): void {
    expect(config('app.secondary_trading'))->toBeFalse()
        ->and(get_class(app(SecondaryMarket::class)))->toBe(UnavailableSecondaryMarket::class);

    config(['app.secondary_trading' => true]);

    expect(get_class(app(SecondaryMarket::class)))->toBe(EloquentSecondaryMarket::class);
});

it('lists whole units at a whole-franc price within the cap for exactly seven days (SEC-R02, SEC-R03)', function (): void {
    $note = DB::transaction(fn (): ?array => app(SecondaryNotes::class)->find($this->noteId));
    $gross = array_sum(array_map(fn (array $instalment): int => (int) $instalment['principal'] + (int) $instalment['return'], $note['instalments']));
    $cap = intdiv($gross, 2160);

    $tooHigh = ($this->list)('10', (string) ($cap + 1));
    $listed = ($this->list)('10', (string) $cap);

    expect($tooHigh)->toMatchArray(['status' => 'rejected', 'code' => 'PRICE_ABOVE_CAP', 'http_status' => 422])
        ->and($tooHigh['data'])->toEqual(['cap' => ['currency' => 'RWF', 'amount' => (string) $cap]])
        ->and($listed)->toMatchArray(['status' => 'completed', 'code' => 'SECONDARY_LISTED'])
        ->and($listed['data']['listing'])->toMatchArray(['note_id' => $this->noteId, 'lot_id' => $this->holding->id, 'listed_units' => '10',
            'remaining_units' => '10', 'price' => ['currency' => 'RWF', 'amount' => (string) $cap], 'state' => 'open', 'reservation' => null,
            'listed_at' => '2026-10-10T08:00:00+00:00', 'expires_at' => '2026-10-17T08:00:00+00:00']);
});

it('lists only units the seller holds that no other listing already offers', function (): void {
    ($this->list)('1000');

    expect(($this->list)('81'))->toMatchArray(['code' => 'UNITS_NOT_HELD', 'data' => ['free_units' => '80']])
        ->and(($this->list)('80')['code'])->toBe('SECONDARY_LISTED')
        ->and(($this->list)('1', as: $this->buyer['user']))->toMatchArray(['code' => 'LOT_NOT_FOUND', 'http_status' => 404]);
});

it('replays a repeated request and refuses the same request id with different input', function (): void {
    $requestId = (string) Str::uuid();
    $first = $this->market->list($this->seller->id, 1, $this->holding->id, '10', '5000', $requestId);

    expect($this->market->list($this->seller->id, 1, $this->holding->id, '10', '5000', $requestId))->toBe($first)
        ->and(SecondaryListingRecord::query()->count())->toBe(1)
        ->and(fn () => $this->market->list($this->seller->id, 1, $this->holding->id, '11', '5000', $requestId))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('reserves units for five minutes with a hold on cash already settled, and the seller cannot cancel or reprice meanwhile (SEC-R05, LED-R02)', function (): void {
    $listing = ($this->list)('10', '5000')['data']['listing'];
    $reserved = ($this->reserve)($listing['listing_id'], '4');
    $partyId = $this->buyer['party']->id;

    expect($reserved)->toMatchArray(['status' => 'completed', 'code' => 'SECONDARY_RESERVED'])
        ->and($reserved['data']['reservation'])->toMatchArray(['units' => '4', 'buyer_pays' => ['currency' => 'RWF', 'amount' => '20000'],
            'expires_at' => '2026-10-10T08:05:00+00:00'])
        ->and(($this->bucket)($partyId, 'investor_held'))->toBe('20000')
        ->and(($this->bucket)($partyId, 'investor_available'))->toBe('980000');

    $cancel = $this->market->amend($this->seller->id, 1, $listing['listing_id'], null, (string) Str::uuid());
    $reprice = $this->market->amend($this->seller->id, 1, $listing['listing_id'], '4000', (string) Str::uuid());
    $second = ($this->reserve)($listing['listing_id'], '1');

    expect($cancel)->toMatchArray(['code' => 'LISTING_RESERVED', 'data' => ['reserved_until' => '2026-10-10T08:05:00+00:00']])
        ->and($reprice['code'])->toBe('LISTING_RESERVED')
        ->and($second['code'])->toBe('LISTING_RESERVED');
});

it('releases a lapsed reservation at no charge, by the next command or the sweep', function (): void {
    $listing = ($this->list)('10', '5000')['data']['listing'];
    ($this->reserve)($listing['listing_id'], '4');
    $partyId = $this->buyer['party']->id;
    $this->travel(5)->minutes();

    $result = $this->market->sweep(now('UTC')->toDateTimeImmutable());
    ($this->flush)();

    expect($result)->toBe(['released' => 1, 'expired' => 0])
        ->and(SecondaryReservationRecord::query()->sole()->only(['ended', 'end_reason']))->toBe(['ended' => 'released', 'end_reason' => 'lapsed'])
        ->and(($this->bucket)($partyId, 'investor_held'))->toBe('0')
        ->and(($this->bucket)($partyId, 'investor_available'))->toBe('1000000')
        ->and($this->market->amend($this->seller->id, 1, $listing['listing_id'], '4500', (string) Str::uuid())['code'])->toBe('SECONDARY_REPRICED');
});

it('refuses a reservation the buyer cannot pay from settled cash', function (): void {
    $listing = ($this->list)('1000', '5000')['data']['listing'];

    $result = ($this->reserve)($listing['listing_id'], '201');

    expect($result)->toMatchArray(['code' => 'INSUFFICIENT_AVAILABLE_FUNDS', 'http_status' => 422,
        'data' => ['available' => ['currency' => 'RWF', 'amount' => '1000000']]])
        ->and(SecondaryReservationRecord::query()->count())->toBe(0);
});

it('keeps every buyer within half of the raise, counting what they hold and have reserved (INV-R01, SEC-R01)', function (): void {
    $other = PrimaryHolding::query()->where('id', '<>', $this->holding->id)->sole();
    $otherSeller = User::query()->where('party_id', $other->party_id)->sole();
    $listing = ($this->list)('10', '100')['data']['listing'];

    expect(($this->reserve)($listing['listing_id'], '1', $otherSeller))->toMatchArray(['code' => 'HOLDING_LIMIT_EXCEEDED', 'data' => ['maximum_units' => '0']])
        ->and(($this->reserve)($listing['listing_id'], '1', $this->seller)['code'])->toBe('OWN_LISTING');
});

it('settles in one step: cash and the lowest ordinals swap, and the seller pays the 2% fee (SEC-R06)', function (): void {
    $listing = ($this->list)('10', '2700')['data']['listing'];
    $reservation = ($this->reserve)($listing['listing_id'], '4')['data']['reservation'];
    $buyerParty = $this->buyer['party']->id;
    $sellerAvailable = ($this->bucket)($this->holding->party_id, 'investor_available');

    $settled = $this->market->settle($this->buyer['user']->id, 1, $listing['listing_id'], $reservation['reservation_id'], (string) Str::uuid());
    ($this->flush)();
    $first = (int) $this->holding->ordinals[0]['first'];

    expect($settled)->toMatchArray(['status' => 'completed', 'code' => 'SECONDARY_SETTLED'])
        ->and($settled['data']['transfer'])->toMatchArray(['units' => '4', 'price' => '2700', 'gross' => '10800', 'buyer_pays' => '10800',
            'seller_fee' => '216', 'seller_receives' => '10584', 'ordinals' => [['first' => (string) $first, 'last' => (string) ($first + 3)]]])
        ->and($settled['data']['listing'])->toMatchArray(['remaining_units' => '6', 'state' => 'open', 'reservation' => null])
        ->and(($this->bucket)($buyerParty, 'investor_held'))->toBe('0')
        ->and(($this->bucket)($buyerParty, 'investor_available'))->toBe('989200')
        ->and(($this->bucket)($this->holding->party_id, 'investor_available'))->toBe((string) ((int) $sellerAvailable + 10584))
        ->and(($this->platform)('secondary_fee_revenue'))->toBe('216')
        ->and(($this->platform)('secondary_settlement'))->toBe('0');

    $lots = collect(DB::transaction(fn (): array => app(NoteUnitHolders::class)->lots($this->noteId)))->keyBy('lot_id');
    $bought = SecondaryTransfer::query()->sole();
    expect($lots[$this->holding->id])->toMatchArray(['party_id' => $this->holding->party_id, 'units' => '1076'])
        ->and($lots[$this->holding->id]['ordinals'][0]['first'])->toBe((string) ($first + 4))
        ->and($lots[$bought->id])->toMatchArray(['lot_type' => 'secondary_transfer', 'party_id' => $buyerParty, 'units' => '4']);

    // The buyer may relist what they bought, the seller may not list it, and nobody settles the same reservation twice.
    expect(($this->list)('4', '2700', $bought->id)['code'])->toBe('LOT_NOT_FOUND')
        ->and(($this->list)('4', '2700', $bought->id, $this->buyer['user'])['code'])->toBe('SECONDARY_LISTED')
        ->and($this->market->settle($this->buyer['user']->id, 1, $listing['listing_id'], $reservation['reservation_id'], (string) Str::uuid())['code'])
        ->toBe('RESERVATION_NOT_OPEN');
});

it('sells the listing out when every unit is bought', function (): void {
    $listing = ($this->list)('4', '2700')['data']['listing'];
    $reservation = ($this->reserve)($listing['listing_id'], '4')['data']['reservation'];

    $settled = $this->market->settle($this->buyer['user']->id, 1, $listing['listing_id'], $reservation['reservation_id'], (string) Str::uuid());
    ($this->flush)();

    expect($settled['data']['listing'])->toMatchArray(['remaining_units' => '0', 'state' => 'sold'])
        ->and(($this->reserve)($listing['listing_id'], '1')['code'])->toBe('LISTING_CLOSED');
});

it('refuses to settle a lapsed reservation', function (): void {
    $listing = ($this->list)('10', '2700')['data']['listing'];
    $reservation = ($this->reserve)($listing['listing_id'], '4')['data']['reservation'];
    $this->travel(6)->minutes();

    expect($this->market->settle($this->buyer['user']->id, 1, $listing['listing_id'], $reservation['reservation_id'], (string) Str::uuid())['code'])
        ->toBe('RESERVATION_NOT_OPEN')
        ->and(SecondaryTransfer::query()->count())->toBe(0);
});

it('pauses trading from 23:00 Kigali on a due date until that month\'s payouts post (SEC-R07)', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-08 09:00:00', 'Africa/Kigali'));
    $listing = ($this->list)('10', '2700')['data']['listing'];
    $this->travelTo(CarbonImmutable::parse('2026-11-10 22:59:00', 'Africa/Kigali'));
    expect(($this->reserve)($listing['listing_id'], '1')['code'])->toBe('SECONDARY_RESERVED');
    $this->travelTo(CarbonImmutable::parse('2026-11-10 23:10:00', 'Africa/Kigali'));

    $result = ($this->reserve)($listing['listing_id'], '1');

    expect($result)->toMatchArray(['code' => 'TRADING_PAUSED', 'http_status' => 409])
        ->and($result['data'])->toEqual(['paused_since' => '2026-11-10T21:00:00+00:00', 'reopens' => 'when_payouts_post']);
});

it('expires a listing after seven days at no cost (SEC-R02)', function (): void {
    $listing = ($this->list)('10', '2700')['data']['listing'];
    $this->travelTo(CarbonImmutable::parse('2026-10-17 10:00:00', 'Africa/Kigali'));

    expect($this->market->sweep(now('UTC')->toDateTimeImmutable()))->toBe(['released' => 0, 'expired' => 1])
        ->and(SecondaryListingRecord::query()->sole()->state)->toBe('expired')
        ->and(($this->reserve)($listing['listing_id'], '1')['code'])->toBe('LISTING_CLOSED')
        ->and(($this->list)('10', '2700')['code'])->toBe('SECONDARY_LISTED');
});

it('lets the seller cancel when nobody is buying, and staff only with a reason shown to the seller (SEC-R09)', function (): void {
    $staff = ($this->compliance)();
    $mine = ($this->list)('10', '2700')['data']['listing'];
    $theirs = ($this->list)('10', '2700')['data']['listing'];

    $cancelled = $this->market->amend($this->seller->id, 1, $mine['listing_id'], null, (string) Str::uuid());
    $noReason = $this->market->cancelListing($staff->id, $theirs['listing_id'], '  ', (string) Str::uuid());
    $byStaff = $this->market->cancelListing($staff->id, $theirs['listing_id'], 'Price misleads buyers.', (string) Str::uuid());

    expect($cancelled['data']['listing'])->toMatchArray(['state' => 'cancelled', 'cancellation' => ['by' => 'seller', 'reason' => null]])
        ->and($noReason['code'])->toBe('REASON_REQUIRED')
        ->and($byStaff['data']['listing'])->toMatchArray(['state' => 'cancelled', 'cancellation' => ['by' => 'staff', 'reason' => 'Price misleads buyers.']])
        ->and(fn () => $this->market->cancelListing($this->seller->id, $mine['listing_id'], 'Reason.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class);
});

it('halts the market with a reason, releasing reservations and cancelling listings for good (SEC-R08)', function (): void {
    $staff = ($this->compliance)();
    $reserved = ($this->list)('10', '2700')['data']['listing'];
    $idle = ($this->list)('10', '2700')['data']['listing'];
    ($this->reserve)($reserved['listing_id'], '4');

    $halt = $this->market->halt($staff->id, null, 'Suspected wash trading.', (string) Str::uuid());
    ($this->flush)();

    expect($halt)->toMatchArray(['status' => 'completed', 'code' => 'SECONDARY_HALTED'])
        ->and($halt['data'])->toMatchArray(['note_id' => null, 'cancelled_listings' => 2, 'released_reservations' => 1])
        ->and(SecondaryListingRecord::query()->pluck('cancelled_by')->unique()->all())->toBe(['halt'])
        ->and(SecondaryReservationRecord::query()->sole()->end_reason)->toBe('halted')
        ->and(($this->bucket)($this->buyer['party']->id, 'investor_held'))->toBe('0')
        ->and(($this->list)('10', '2700')['code'])->toBe('MARKET_HALTED')
        ->and($this->market->halt($staff->id, null, 'Again.', (string) Str::uuid())['code'])->toBe('MARKET_ALREADY_HALTED');

    $lifted = $this->market->lift($staff->id, null, 'Investigation closed.', (string) Str::uuid());

    expect($lifted['code'])->toBe('SECONDARY_HALT_LIFTED')
        ->and(($this->reserve)($idle['listing_id'], '1')['code'])->toBe('LISTING_CLOSED')
        ->and(($this->list)('10', '2700')['code'])->toBe('SECONDARY_LISTED')
        ->and($this->market->lift($staff->id, null, 'Again.', (string) Str::uuid())['code'])->toBe('MARKET_NOT_HALTED');
});

it('halts one note without touching the rest of the market', function (): void {
    $staff = ($this->compliance)();
    ($this->list)('10', '2700');

    $halt = $this->market->halt($staff->id, $this->noteId, 'Business under review.', (string) Str::uuid());

    expect($halt['data'])->toMatchArray(['note_id' => $this->noteId, 'cancelled_listings' => 1])
        ->and(($this->list)('10', '2700')['code'])->toBe('NOTE_HALTED')
        ->and($this->market->halt($staff->id, (string) Str::ulid(), 'Unknown.', (string) Str::uuid())['code'])->toBe('NOTE_NOT_FOUND');
});

it('refuses a halt from staff without the compliance permission', function (): void {
    $treasury = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($treasury, ['treasury'], 'Treasury duty.');

    expect(fn () => $this->market->halt($treasury->id, null, 'Reason.', (string) Str::uuid()))->toThrow(IdentityViolation::class)
        ->and(StaffFixture::permissions(['compliance']))->toContain('secondary.halt', 'secondary.listings.cancel')
        ->and(StaffFixture::permissions(['superadmin']))->not->toContain('secondary.halt');
});

it('refuses resale postings the database cannot bind to a reservation', function (): void {
    $listing = ($this->list)('10', '2700')['data']['listing'];
    ($this->reserve)($listing['listing_id'], '4');
    $hold = DB::table('ledger_entries')->where('kind', 'secondary_hold')->sole();

    expect(fn () => DB::table('ledger_entries')->where('id', $hold->id)->delete())->toThrow(QueryException::class)
        ->and(fn () => SecondaryReservationRecord::query()->sole()->forceFill(['units' => 5])->save())->toThrow(QueryException::class)
        ->and(fn () => SecondaryListingRecord::query()->sole()->forceFill(['listed_units' => 11])->save())->toThrow(QueryException::class);
});

it('rolls back cleanly before any resale activity, and refuses once a listing exists', function (): void {
    $migration = require database_path('migrations/2026_11_11_060000_create_secondary_market_tables.php');
    ($this->flush)();
    $migration->down();

    expect(DB::table('permissions')->where('name', 'secondary.halt')->exists())->toBeFalse()
        ->and(DB::selectOne("SELECT to_regclass('secondary_listings') AS name")->name)->toBeNull();

    $migration->up();
    ($this->list)('10', '2700');

    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Recorded resale activity requires a forward migration');
});

it('refuses commands on lots, listings and reasons that do not exist', function (): void {
    $staff = ($this->compliance)();
    $listing = ($this->list)('10', '2700')['data']['listing'];

    expect(($this->list)('1', lot: (string) Str::ulid()))->toMatchArray(['code' => 'LOT_NOT_FOUND', 'http_status' => 404])
        ->and(($this->reserve)(strtolower((string) Str::ulid())))->toMatchArray(['code' => 'LISTING_NOT_FOUND', 'http_status' => 404])
        ->and($this->market->amend($this->buyer['user']->id, 1, $listing['listing_id'], '2000', (string) Str::uuid()))
        ->toMatchArray(['code' => 'LISTING_NOT_FOUND', 'http_status' => 404])
        ->and($this->market->halt($staff->id, null, '  ', (string) Str::uuid()))->toMatchArray(['code' => 'REASON_REQUIRED', 'http_status' => 422])
        ->and($this->market->lift($staff->id, null, '', (string) Str::uuid()))->toMatchArray(['code' => 'REASON_REQUIRED', 'http_status' => 422]);
});

it('knows no holders for an unknown note or lot type', function (): void {
    $holders = app(NoteUnitHolders::class);

    expect(DB::transaction(fn (): array => $holders->lots((string) Str::ulid())))->toBe([])
        ->and(DB::transaction(fn (): ?array => $holders->lot($this->noteId, 'unknown', $this->holding->id)))->toBeNull();
});

it('sweeps lapsed reservations and expired listings on the schedule', function (): void {
    $listing = ($this->list)('10', '5000')['data']['listing'];
    ($this->reserve)($listing['listing_id'], '4');
    $this->travel(8)->days();

    expect(Artisan::call('secondary:expire'))->toBe(0)
        ->and(Artisan::output())->toContain('Released 1 reservations and expired 1 listings.');
});
