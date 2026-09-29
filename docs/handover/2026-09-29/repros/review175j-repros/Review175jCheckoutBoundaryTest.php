<?php

declare(strict_types=1);

/*
 * Review repro for PR #175 range ad598a09..2271131e. Copy into tests/Feature/ and run on PostgreSQL.
 *
 * - The first test documents the order of refusals: an unknown campaign is refused before any
 *   Investor authority is checked, so a caller without authority can tell unknown from known ids.
 * - The commitment-source tests try raw SQL INSERT and UPDATE bypasses with the user triggers disabled,
 *   including case and whitespace variants and the deposit_credit kind.
 * - The last test runs the wallet round trip the way PostgreSqlConfigurationTest does and checks that
 *   the 165949 guard comes back.
 */

use App\Application\Identity\SelectActiveRole;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('OBSERVATION: refuses an unknown campaign before checking Investor authority', function (string $call): void {
    $this->freezeSecond();
    $campaign = PrimaryReservationFixture::campaign();
    $party = Party::factory()->verified()->create();
    $user = User::factory()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
    app(SelectActiveRole::class)->handle($user->id, 'business', 0, (string) Str::uuid());
    $checkout = app(PrimaryCheckout::class);
    $invoke = fn (string $campaignId): array => $call === 'reserve'
        ? $checkout->reserve($user->id, 1, $campaignId, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...))
        : $checkout->findReservation($user->id, 1, $campaignId, (string) Str::uuid());

    expect(fn () => $invoke(strtolower((string) Str::ulid())))->toThrow(CommandRejection::class, 'CAMPAIGN_NOT_FOUND')
        ->and(fn () => $invoke($campaign->id))->toThrow(IdentityViolation::class, 'ROLE_NOT_AVAILABLE');
})->with(['reserve', 'lookup']);

it('rejects raw commitment-source inserts for every kind and spelling with user triggers disabled', function (string $kind, string $sourceType): void {
    $fixture = InvestorWalletFixture::ready();
    $wallet = app(WalletPostings::class)->lockForParty($fixture['party']->id);
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    try {
        DB::transaction(fn () => DB::table('ledger_entries')->insert(['id' => strtolower((string) Str::ulid()), 'wallet_id' => $wallet->walletId,
            'kind' => $kind, 'source_type' => $sourceType, 'source_id' => strtolower((string) Str::ulid()), 'currency' => 'RWF',
            'payload' => '{}', 'sha256' => str_repeat('a', 64), 'created_at' => now(), 'origin_operation_id' => strtolower((string) Str::ulid())]));
        $this->fail('Insert was accepted.');
    } catch (QueryException $exception) {
        expect($exception->getCode())->toBe('23514');
    }
    expect(DB::table('ledger_entries')->whereRaw("lower(trim(source_type)) = 'primary_commitment'")->count())->toBe(0);
})->with(['deposit_credit', 'primary_hold', 'primary_commit', 'primary_release', 'primary_refund'])
    ->with(['primary_commitment', 'Primary_Commitment', 'PRIMARY_COMMITMENT', ' primary_commitment', 'primary_commitment ', "primary_commitment\t"]);

it('rejects a raw UPDATE that relabels a retained deposit as a commitment source', function (string $kind): void {
    $this->freezeSecond();
    $fixture = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture)['data']['intent_id']);
    $entry = LedgerEntry::query()->where('kind', 'deposit_credit')->sole();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    try {
        DB::transaction(fn () => DB::table('ledger_entries')->where('id', $entry->id)->update(['kind' => $kind,
            'source_type' => 'primary_commitment', 'origin_operation_id' => strtolower((string) Str::ulid())]));
        $this->fail('Update was accepted.');
    } catch (QueryException $exception) {
        expect($exception->getCode())->toBe('23514');
    }
    expect(LedgerEntry::query()->where('source_type', 'primary_commitment')->count())->toBe(0);
})->with(['primary_hold', 'primary_commit', 'primary_release', 'primary_refund']);

it('DEFECT: the PostgreSqlConfigurationTest wallet round trip restores the commitment-source guard', function (): void {
    $guard = "SELECT count(*) AS total FROM pg_constraint WHERE conname = 'primary_commitment_source_unavailable'";
    expect(DB::selectOne($guard)->total)->toBe(1);
    $primary = require database_path('migrations/2026_09_28_143756_create_primary_reservation_records.php');
    $primaryCapacity = require database_path('migrations/2026_09_28_151253_enforce_primary_campaign_capacity_and_closure.php');
    $primaryCommands = require database_path('migrations/2026_09_28_152823_bind_primary_evidence_to_command_actors.php');
    $primaryOrdinals = require database_path('migrations/2026_09_28_154941_enforce_primary_ordinal_exclusion.php');
    $walletLedger = require database_path('migrations/2026_09_28_104818_create_investor_wallet_ledger_tables.php');
    $walletInputs = require database_path('migrations/2026_09_28_104819_create_wallet_deposit_policy_method_and_restriction_tables.php');
    $walletDeposits = require database_path('migrations/2026_09_28_104821_create_wallet_deposit_intent_and_outcome_tables.php');
    $ledgerSeal = require database_path('migrations/2026_09_28_112500_seal_ledger_entries_once_validated.php');
    $primaryPostings = require database_path('migrations/2026_09_28_112902_add_primary_postings_to_wallet_ledger.php');
    $postingAnchors = require database_path('migrations/2026_09_28_140000_bind_primary_postings_to_their_source_anchor.php');
    // Same order as tests/Feature/PostgreSqlConfigurationTest.php lines 85-104 and 180-189.
    $primaryOrdinals->down();
    $primaryCommands->down();
    $primaryCapacity->down();
    $primary->down();
    $postingAnchors->down();
    $primaryPostings->down();
    $ledgerSeal->down();
    $walletDeposits->down();
    $walletInputs->down();
    $walletLedger->down();
    $walletLedger->up();
    $walletInputs->up();
    $walletDeposits->up();
    $ledgerSeal->up();
    $primaryPostings->up();
    $postingAnchors->up();
    $primary->up();
    $primaryCapacity->up();
    $primaryCommands->up();
    $primaryOrdinals->up();

    expect(DB::selectOne($guard)->total)->toBe(1);
});
