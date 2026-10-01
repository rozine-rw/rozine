<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\Party;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositIntent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/** @extends Factory<PrimaryReservationRecord> */
class PrimaryReservationRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_campaign_id' => BusinessCampaign::factory(),
            'publication_sha256' => fn (array $a): string => BusinessCampaign::query()->whereKey($a['business_campaign_id'])->firstOrFail()->sha256,
            'party_id' => Party::factory(),
            'origin_operation_id' => fn (array $a): string => CommandOperation::factory()->create([
                'actor_key' => 'party:'.$a['party_id'], 'actor_user_id' => User::factory()->create(['party_id' => $a['party_id']])->id,
                'command' => 'primary.reserve', 'target_type' => 'campaign', 'target_id' => $a['business_campaign_id'],
            ])->id,
            'units' => 1, 'principal' => '5000',
            'ordinal_ranges' => function (array $a): string {
                $first = (int) DB::table('primary_reservations')->where('business_campaign_id', $a['business_campaign_id'])
                    ->selectRaw('COALESCE(MAX(upper(ordinal_ranges)), 1) AS first')->value('first');

                return '{['.$first.','.($first + max(1, (int) $a['units'])).')}';
            },
            'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $a): string => hash('sha256', app(CanonicalJson::class)->encode($a['payload'])),
            'created_at' => fn () => now()->startOfSecond(),
            'expires_at' => fn (array $a) => min(CarbonImmutable::parse($a['created_at'])->addSeconds(300), BusinessCampaign::query()->whereKey($a['business_campaign_id'])->firstOrFail()->expires_at)];
    }

    public function withInitialVersion(bool $withHold = true): static
    {
        return $this->afterCreating(function (PrimaryReservationRecord $reservation) use ($withHold): void {
            PrimaryReservationVersion::factory()->create(['primary_reservation_id' => $reservation->id, 'revision' => 1,
                'operation_id' => $reservation->origin_operation_id, 'previous_sha256' => null, 'created_at' => $reservation->created_at]);
            if ($withHold) {
                $this->retainSyntheticHold($reservation);
            }
        });
    }

    /** Synthetic schema evidence only; the real reserve integration uses a settled deposit. */
    private function retainSyntheticHold(PrimaryReservationRecord $reservation): void
    {
        $postings = app(WalletPostings::class);
        $wallet = $postings->lockForParty($reservation->party_id);
        $available = LedgerAccount::query()->where('wallet_id', $wallet->walletId)->where('kind', 'investor_available')->first()
            ?? LedgerAccount::factory()->create(['wallet_id' => $wallet->walletId]);
        $clearing = LedgerAccount::query()->where('kind', 'deposit_clearing')->first()
            ?? LedgerAccount::factory()->system()->create();
        $deposit = LedgerEntry::query()->findOrFail(WalletDepositCredit::factory()->create(['intent_id' => WalletDepositIntent::factory()
            ->state(['wallet_id' => $wallet->walletId, 'amount' => $reservation->principal, 'credited' => $reservation->principal])])->ledger_entry_id);
        LedgerLine::factory()->create(['entry_id' => $deposit->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => $reservation->principal]);
        LedgerLine::factory()->create(['entry_id' => $deposit->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => $reservation->principal]);
        $postings->hold($wallet, WalletMoney::of($reservation->principal), new PostingSource('primary_reservation', $reservation->id, $reservation->origin_operation_id));
    }
}
