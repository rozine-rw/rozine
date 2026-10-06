<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\OperationRecords;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\ExpireReservations;
use App\Application\Wallet\Contracts\LedgerReader;
use App\Application\Wallet\GetStaffLedger;
use App\Domain\Identity\StaffPermission;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\DisbursementFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->investor = PrimaryReservationFixture::investor('20000');
    $this->treasury = DisbursementFixture::staff(['treasury']);
    $this->ledger = fn (array $query = []) => $this->actingAs($this->treasury)->get(route('staff.ledger.index', $query));
});

it('grants ledger.view to treasury, compliance and superadmin only', function (): void {
    foreach (['treasury', 'compliance', 'superadmin'] as $role) {
        expect(StaffPermission::forRoles([$role]))->toContain('ledger.view');
    }
    foreach (['analyst', 'approver'] as $role) {
        expect(StaffPermission::forRoles([$role]))->not->toContain('ledger.view');
    }
});

it('lists the sealed journal newest first by reference, never by owner, and opens an entry with balanced postings', function (): void {
    $credit = LedgerEntry::query()->where('kind', 'deposit_credit')->sole();
    $props = ($this->ledger)()->assertOk()->assertInertia(fn ($page) => $page->component('admin/ledger'))->viewData('page')['props'];
    $wallet = 'INV-'.strtoupper(substr($credit->wallet_id, -8));
    $reference = 'RZL-'.strtoupper(substr($credit->source_id, -10));

    expect($props)->toMatchArray(['contract_version' => 'staff-ledger-v1', 'total' => 1, 'more' => null, 'entry' => null, 'search' => ''])
        ->and($props['nav'])->toMatchArray(['ledger' => ['url' => '/admin/ledger', 'method' => 'get'], 'events' => null, 'applications' => null,
            'disbursements' => ['url' => '/admin/disbursements', 'method' => 'get'], 'launcher' => ['url' => '/dashboard', 'method' => 'get']])
        ->and($props['entries'])->toBe([['id' => $credit->id, 'at' => $credit->created_at->toIso8601String(), 'kind' => 'deposit', 'from' => 'ROZINE',
            'to' => $wallet, 'reference' => $reference, 'amount' => ['currency' => 'RWF', 'amount' => '20000'],
            'link' => ['url' => '/admin/ledger?entry='.$credit->id, 'method' => 'get']]]);
    expect(json_encode($props, JSON_THROW_ON_ERROR))->not->toContain((string) $this->investor['party']->id)->not->toContain($this->investor['user']->name);

    $entry = ($this->ledger)(['entry' => $credit->id])->viewData('page')['props']['entry'];
    expect($entry)->toMatchArray(['id' => $credit->id, 'kind' => 'deposit', 'operation_id' => $credit->origin_operation_id, 'balanced' => true,
        'totals' => ['debit' => ['currency' => 'RWF', 'amount' => '20000'], 'credit' => ['currency' => 'RWF', 'amount' => '20000']],
        'contra_of' => null, 'contra_by' => null, 'links' => ['close' => ['url' => '/admin/ledger', 'method' => 'get'], 'events' => null]])
        ->and(array_column($entry['postings'], 'account_code'))->toEqualCanonicalizing(['deposit_clearing', 'investor_available'])
        ->and($entry)->not->toHaveKey('posted_by')
        ->and($entry['origin'])->toMatchArray(['at' => app(OperationRecords::class)->attribution((string) $credit->origin_operation_id)['recorded_at'] ?? null, 'reason' => null])
        ->and($entry['origin']['actor'])->not->toContain((string) $this->investor['party']->id);

    expect(($this->ledger)(['search' => $reference])->viewData('page')['props']['total'])->toBe(1)
        ->and(($this->ledger)(['search' => $wallet])->viewData('page')['props']['total'])->toBe(1)
        ->and(($this->ledger)(['search' => 'deposit'])->viewData('page')['props']['total'])->toBe(1)
        ->and(($this->ledger)(['search' => 'RZL-NOTHING%'])->viewData('page')['props'])->toMatchArray(['total' => 0, 'entries' => []])
        ->and(($this->ledger)(['entry' => strtolower((string) Str::ulid())])->viewData('page')['props']['entry'])->toBeNull();
});

it('names Primary movements by their own kind and pages with a cursor', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($this->investor['user']->id, 1, $campaign->id, '2', (string) Str::uuid(), PrimaryReservationFixture::terms(...));

    $kinds = array_column(($this->ledger)()->viewData('page')['props']['entries'], 'kind');
    expect($kinds)->toContain('hold', 'deposit');

    $first = app(LedgerReader::class)->page('', null, 1);
    $rest = app(LedgerReader::class)->page('', $first['next_before'], 1);
    expect([$first['total'], count($first['entries']), $rest['entries'][0]['id'] < $first['entries'][0]['id']])->toBe([2, 1, true])
        ->and($rest['next_before'])->toBeNull();
    $props = ($this->ledger)(['before' => $first['entries'][0]['id']])->viewData('page')['props'];
    expect($props['entries'][0]['link']['url'])->toContain('before='.$first['entries'][0]['id']);
});

it('searches through the one parameter the live page sends, and keeps it on every link', function (): void {
    $credit = LedgerEntry::query()->sole();
    $reference = 'RZL-'.strtoupper(substr($credit->source_id, -10));
    $props = $this->actingAs($this->treasury)->get('/admin/ledger?search='.$reference)->assertOk()->viewData('page')['props'];

    expect($props)->toMatchArray(['search' => $reference, 'total' => 1])
        ->and($props['entries'][0]['link']['url'])->toBe('/admin/ledger?search='.$reference.'&entry='.$credit->id);
    $missing = $this->actingAs($this->treasury)->get('/admin/ledger?search=RZL-NOTHING')->viewData('page')['props'];
    expect($missing)->toMatchArray(['search' => 'RZL-NOTHING', 'total' => 0, 'entries' => []]);
});

it('reports a confirmation by its reservation\'s origin, never as its poster, and keeps its own posting time', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    $reservedAt = now()->toIso8601String();
    $checkout->reserve($this->investor['user']->id, 1, $campaign->id, '2', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $initial = PrimaryReservationVersion::query()->sole()->payload;
    $this->travelTo($root->expires_at->subSecond());
    $checkout->confirm($this->investor['user']->id, 1, $campaign->id, $root->id, 1, $initial['terms']['disclosure_version'],
        $initial['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $commit = LedgerEntry::query()->where('kind', 'primary_commit')->sole();
    $confirmation = PrimaryCommitment::query()->sole()->operation_id;

    $entry = ($this->ledger)(['entry' => $commit->id])->viewData('page')['props']['entry'];
    expect($entry)->toMatchArray(['kind' => 'investment', 'at' => now()->toIso8601String(), 'operation_id' => $root->origin_operation_id,
        'origin' => ['actor' => 'PARTY-'.strtoupper(substr($this->investor['party']->id, -8)), 'at' => $reservedAt, 'reason' => null]])
        ->and($entry)->not->toHaveKey('posted_by')
        ->and($entry['operation_id'])->not->toBe($confirmation)
        ->and($entry['at'])->not->toBe($entry['origin']['at']);
});

it('reports a worker release by its reservation\'s origin and the time the worker posted it', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $reservedAt = now()->toIso8601String();
    app(PrimaryCheckout::class)->reserve($this->investor['user']->id, 1, $campaign->id, '2', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $this->travelTo($root->expires_at);
    expect(app(ExpireReservations::class)->handle(100))->toBe(1);
    $release = LedgerEntry::query()->where('kind', 'primary_release')->sole();

    $entry = ($this->ledger)(['entry' => $release->id])->viewData('page')['props']['entry'];
    expect($entry)->toMatchArray(['kind' => 'release', 'at' => $root->expires_at->toIso8601String(), 'operation_id' => $root->origin_operation_id,
        'origin' => ['actor' => 'PARTY-'.strtoupper(substr($this->investor['party']->id, -8)), 'at' => $reservedAt, 'reason' => null]])
        ->and($entry)->not->toHaveKey('posted_by')
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail()->operation_id)->toBeNull();
});

it('names the origin actor by reference only, and no origin when the journal holds none', function (?string $actorKey, ?string $actor): void {
    $attribution = $actorKey === null ? null : ['operation_id' => 'x', 'actor_key' => $actorKey, 'command' => 'c', 'recorded_at' => '2026-10-05T00:00:00+00:00'];
    app()->instance(OperationRecords::class, new readonly class($attribution) implements OperationRecords
    {
        /** @param array{operation_id: string, actor_key: string, command: string, recorded_at: string}|null $attribution */
        public function __construct(private ?array $attribution) {}

        public function forTarget(string $actorKey, string $command, string $targetType, string $targetId): array
        {
            return [];
        }

        public function attribution(string $operationId): ?array
        {
            return $this->attribution;
        }
    });
    $credit = LedgerEntry::query()->sole();

    expect(app(GetStaffLedger::class)->page($this->treasury->id, ['entry' => $credit->id])['entry']['origin']['actor'] ?? null)->toBe($actor);
})->with([
    'no recorded operation' => [null, null],
    'a Party' => ['party:01k0000000000000000000abcd', 'PARTY-0000ABCD'],
    'a staff member' => ['staff:42', 'STAFF-42'],
    'a provider' => ['provider:synthetic', 'PROVIDER:SYNTHETIC'],
]);

it('shows each ledger viewer their own staff role', function (string $roles, string $role): void {
    $viewer = DisbursementFixture::staff(explode(',', $roles));

    expect($this->actingAs($viewer)->get(route('staff.ledger.index'))->viewData('page')['props']['viewer']['role'])->toBe($role);
})->with([
    'treasury' => ['treasury', 'treasury'],
    'compliance' => ['compliance', 'compliance'],
    'superadmin' => ['superadmin', 'superadmin'],
    'treasury and compliance' => ['compliance,treasury', 'treasury'],
]);

it('links the ledger from the other live staff pages only for a viewer with ledger.view', function (): void {
    $ledger = ['url' => '/admin/ledger', 'method' => 'get'];
    $nav = fn (string $role, string $route): mixed => $this->actingAs(DisbursementFixture::staff([$role]))->get(route($route))
        ->assertOk()->viewData('page')['props']['nav']['ledger'];

    expect($nav('treasury', 'staff.disbursements.index'))->toBe($ledger)
        ->and($nav('approver', 'staff.disbursements.index'))->toBeNull()
        ->and($nav('superadmin', 'staff.applications.index'))->toBe($ledger)
        ->and($nav('approver', 'staff.applications.index'))->toBeNull();

    Sanctum::actingAs(DisbursementFixture::staff(['treasury']), ['staff:disbursements:read']);
    expect($this->getJson(route('api.v1.staff.disbursements.index'))->assertOk()->json('data.nav.ledger'))
        ->toBe(['url' => '/api/v1/staff/ledger', 'method' => 'get']);
});

it('refuses staff without ledger.view, and API tokens without the ledger ability', function (): void {
    $this->actingAs(DisbursementFixture::staff(['analyst']))->get(route('staff.ledger.index'))->assertForbidden();

    Sanctum::actingAs($this->treasury, ['staff:disbursements:read']);
    $this->getJson(route('api.v1.staff.ledger.index'))->assertForbidden();
    Sanctum::actingAs($this->treasury, ['staff:ledger:read']);
    $api = $this->getJson(route('api.v1.staff.ledger.index'))->assertOk();
    expect($api->json('data.entries.0.link.url'))->toStartWith('/api/v1/staff/ledger?entry=')
        ->and($api->json('data.nav.launcher'))->toBe(['url' => '/api/v1/identity', 'method' => 'get']);
    $this->getJson(route('api.v1.staff.ledger.index', ['before' => 'not-a-ulid']))->assertUnprocessable()->assertJsonValidationErrors('before');
});
