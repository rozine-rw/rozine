<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\BrowseLedger;
use App\Application\Operations\Contracts\LedgerJournal;
use App\Models\BusinessProfile;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The Admin Ledger's edges: an opened entry whose money flow has no recorded origin, one started
 * by a staff member, and a purchase posting that moved under its Business rather than a provider
 * reference.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    config(['app.staff_ledger_view_signed_off' => true]);
    $this->staff = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($this->staff->id, true, 'Ledger assignment.', (string) Str::uuid(), ['treasury']);

    /**
     * Opens one entry through BrowseLedger with the journal returning a single posting that
     * names the given origin operation.
     *
     * @return array<string, mixed>|null
     */
    $this->opened = function (?string $operationId): ?array {
        $entry = ['id' => '01k00000000000000000000001', 'kind' => 'deposit_credit', 'at' => '2026-10-14T05:00:00+00:00', 'reference' => null,
            'operation_id' => $operationId, 'lines' => []];
        app()->instance(LedgerJournal::class, new
        /** @phpstan-import-type Entry from LedgerJournal */
        class($entry) implements LedgerJournal
        {
            /** @param Entry $entry */
            public function __construct(private array $entry) {}

            public function entries(?string $search, int $limit, ?string $before = null): array
            {
                return ['entries' => [], 'total' => 0, 'next_before' => null];
            }

            public function entry(string $id): ?array
            {
                return $id === $this->entry['id'] ? $this->entry : null;
            }
        });

        return app(BrowseLedger::class)->handle($this->staff->id, null, 50, '01k00000000000000000000001')['entry'];
    };
});

it('opens an entry with no origin when it carries no operation or none is recorded for it', function (): void {
    expect(($this->opened)(null))->toMatchArray(['id' => '01k00000000000000000000001', 'operation_id' => null, 'origin' => null])
        ->and(($this->opened)(strtolower((string) Str::ulid()))['origin'])->toBeNull();
});

it('names a staff-started origin by its staff reference, never by name', function (): void {
    $operation = CommandOperation::factory()->create(['actor_key' => 'staff:'.$this->staff->id, 'actor_user_id' => $this->staff->id]);
    $recorded = CommandOperation::query()->whereKey($operation->id)->sole();

    expect(($this->opened)($operation->id)['origin'])->toBe(['actor' => 'STAFF-'.$this->staff->id, 'at' => $recorded->created_at?->toIso8601String()]);
});

it('gives a purchase posting the Business it moved under as its reference, and finds it by that name', function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    PrimaryReservationFixture::reserve($campaign, PrimaryReservationFixture::investor(), '3');
    $hold = (string) LedgerEntry::query()->where('kind', 'primary_hold')->sole()->id;
    $business = (string) BusinessProfile::query()->whereKey($campaign->business_id)->sole()->profile['name'];
    $journal = app(LedgerJournal::class);

    expect($journal->entry($hold))->toMatchArray(['id' => $hold, 'kind' => 'primary_hold', 'reference' => $business])
        ->and(array_column($journal->entries(mb_strtolower($business), 50)['entries'], 'reference', 'id'))->toMatchArray([$hold => $business]);
});
