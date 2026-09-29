<?php

declare(strict_types=1);

/*
 * Review repros for PR #175 range e12a6ea9..ec64f0ea (P2-1 fix: per-campaign settlement deferral in campaigns:expire).
 * Copy into tests/Feature/. "S*:" assert the safe behaviour and must pass; "observe:" pin current behaviour
 * (a known limit or a cost), and pass.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Primary\Contracts\CampaignCommitments;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Domain\Operations\CommandRejection;
use App\Infrastructure\Primary\EloquentCampaignCommitments;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = function (BusinessCampaign $campaign): PrimaryReservationRecord {
        $held = $this->checkout->reserve($this->investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($held['code'])->toBe('RESERVATION_HELD');

        return PrimaryReservationRecord::query()->whereKey($held['data']['reservation_id'])->sole();
    };
    $this->confirm = function (BusinessCampaign $campaign, PrimaryReservationRecord $root): array {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->first();

        return $this->checkout->confirm($this->investor['user']->id, 1, $campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    };
    expect(($this->confirm)($this->campaign, ($this->reserve)($this->campaign))['code'])->toBe('RESERVATION_CONFIRMED');
    $this->another = function (): BusinessCampaign {
        $this->travel(1)->minute();
        Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));

        return PrimaryReservationFixture::campaign();
    };
    /* Treat extra campaigns as committed without building a checkout for each (cheap scale). */
    $this->alsoCommitted = function (array $ids): void {
        app()->instance(CampaignCommitments::class, new readonly class($ids) implements CampaignCommitments
        {
            public function __construct(private array $ids) {}

            public function anyForCampaign(string $campaignId): bool
            {
                return in_array($campaignId, $this->ids, true) || (new EloquentCampaignCommitments)->anyForCampaign($campaignId);
            }
        });
    };
    $this->store = fn (): BusinessCampaignStore => app(BusinessCampaignStore::class);
    $this->closed = fn (): array => BusinessCampaignClosure::query()->orderBy('closed_at')->orderBy('business_campaign_id')->pluck('business_campaign_id')->all();
    $this->exposure = fn (string $business): array => app(BusinessExposureStore::class)->current($business);
    $this->notices = [];
    Event::listen(MessageLogged::class, function (MessageLogged $event): void {
        if ($event->level === 'notice') {
            $this->notices[] = $event->context['campaign_id'];
        }
    });
});

afterEach(function (): void {
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('S1: a deferral inside a later batch cannot overshoot the limit', function (): void {
    [$o1, $o2, $o3] = [($this->another)(), ($this->another)(), ($this->another)()];
    $this->travelTo($o3->expires_at);
    expect(($this->store)()->expireDue(2))->toBe(2)
        ->and(($this->closed)())->toBe([$o1->id, $o2->id])
        ->and(($this->exposure)($o3->business_id))->toHaveCount(1)
        ->and($this->notices)->toBe([$this->campaign->id])
        ->and(($this->store)()->expireDue(2))->toBe(1)
        ->and(($this->closed)())->toBe([$o1->id, $o2->id, $o3->id]);
});

it('S2: more committed campaigns than the limit are all passed over at --limit=1', function (): void {
    [$o1, $o2, $o3] = [($this->another)(), ($this->another)(), ($this->another)()];
    ($this->alsoCommitted)([$o1->id, $o2->id]);
    $this->travelTo($o3->expires_at);
    expect(Artisan::call('campaigns:expire', ['--limit' => 1]))->toBe(0)
        ->and(Artisan::output())->toBe("Expired 1 campaigns.\n")
        ->and(($this->closed)())->toBe([$o3->id])
        ->and($this->notices)->toBe([$this->campaign->id, $o1->id, $o2->id]);
});

it('observe: every run rescans every deferred campaign, one batch query and one Business lock each at --limit=1', function (): void {
    $extra = [($this->another)(), ($this->another)(), ($this->another)(), ($this->another)()];
    ($this->alsoCommitted)(array_map(fn (BusinessCampaign $c): string => $c->id, $extra));
    $this->travelTo(end($extra)->expires_at);
    $runs = [];
    foreach ([1, 1, 100] as $limit) {
        $counts = ['scan' => 0, 'business_lock' => 0];
        DB::listen(function (QueryExecuted $query) use (&$counts): void {
            $sql = $query->sql;
            if (str_starts_with($sql, 'select "id", "business_id", "expires_at" from "business_campaigns"')) {
                $counts['scan']++;
            }
            if (str_contains($sql, 'from "business_profiles"') && str_contains($sql, 'for update')) {
                $counts['business_lock']++;
            }
        });
        $this->notices = [];
        expect(($this->store)()->expireDue($limit))->toBe(0);
        $runs[] = [$limit, $counts['scan'], $counts['business_lock'], count($this->notices)];
        DB::flushQueryLog();
        app('events')->forget(QueryExecuted::class);
    }
    /* 5 deferred campaigns: limit 1 -> 6 scans, 10 Business FOR UPDATE statements (2 per campaign), 5 notices; the second run repeats all of it; limit 100 -> 2 scans. */
    expect($runs)->toBe([[1, 6, 10, 5], [1, 6, 10, 5], [100, 2, 10, 5]])
        ->and(BusinessCampaignClosure::query()->count())->toBe(0);
});

it('S3: an unexpected failure aborts the run but keeps closures already committed and never logs a deferral for it', function (): void {
    [$o1, $o2, $o3] = [($this->another)(), ($this->another)(), ($this->another)()];
    $this->travelTo($o3->expires_at);
    $event = 'eloquent.creating: '.BusinessCampaignClosure::class;
    Event::listen($event, function (BusinessCampaignClosure $closure) use ($o2): void {
        if ($closure->business_campaign_id === $o2->id) {
            throw new RuntimeException('synthetic integrity failure');
        }
    });
    try {
        expect(fn () => ($this->store)()->expireDue(100))->toThrow(RuntimeException::class, 'synthetic integrity failure');
    } finally {
        Event::forget($event);
    }
    expect(($this->closed)())->toBe([$o1->id])
        ->and(($this->exposure)($o2->business_id))->toHaveCount(1)
        ->and(($this->exposure)($o3->business_id))->toHaveCount(1)
        ->and($this->notices)->toBe([$this->campaign->id]);
});

it('S4: a closure that lands after selection neither counts against the limit nor stops the scan', function (): void {
    [$o1, $o2] = [($this->another)(), ($this->another)()];
    $this->travelTo($o2->expires_at);
    $advanced = false;
    $event = 'eloquent.retrieved: '.BusinessCampaign::class;
    Event::listen($event, function (BusinessCampaign $campaign) use (&$advanced, $o1): void {
        if (! $advanced && $campaign->id === $o1->id && ! array_key_exists('principal', $campaign->getAttributes())) {
            $advanced = true;
            expect(($this->store)()->expireDue(1))->toBe(1);
        }
    });
    try {
        expect(($this->store)()->expireDue(1))->toBe(1);
    } finally {
        Event::forget($event);
    }
    expect($advanced)->toBeTrue()->and(($this->closed)())->toBe([$o1->id, $o2->id]);
});

it('S5: a confirmation that lands mid-sweep (skewed clock) on a selected campaign is deferred, not closed', function (): void {
    $o1 = ($this->another)();
    $this->travelTo($o1->expires_at->subSeconds(60));
    $root = ($this->reserve)($o1);
    $this->travelTo($o1->expires_at);
    $cash = null;
    $exposure = ($this->exposure)($o1->business_id);
    $event = 'eloquent.retrieved: '.BusinessCampaign::class;
    $advanced = false;
    Event::listen($event, function (BusinessCampaign $campaign) use (&$advanced, &$cash, $o1, $root): void {
        if (! $advanced && $campaign->id === $o1->id && ! array_key_exists('principal', $campaign->getAttributes())) {
            $advanced = true;
            $this->travelTo($o1->expires_at->subSeconds(30));
            expect(($this->confirm)($o1, $root)['code'])->toBe('RESERVATION_CONFIRMED');
            $cash = LedgerEntry::query()->orderBy('id')->get()->toArray();
            $this->travelTo($o1->expires_at);
        }
    });
    try {
        expect(($this->store)()->expireDue(100))->toBe(0);
    } finally {
        Event::forget($event);
    }
    expect($advanced)->toBeTrue()
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(2)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toArray())->toBe($cash)
        ->and(($this->exposure)($o1->business_id))->toBe($exposure)
        ->and($this->notices)->toBe([$this->campaign->id, $o1->id]);
});

it('observe: the command reports success and only the closure count when every due campaign is deferred', function (): void {
    $this->travelTo($this->campaign->expires_at->addDay());
    expect(Artisan::call('campaigns:expire'))->toBe(0)
        ->and(Artisan::output())->toBe("Expired 0 campaigns.\n")
        ->and($this->notices)->toBe([$this->campaign->id])
        ->and(Artisan::call('campaigns:expire'))->toBe(0)
        ->and($this->notices)->toBe([$this->campaign->id, $this->campaign->id]);
});

it('S6: the command rejects out-of-range limits before sweeping', function (string $limit): void {
    ($this->another)();
    $this->travelTo(now()->addDays(31));
    expect(Artisan::call('campaigns:expire', ['--limit' => $limit]))->toBe(2)
        ->and(Artisan::output())->toContain('The limit must be an integer from 1 to 1000.')
        ->and(BusinessCampaignClosure::query()->count())->toBe(0)
        ->and($this->notices)->toBe([]);
})->with(['0', '-1', '1001', '99999999999999999999', 'abc', '1.5', '1e2', '', '0x10']);

it('S7: the command accepts the boundary limits', function (string $limit, int $closed): void {
    ($this->another)();
    $this->travelTo(now()->addDays(31));
    expect(Artisan::call('campaigns:expire', ['--limit' => $limit]))->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe($closed);
})->with([['1', 1], ['1000', 1], [' 7 ', 1], ['+5', 1]]);

it('S8: the store rejects out-of-range limits', function (int $limit): void {
    expect(fn () => ($this->store)()->expireDue($limit))->toThrow(CommandRejection::class, 'INVALID_SWEEP_LIMIT');
})->with([0, -1, 1001, PHP_INT_MAX, PHP_INT_MIN]);

it('S9: an unrelated refusal after a deferral and a closure still escapes, keeping the closure', function (): void {
    [$o1, $o2] = [($this->another)(), ($this->another)()];
    app()->instance(CampaignCommitments::class, new readonly class($o2->id) implements CampaignCommitments
    {
        public function __construct(private string $failing) {}

        public function anyForCampaign(string $campaignId): bool
        {
            if ($campaignId === $this->failing) {
                throw new CommandRejection('UNRELATED_REFUSAL');
            }

            return (new EloquentCampaignCommitments)->anyForCampaign($campaignId);
        }
    });
    $this->travelTo($o2->expires_at);
    expect(fn () => ($this->store)()->expireDue(100))->toThrow(CommandRejection::class, 'UNRELATED_REFUSAL')
        ->and(($this->closed)())->toBe([$o1->id])
        ->and($this->notices)->toBe([$this->campaign->id]);
});
