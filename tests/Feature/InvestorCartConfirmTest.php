<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\QuoteInvestorCart;
use App\Application\Primary\ReservationConfirmation;
use App\Application\Primary\ReserveInvestorCart;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\RiskStatements;
use App\Infrastructure\Primary\EloquentCartCheckouts;
use App\Models\CommandOperation;
use App\Models\InvestorCartCheckout;
use App\Models\InvestorCartCheckoutVersion;
use App\Models\InvestorCartLine;
use App\Models\InvestorCartQuote;
use App\Models\InvestorCartRoot;
use App\Models\PrimaryCommitment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\DecoratedPrimaryReservations;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The whole-cart confirmation (#383 step 4b, contract cart-checkout-1 `confirmAll`): every held line
 * bought at once under one `cart.confirm`, each with its own `primary.confirm` receipt, or none. If
 * anything the Investor acknowledged changed, nothing is bought and the checkout is requoted.
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $this->second = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->user = $this->investor['user'];
    $this->party = $this->investor['party']->id;
    $this->add = fn (string $campaign, string $units) => $this->actingAs($this->user)
        ->postJson(route('investor.cart_add', ['campaign' => $campaign]), ['identity_context_revision' => 1, 'units' => $units])->assertOk();
    $this->acknowledge = fn () => $this->actingAs($this->user)->postJson(route('investor.risks.acknowledge'), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'version' => RiskStatements::VERSION,
        'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true])->assertOk();
    $this->quote = fn (int $revision) => app(QuoteInvestorCart::class)->handle($this->user->id, 1, $revision);
    $this->reserve = fn (array $quote, ?string $requestId = null, ?string $sha = null, int $quoteRevision = 1, ?User $user = null) => app(ReserveInvestorCart::class)
        ->handle(($user ?? $this->user)->id, ($user ?? $this->user)->context_revision, $quote['cart_revision'], $quote['id'], $quoteRevision,
            $sha ?? $quote['disclosure_sha256'], $requestId ?? (string) Str::uuid());
    $this->refusal = function (Closure $command): CommandRejection {
        try {
            $command();
        } catch (CommandRejection $rejection) {
            return $rejection;
        }
        throw new RuntimeException('The command was not refused.');
    };
    $this->capital = fn (string $outstanding) => app()->instance(HeldNoteCapital::class, new readonly class($outstanding) implements HeldNoteCapital
    {
        public function __construct(private string $outstanding) {}

        public function current(string $partyId): array
        {
            return ['outstanding_principal' => $this->outstanding, 'revision' => 4, 'sha256' => str_repeat('c', 64), 'complete' => true, 'as_of' => '2026-11-01T00:00:00Z'];
        }
    });
    $this->cart = function (string $first = '2', string $second = '3'): array {
        ($this->add)($this->campaign->id, $first);
        ($this->add)($this->second->id, $second);
        ($this->acknowledge)();

        return ($this->quote)(2);
    };
    $this->failingLock = fn (string $campaignId, int $failingCall) => app()->instance(PrimaryCampaignSource::class,
        new class(app(PrimaryCampaignSource::class), $campaignId, $failingCall) implements PrimaryCampaignSource
        {
            private int $calls = 0;

            public function __construct(private PrimaryCampaignSource $live, private string $campaignId, private int $failingCall) {}

            public function lockBusiness(string $campaignId): void
            {
                $this->live->lockBusiness($campaignId);
            }

            public function rejectKnownConnections(string $businessId, array $partyIds): void
            {
                $this->live->rejectKnownConnections($businessId, $partyIds);
            }

            public function lock(string $campaignId): array
            {
                if ($campaignId === $this->campaignId && ++$this->calls === $this->failingCall) {
                    throw new CommandRejection('CAMPAIGN_NOT_OPEN');
                }

                return $this->live->lock($campaignId);
            }

            public function lockForFunding(string $campaignId): array
            {
                return $this->live->lockForFunding($campaignId);
            }

            public function lockRetained(string $campaignId): array
            {
                return $this->live->lockRetained($campaignId);
            }

            public function presentation(string $campaignId): array
            {
                return $this->live->presentation($campaignId);
            }
        });
});

beforeEach(function (): void {
    // Runs the deferred checks a commit would, then defers again: the next command is a new request.
    $this->committed = function (): void {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
    };
    $this->held = function (string $first = '2', string $second = '3'): array {
        $reserved = ($this->reserve)(($this->cart)($first, $second));
        ($this->committed)();
        $checkout = InvestorCartCheckout::query()->whereKey($reserved['data']['checkout_id'])->sole();

        return ['reserved' => $reserved, 'checkout' => $checkout, 'quote' => InvestorCartQuote::query()->whereKey($checkout->investor_cart_quote_id)->sole()->payload];
    };
    $this->confirm = fn (string $checkoutId, array $quote, int $revision = 1, ?string $requestId = null, ?string $sha = null, int $quoteRevision = 1) => app(ReserveInvestorCart::class)
        ->confirm($this->user->id, 1, $checkoutId, $revision, $quote['id'], $quoteRevision, $sha ?? $quote['disclosure_sha256'], $requestId ?? (string) Str::uuid());
    $this->ordered = function (): array {
        $ordered = [$this->campaign->id, $this->second->id];
        sort($ordered);

        return $ordered;
    };
    $this->reservations = fn (?Closure $requotes = null, ?Closure $confirmed = null) => app()->instance(PrimaryReservations::class,
        new DecoratedPrimaryReservations(app(PrimaryReservations::class), requotes: $requotes, confirmed: $confirmed));
});

it('buys every held line at once, each under its own confirmation receipt, and empties the cart in one revision', function (): void {
    ['reserved' => $reserved, 'checkout' => $checkout, 'quote' => $quote] = ($this->held)();
    $requestId = (string) Str::uuid();

    $result = ($this->confirm)($checkout->id, $quote, requestId: $requestId);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    $ordered = ($this->ordered)();
    $commitments = PrimaryCommitment::query()->get()->keyBy('primary_reservation_id');
    $removed = array_column($quote['lines'], 'cart_line_id');
    sort($removed);
    expect($result)->toMatchArray(['status' => 'completed', 'code' => 'CART_CONFIRMED', 'revision' => 2])
        ->and($result['data'])->toMatchArray(['checkout_id' => $checkout->id, 'quote_id' => $quote['id'], 'disclosure_sha256' => $quote['disclosure_sha256'],
            'fee_band' => $quote['fee_band'], 'confirmed_at' => now('UTC')->format('Y-m-d\TH:i:s.u\Z'), 'total_principal' => ['currency' => 'RWF', 'amount' => '25000']])
        ->and($result['data']['cart'])->toEqual(['revision' => 3, 'count' => 0, 'removed_line_ids' => $removed])
        ->and(array_column($result['data']['lines'], 'campaign_id'))->toBe($ordered)
        ->and(array_column($result['data']['lines'], 'principal'))->toEqual(array_column($reserved['data']['lines'], 'principal'))
        ->and($commitments)->toHaveCount(2)
        ->and(InvestorCartLine::query()->count())->toBe(0)
        ->and(InvestorCartRoot::query()->sole()->revision)->toBe(3)
        ->and(DB::table('investor_cart_live_checkouts')->count())->toBe(0)
        ->and(InvestorCartCheckoutVersion::query()->orderBy('revision')->get()->map->only(['revision', 'state', 'operation_id'])->all())->toBe([
            ['revision' => 1, 'state' => 'held', 'operation_id' => $reserved['operation_id']],
            ['revision' => 2, 'state' => 'confirmed', 'operation_id' => $result['operation_id']]]);

    foreach ($result['data']['lines'] as $line) {
        $child = CommandOperation::query()->findOrFail($commitments[$line['reservation_id']]->operation_id);
        expect($line['commitment_id'])->toBe($commitments[$line['reservation_id']]->id)
            ->and($line['fee_rate_bps'])->toBe($quote['fee_band']['rate_bps'])
            ->and($child->only(['command', 'target_type', 'target_id', 'request_id']))->toBe(['command' => 'primary.confirm', 'target_type' => 'primary_reservation',
                'target_id' => $line['reservation_id'], 'request_id' => EloquentCartCheckouts::childRequestId($result['operation_id'], 'confirm', $line['campaign_id'])])
            ->and($child->result)->toMatchArray(['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED'])
            ->and($child->result['data'])->toMatchArray(['entry_id' => $line['entry_id'], 'cart_operation_id' => $result['operation_id']]);
    }

    $service = app(ReserveInvestorCart::class);
    expect(($this->confirm)($checkout->id, $quote, requestId: $requestId))->toBe($result)
        ->and($service->findOperation($this->user->id, 1, 'cart.confirm', $checkout->id, $requestId))->toBe($result)
        ->and(($this->refusal)(fn () => $service->findOperation($this->user->id, 1, 'cart.release', $checkout->id, $requestId)))->reason->toBe('OPERATION_NOT_FOUND')
        ->and(($this->refusal)(fn () => $service->findOperation($this->user->id, 1, 'cart.confirm', strtolower((string) Str::ulid()), $requestId)))->reason->toBe('OPERATION_NOT_FOUND')
        ->and(($this->confirm)($checkout->id, $quote, 2))->toMatchArray(['status' => 'rejected', 'code' => 'CART_RESERVATION_NOT_HELD'])
        ->and($service->reservation($this->user->id, 1, $checkout->id))->toMatchArray(['revision' => 2, 'state' => 'confirmed'])
        ->and(PrimaryCommitment::query()->count())->toBe(2);
});

it('requotes every changed line and buys nothing when the band changed, then buys at the new terms once confirmed again', function (): void {
    ['checkout' => $checkout, 'quote' => $quote] = ($this->held)();
    ($this->capital)('990000');
    $requestId = (string) Str::uuid();

    $requoted = ($this->confirm)($checkout->id, $quote, requestId: $requestId);
    ($this->committed)();

    $replacement = $requoted['data']['replacement_quote'];
    expect($requoted)->toMatchArray(['status' => 'completed', 'code' => 'CART_REQUOTED', 'revision' => 2])
        ->and($requoted['data'])->toMatchArray(['checkout_id' => $checkout->id, 'state' => 'held', 'revision' => 2, 'requires_reconfirmation' => true,
            'expires_at' => $checkout->expires_at->format('Y-m-d\TH:i:s.u\Z')])
        ->and($requoted['data']['changed_line_ids'])->toHaveCount(2)
        ->and($replacement['id'])->not->toBe($quote['id'])
        ->and($replacement['fee_band']['rate_bps'])->not->toBe($quote['fee_band']['rate_bps'])
        ->and($replacement['expires_at'])->toBe($requoted['data']['expires_at'])
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(InvestorCartLine::query()->count())->toBe(2)
        ->and(DB::table('investor_cart_live_checkouts')->count())->toBe(1)
        ->and(DB::table('primary_reservation_versions')->where('revision', 2)->where('state', 'held')->count())->toBe(2)
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->get()->pluck('result.code')->unique()->values()->all())->toBe(['RESERVATION_REQUOTED'])
        ->and(app(ReserveInvestorCart::class)->reservation($this->user->id, 1, $checkout->id))->toMatchArray(['revision' => 2, 'state' => 'held', 'quote' => $replacement]);

    expect(($this->confirm)($checkout->id, $quote, requestId: $requestId))->toBe($requoted)
        ->and(($this->confirm)($checkout->id, $quote, 2))->toMatchArray(['status' => 'rejected', 'code' => 'CART_QUOTE_STALE'])
        ->and(($this->confirm)($checkout->id, $replacement, 2, sha: $quote['disclosure_sha256']))->toMatchArray(['status' => 'rejected', 'code' => 'CART_QUOTE_STALE']);

    $confirmed = ($this->confirm)($checkout->id, $replacement, 2);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect($confirmed)->toMatchArray(['status' => 'completed', 'code' => 'CART_CONFIRMED', 'revision' => 3])
        ->and($confirmed['data']['quote_id'])->toBe($replacement['id'])
        ->and(array_unique(array_column($confirmed['data']['lines'], 'fee_rate_bps')))->toBe([$replacement['fee_band']['rate_bps']])
        ->and(PrimaryCommitment::query()->count())->toBe(2);
});

it('requotes without new line terms when only an acknowledged band figure changed', function (): void {
    ['checkout' => $checkout, 'quote' => $quote] = ($this->held)();
    ($this->capital)('1000');

    $requoted = ($this->confirm)($checkout->id, $quote);

    expect($requoted)->toMatchArray(['status' => 'completed', 'code' => 'CART_REQUOTED'])
        ->and($requoted['data']['changed_line_ids'])->toBe([])
        ->and($requoted['data']['replacement_quote']['fee_band']['rate_bps'])->toBe($quote['fee_band']['rate_bps'])
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->count())->toBe(0)
        ->and(DB::table('primary_reservation_versions')->where('revision', 2)->count())->toBe(0);
});

it('refuses a stale revision or acknowledgement, an unknown checkout and an elapsed one, buying nothing', function (): void {
    ['checkout' => $checkout, 'quote' => $quote] = ($this->held)();

    expect(($this->confirm)($checkout->id, $quote, 2))->toMatchArray(['status' => 'rejected', 'code' => 'VERSION_CONFLICT', 'revision' => 1])
        ->and(($this->confirm)($checkout->id, $quote, sha: str_repeat('b', 64)))->toMatchArray(['status' => 'rejected', 'code' => 'CART_QUOTE_STALE'])
        ->and(($this->confirm)($checkout->id, $quote, quoteRevision: 2)['code'])->toBe('CART_QUOTE_STALE')
        ->and(($this->confirm)($checkout->id, [...$quote, 'id' => strtolower((string) Str::ulid())])['data'])->toMatchArray(['checkout_id' => $checkout->id,
            'quote_id' => $quote['id'], 'atomic' => true, 'financial_effect' => 'none', 'requires_reconfirmation' => true])
        ->and(($this->refusal)(fn () => ($this->confirm)(strtolower((string) Str::ulid()), $quote)))->reason->toBe('CART_RESERVATION_NOT_FOUND');

    $this->travelTo($checkout->expires_at);
    expect(($this->confirm)($checkout->id, $quote))->toMatchArray(['status' => 'rejected', 'code' => 'CART_RESERVATION_EXPIRED'])
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(InvestorCartCheckoutVersion::query()->count())->toBe(1);
});

it('refuses the whole cart when one line can no longer be admitted, naming it', function (): void {
    ['checkout' => $checkout, 'quote' => $quote] = ($this->held)();

    ($this->failingLock)($this->second->id, 1);
    $refused = ($this->confirm)($checkout->id, $quote);

    expect($refused)->toMatchArray(['status' => 'rejected', 'code' => 'CART_LINES_REFUSED', 'http_status' => 409])
        ->and($refused['data'])->toMatchArray(['checkout_id' => $checkout->id, 'atomic' => true, 'financial_effect' => 'none', 'cart_refusals' => []])
        ->and($refused['data']['line_refusals'])->toEqual([['cart_line_id' => array_column($quote['lines'], 'cart_line_id', 'campaign_id')[$this->second->id],
            'campaign_id' => $this->second->id, 'code' => 'CAMPAIGN_NOT_OPEN', 'http_status' => 409, 'field_errors' => []]])
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->count())->toBe(0);
});

it('rolls everything back when a line confirms other than it was admitted, or the cart no longer matches its checkout', function (): void {
    ['checkout' => $checkout, 'quote' => $quote] = ($this->held)();

    ($this->reservations)(confirmed: fn (ReservationConfirmation $result): ReservationConfirmation => new ReservationConfirmation($result->id, $result->revision,
        $result->reservation, null, null));
    expect(fn () => ($this->confirm)($checkout->id, $quote))->toThrow(RuntimeException::class, 'CART_CHECKOUT_INTEGRITY_FAILED');

    app()->forgetInstance(PrimaryReservations::class);
    ($this->reservations)(requotes: fn (bool $requotes): bool => true);
    expect(fn () => ($this->confirm)($checkout->id, $quote))->toThrow(RuntimeException::class, 'CART_CHECKOUT_INTEGRITY_FAILED');

    app()->forgetInstance(PrimaryReservations::class);
    DB::table('investor_cart_lines')->where('business_campaign_id', $this->second->id)->delete();
    expect(fn () => ($this->confirm)($checkout->id, $quote))->toThrow(RuntimeException::class, 'CART_CHECKOUT_INTEGRITY_FAILED')
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'cart.confirm')->count())->toBe(0);
});
