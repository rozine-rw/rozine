<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/* Review #175 (B). Dumps the server progress for UI rendering; PROBE = expected, OBSERVE = finding. */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = function (array $investor, string $units): PrimaryReservationRecord {
        $result = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->confirm = function (array $investor, PrimaryReservationRecord $root): void {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
        expect($this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, $version->revision,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    };
    $this->release = fn (array $investor, PrimaryReservationRecord $root) => $this->checkout->release($investor['user']->id, 1, $this->campaign->id, $root->id, 1, (string) Str::uuid());
    $this->page = fn (): array => app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    $this->dump = function (string $name, array $page): void {
        $dir = getenv('R175W_OUT') ?: sys_get_temp_dir();
        file_put_contents($dir.'/'.$name.'.json', json_encode(['lifecycle' => $page['lifecycle'], 'can_cancel' => $page['can_cancel'], 'progress' => $page['progress']], JSON_PRETTY_PRINT));
        fwrite(STDERR, $name.': '.json_encode($page['progress'])."\n");
    };
});

it('OBSERVE mixed progress: committed + reserved + remaining exceeds the target; unit parts do not sum to total', function (): void {
    $investor = PrimaryReservationFixture::investor();
    ($this->confirm)($investor, ($this->reserve)($investor, '3'));
    ($this->confirm)($investor, ($this->reserve)($investor, '2'));
    ($this->reserve)($investor, '4');
    ($this->release)($investor, ($this->reserve)($investor, '5'));
    $expired = ($this->reserve)($investor, '6');
    $this->travelTo($expired->expires_at);
    ($this->reserve)($investor, '7');
    $page = ($this->page)();
    ($this->dump)('mixed', $page);
    $p = $page['progress'];
    $money = (int) $p['committed']['amount'] + (int) $p['reserved']['amount'] + (int) $p['remaining']['amount'];
    $units = (int) $p['units']['committed'] + (int) $p['units']['reserved'] + (int) $p['units']['available'];
    fwrite(STDERR, "money parts=$money target={$page['principal']}; unit parts=$units total={$p['units']['total']}\n");
    expect($money)->toBeGreaterThan((int) $page['principal'])->and($units)->toBeLessThan((int) $p['units']['total']);
});

it('OBSERVE two abandoned checkouts leave a live raise with zero available units that cannot be bought', function (): void {
    $first = PrimaryReservationFixture::investor();
    $second = PrimaryReservationFixture::investor();
    ($this->release)($first, ($this->reserve)($first, '1080'));
    ($this->release)($second, ($this->reserve)($second, '1080'));
    $page = ($this->page)();
    ($this->dump)('burned', $page);
    $third = PrimaryReservationFixture::investor();
    $refusal = $this->checkout->reserve($third['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
    fwrite(STDERR, "new reserve after burn: $refusal\n");
    expect($page['progress'])->toMatchArray(['phase' => 'raising', 'lifecycle' => 'live', 'funded_pct' => '0.0',
        'remaining' => ['currency' => 'RWF', 'amount' => '10800000'], 'units' => ['total' => '2160', 'available' => '0', 'reserved' => '0', 'committed' => '0']])
        ->and($page['can_cancel'])->toBeTrue()->and($refusal)->not->toBe('accepted');
});

it('OBSERVE every note held in live checkouts is still reported lifecycle live, never fully_reserved', function (): void {
    $first = PrimaryReservationFixture::investor();
    $second = PrimaryReservationFixture::investor();
    ($this->reserve)($first, '1080');
    ($this->reserve)($second, '1080');
    $page = ($this->page)();
    ($this->dump)('all_held', $page);
    expect($page['progress']['lifecycle'])->toBe('live')->and($page['progress']['units']['available'])->toBe('0');
});

it('PROBE exactly full and one unit short, plus a minimal commitment', function (string $case): void {
    $first = PrimaryReservationFixture::investor();
    $second = PrimaryReservationFixture::investor();
    ($this->confirm)($first, ($this->reserve)($first, $case === 'tiny' ? '1' : '1080'));
    if ($case !== 'tiny') {
        ($this->confirm)($second, ($this->reserve)($second, $case === 'full' ? '1080' : '1079'));
    }
    $page = ($this->page)();
    ($this->dump)('pct_'.$case, $page);
    expect($page['progress']['phase'])->toBe('raising')
        ->and($page['progress']['funded_pct'])->toBe(['full' => '100.0', 'short' => '99.9', 'tiny' => '0.0'][$case]);
})->with(['full', 'short', 'tiny']);

it('OBSERVE after the campaign deadline with a commitment the page stays raising/live and campaigns:expire defers', function (): void {
    $investor = PrimaryReservationFixture::investor();
    ($this->confirm)($investor, ($this->reserve)($investor, '3'));
    $this->travelTo($this->campaign->expires_at->addHour());
    $this->artisan('campaigns:expire')->assertSuccessful();
    $page = ($this->page)();
    ($this->dump)('elapsed', $page);
    expect($page['lifecycle'])->toBe('live')->and($page['progress']['lifecycle'])->toBe('live');
});

it('OBSERVE a refunded commitment still counts as committed, an investor and funded_pct on the Business page and API', function (): void {
    $investor = PrimaryReservationFixture::investor();
    $root = ($this->reserve)($investor, '1080');
    ($this->confirm)($investor, $root);
    $before = ($this->page)()['progress'];
    DB::transaction(function () use ($root): void {
        $wallets = app(WalletPostings::class);
        $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    });
    $after = ($this->page)();
    ($this->dump)('refunded', $after);
    Sanctum::actingAs(User::query()->findOrFail($this->campaign->actor_user_id), ['business:read']);
    $api = $this->getJson(route('api.v1.business.campaigns.show', ['business' => $this->campaign->business_id, 'campaign' => $this->campaign->id]))->assertOk();
    fwrite(STDERR, 'refunds='.LedgerEntry::query()->where('kind', 'primary_refund')->count().' api funded_pct='.$api->json('data.note.progress.funded_pct')
        .' investors='.$api->json('data.note.progress.investors').' committed='.$api->json('data.note.progress.committed.amount')."\n");
    expect($after['progress'])->toBe($before)->and($after['progress']['funded_pct'])->toBe('50.0')->and($after['progress']['investors'])->toBe(1)
        ->and($api->json('data.note.progress.funded_pct'))->toBe('50.0');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});
