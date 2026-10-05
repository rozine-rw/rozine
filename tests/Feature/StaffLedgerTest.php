<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\OperationRecords;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Wallet\Contracts\LedgerReader;
use App\Application\Wallet\GetStaffLedger;
use App\Domain\Identity\StaffPermission;
use App\Models\LedgerEntry;
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
            'disbursements' => ['url' => '/admin/disbursements', 'method' => 'get']])
        ->and($props['entries'])->toBe([['id' => $credit->id, 'at' => $credit->created_at->toIso8601String(), 'kind' => 'deposit', 'from' => 'ROZINE',
            'to' => $wallet, 'reference' => $reference, 'amount' => ['currency' => 'RWF', 'amount' => '20000'],
            'link' => ['url' => '/admin/ledger?entry='.$credit->id, 'method' => 'get']]]);
    expect(json_encode($props, JSON_THROW_ON_ERROR))->not->toContain((string) $this->investor['party']->id)->not->toContain($this->investor['user']->name);

    $entry = ($this->ledger)(['entry' => $credit->id])->viewData('page')['props']['entry'];
    expect($entry)->toMatchArray(['id' => $credit->id, 'kind' => 'deposit', 'operation_id' => $credit->origin_operation_id, 'balanced' => true,
        'totals' => ['debit' => ['currency' => 'RWF', 'amount' => '20000'], 'credit' => ['currency' => 'RWF', 'amount' => '20000']],
        'contra_of' => null, 'contra_by' => null, 'links' => ['close' => ['url' => '/admin/ledger', 'method' => 'get'], 'events' => null]])
        ->and(array_column($entry['postings'], 'account_code'))->toEqualCanonicalizing(['deposit_clearing', 'investor_available'])
        ->and($entry['posted_by']['reason'])->toBeNull();

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

it('attributes an entry to its recording actor by reference only', function (?string $actorKey, string $actor): void {
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

    expect(app(GetStaffLedger::class)->page($this->treasury->id, ['entry' => $credit->id])['entry']['posted_by']['actor'] ?? null)->toBe($actor);
})->with([
    'no recorded operation' => [null, 'ROZINE'],
    'a Party' => ['party:01k0000000000000000000abcd', 'PARTY-0000ABCD'],
    'a staff member' => ['staff:42', 'STAFF-42'],
    'a provider' => ['provider:synthetic', 'PROVIDER:SYNTHETIC'],
]);

it('refuses staff without ledger.view, and API tokens without the ledger ability', function (): void {
    $this->actingAs(DisbursementFixture::staff(['analyst']))->get(route('staff.ledger.index'))->assertForbidden();

    Sanctum::actingAs($this->treasury, ['staff:disbursements:read']);
    $this->getJson(route('api.v1.staff.ledger.index'))->assertForbidden();
    Sanctum::actingAs($this->treasury, ['staff:ledger:read']);
    expect($this->getJson(route('api.v1.staff.ledger.index'))->assertOk()->json('data.entries.0.link.url'))->toStartWith('/api/v1/staff/ledger?entry=');
    $this->getJson(route('api.v1.staff.ledger.index', ['before' => 'not-a-ulid']))->assertUnprocessable()->assertJsonValidationErrors('before');
});
