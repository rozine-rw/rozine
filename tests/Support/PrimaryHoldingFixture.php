<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Primary\Contracts\HoldingSource;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\Disbursement;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Real retained Primary evidence for Holding schema tests: a published campaign bought through the
 * real checkout, its durable funding lock, and an issued closing reached through the database's own
 * disbursement rules. Holdings are inserted with raw SQL only; no application code issues them.
 */
final class PrimaryHoldingFixture
{
    /**
     * Every deferred Primary, wallet and funding check has already passed when this returns.
     *
     * @param  list<string>  $units  one real purchase per entry, in order
     * @param  list<int>  $requoted  indexes of purchases that are requoted once before confirming
     * @return array{campaign: BusinessCampaign, commitments: list<PrimaryCommitment>}
     */
    public static function committed(array $units = ['1080', '1080'], array $requoted = [], bool $fund = true): array
    {
        InvestorWalletFixture::policy(maximum: null);
        // A second campaign in one test would replay the sealing fixture's authenticator code inside Fortify's reuse window.
        Cache::flush();
        $campaign = PrimaryReservationFixture::campaign();
        $checkout = app(PrimaryCheckout::class);
        $commitments = [];
        foreach ($units as $index => $quantity) {
            $investor = PrimaryReservationFixture::investor();
            $held = $checkout->reserve($investor['user']->id, 1, $campaign->id, $quantity, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
            $root = PrimaryReservationRecord::query()->whereKey($held['data']['reservation_id'])->sole();
            $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
            [$revision, $disclosure, $sha256, $admit] = [1, $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], PrimaryReservationFixture::terms(...)];
            if (in_array($index, $requoted, true)) {
                $admit = self::revisedTerms(...);
                $requote = $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1, $disclosure, $sha256, (string) Str::uuid(), $admit);
                [$revision, $disclosure, $sha256] = [2, $requote['data']['terms']['disclosure_version'], $requote['data']['disclosure_sha256']];
            }
            $confirmed = $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, $revision, $disclosure, $sha256, (string) Str::uuid(), $admit);
            $commitments[] = PrimaryCommitment::query()->whereKey($confirmed['data']['commitment_id'])->sole();
        }
        if ($fund) {
            DB::transaction(fn (): array => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => self::admission($campaign)));
        }
        self::flushDeferredChecks();

        return ['campaign' => $campaign, 'commitments' => $commitments];
    }

    /**
     * Explicit synthetic admission, never live eligibility, policy, connection or destination evidence.
     *
     * @return array<string, mixed>
     */
    public static function admission(BusinessCampaign $campaign): array
    {
        return ['campaign_id' => $campaign->id, 'publication_sha256' => $campaign->sha256,
            ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'Isolated schema fixture, not live admission.']])];
    }

    /** @param array<string, mixed> $campaign */
    public static function revisedTerms(UnitRights $rights, array $campaign): PrimaryTerms
    {
        $terms = PrimaryReservationFixture::terms($rights, $campaign);

        return PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion, 'synthetic-disclosure-2', $terms->earningsFee, $terms->payoutFee, $rights);
    }

    /**
     * The campaign's disbursement taken to an issued closing by raw rows the schema itself accepts.
     *
     * @param  array<string, mixed>  $snapshot  overrides of the disbursement's funding snapshot
     */
    public static function issuedClosing(BusinessCampaign $campaign, array $snapshot = [], string $kind = 'issued'): string
    {
        return DB::transaction(fn (): string => self::close($campaign, $snapshot, $kind));
    }

    /** @param array<string, mixed> $snapshot */
    private static function close(BusinessCampaign $campaign, array $snapshot, string $kind): string
    {
        $disbursement = Disbursement::factory()->create([...['business_campaign_id' => $campaign->id, 'business_id' => $campaign->business_id,
            'exposure_reservation_id' => $campaign->exposure_reservation_id, 'amount' => $campaign->principal], ...$snapshot]);
        [$maker, $checker] = [User::factory()->create()->id, User::factory()->create()->id];
        $event = fn (int $revision, string $name, ?int $actor, ?string $operation = null): bool => DB::table('disbursement_events')->insert([
            'id' => self::id(), 'disbursement_id' => $disbursement->id, 'revision' => $revision, 'kind' => $name, 'actor_user_id' => $actor,
            'operation_id' => $actor === null ? null : ($operation ?? self::operation($actor)), 'request_id' => $actor === null ? null : (string) Str::uuid(),
            'binding_sha256' => $name === 'authorized' ? str_repeat('b', 64) : null, 'destination_sha256' => $name === 'authorized' ? str_repeat('d', 64) : null,
            'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()]);
        $event(1, 'authorized', $maker);
        if ($kind !== 'issued') {
            return self::closing($disbursement->id, $kind, ['cause' => 'approve_recheck', 'causes' => '["mandate"]', 'operation_id' => self::operation($checker)]);
        }
        [$intent, $operation] = [self::id(), self::operation($checker)];
        DB::table('disbursement_intents')->insert(['id' => $intent, 'disbursement_id' => $disbursement->id, 'operation_id' => $operation,
            'request_id' => (string) Str::uuid(), 'revision' => 1, 'amount' => $disbursement->amount, 'currency' => 'RWF', 'destination_id' => 'dest-1',
            'destination_revision' => 1, 'destination_sha256' => str_repeat('d', 64), 'commitments_digest' => $disbursement->commitments_digest,
            'binding_sha256' => str_repeat('b', 64), 'intent_digest' => str_repeat('i', 64), 'provider' => 'synthetic', 'provider_reference' => 'x',
            'provider_reference_sha256' => hash('sha256', $intent), 'environment' => 'testing', 'idempotent_sends' => false, 'maker_user_id' => $maker,
            'checker_user_id' => $checker, 'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()]);
        $event(2, 'intent_recorded', $checker, $operation);
        foreach (['queued', 'claimed', 'sent'] as $phase) {
            if ($phase === 'sent') {
                DB::table('disbursement_provider_calls')->insert(['id' => self::id(), 'intent_id' => $intent, 'kind' => 'send', 'source' => 'dispatch',
                    'operation_id' => null, 'created_at' => now()]);
            }
            DB::table('disbursement_dispatches')->insert(['id' => self::id(), 'intent_id' => $intent, 'phase' => $phase, 'created_at' => now()]);
        }
        $event(3, 'dispatched', null);
        [$observation, $reconciliation] = [self::id(), self::id()];
        DB::table('disbursement_provider_events')->insert(['id' => $observation, 'intent_id' => $intent, 'provider' => 'synthetic',
            'provider_event_id' => 'evt-'.$observation, 'content_sha256' => hash('sha256', $observation), 'source' => 'callback', 'state' => 'succeeded',
            'amount' => $disbursement->amount, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now(), 'observed_operation_id' => $operation,
            'provider_reference_sha256' => hash('sha256', $intent), 'destination_sha256' => str_repeat('d', 64), 'effective_at' => '2027-01-31T08:00:00Z',
            'disposition' => 'applied', 'mismatches' => '[]', 'evidence' => 'x', 'created_at' => now()]);
        DB::table('disbursement_reconciliations')->insert(['id' => $reconciliation, 'intent_id' => $intent, 'provider_event_id' => $observation,
            'decision' => 'matched_success', 'causes' => '[]', 'comparison' => '{}', 'created_at' => now()]);

        return self::closing($disbursement->id, 'issued', ['intent_id' => $intent, 'reconciliation_id' => $reconciliation, 'cause' => 'reconciled_success',
            'effective_at' => '2027-01-31T08:00:00Z', 'effective_date' => '2027-01-31', 'due_dates' => '["2027-02-28"]']);
    }

    /**
     * The Holding row the adapter would write for a commitment: `HoldingSource::facts` plus its closing.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(string $commitmentId, string $closingId, array $overrides = []): array
    {
        $facts = app(HoldingSource::class)->facts($commitmentId);

        return [...['id' => self::id(), ...$facts, 'ordinals' => json_encode($facts['ordinals'], JSON_THROW_ON_ERROR),
            'rights' => json_encode($facts['rights'], JSON_THROW_ON_ERROR), 'terms' => json_encode($facts['terms'], JSON_THROW_ON_ERROR),
            'disbursement_closing_id' => $closingId, 'schedule' => '[{"index":1}]', 'issued_at' => '2027-01-31T09:00:00Z',
            'disbursement_effective_at' => '2027-01-31T08:00:00Z', 'effective_date' => '2027-01-31', 'receipt_id' => self::id(),
            'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()], ...$overrides];
    }

    /** @param array<string, mixed> $overrides */
    public static function insert(string $commitmentId, string $closingId, array $overrides = []): string
    {
        $row = self::row($commitmentId, $closingId, $overrides);
        DB::table('primary_holdings')->insert($row);

        return $row['id'];
    }

    /** Runs every pending deferred check now, as a commit would, and leaves later writes deferred. */
    public static function flushDeferredChecks(): void
    {
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
    }

    public static function id(): string
    {
        return strtolower((string) Str::ulid());
    }

    /** A retained staff operation, so the deferred operation references hold at a real commit. */
    private static function operation(int $actor): string
    {
        return CommandOperation::factory()->create(['actor_key' => 'staff:'.$actor, 'actor_user_id' => $actor, 'command' => 'disbursement.fixture'])->id;
    }

    /** @param array<string, mixed> $facts */
    private static function closing(string $disbursementId, string $kind, array $facts): string
    {
        $id = self::id();
        DB::table('disbursement_closings')->insert([...['id' => $id, 'disbursement_id' => $disbursementId, 'intent_id' => null, 'reconciliation_id' => null,
            'kind' => $kind, 'causes' => '[]', 'operation_id' => null, 'effective_at' => null, 'effective_date' => null, 'due_dates' => null,
            'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()], ...$facts]);

        return $id;
    }
}
