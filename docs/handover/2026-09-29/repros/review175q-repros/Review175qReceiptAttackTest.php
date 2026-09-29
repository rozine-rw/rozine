<?php

declare(strict_types=1);

/*
 * Review probes for PR #175 at ea00d9a3 (K5 receipt identity guard, 2026_09_28_195022). Copy into tests/Feature/.
 * Each `it` asserts the SAFE behaviour unless prefixed "residual:" (documents what the guard deliberately does not bind)
 * or "control:" (behaviour that must keep working).
 */

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\CommandOperation;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = fn (array $investor, string $units = '3'): PrimaryReservationRecord => PrimaryReservationRecord::query()->findOrFail(
        $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...))['data']['reservation_id']);
    $this->confirm = function (array $investor, PrimaryReservationRecord $root, int $revision = 1): array {
        $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->where('revision', $revision)->sole();

        return $this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, $revision,
            $held->payload['terms']['disclosure_version'], $held->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    };
    /*
     * Raw purchase evidence for $root in the journal-after-evidence order the app uses: confirmed version,
     * commitment, then the primary.confirm receipt built by $receipt(ids). Only the receipt trigger is forced.
     */
    $this->forge = function (array $investor, PrimaryReservationRecord $root, Closure $receipt, bool $held = false): void {
        $previous = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
        $operationId = strtolower((string) Str::ulid());
        $commitmentId = strtolower((string) Str::ulid());
        $at = now('UTC');
        $revision = $previous->revision + 1;
        $state = $held ? 'held' : 'confirmed';
        $payload = [...$previous->payload, 'revision' => $revision, 'state' => $state, 'operation_id' => $operationId, 'previous_sha256' => $previous->sha256];
        (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $root->id, 'revision' => $revision, 'state' => $state,
            'operation_id' => $operationId, 'previous_sha256' => $previous->sha256, 'payload' => $payload,
            'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), 'created_at' => $at])->save();
        $version = PrimaryReservationVersion::query()->where('operation_id', $operationId)->sole();
        if (! $held) {
            (new PrimaryCommitment)->forceFill(['id' => $commitmentId, 'primary_reservation_id' => $root->id, 'primary_reservation_version_id' => $version->id,
                'operation_id' => $operationId, 'confirmed_at' => $at, 'created_at' => $at])->save();
        }
        $result = $receipt(['operation_id' => $operationId, 'commitment_id' => $commitmentId, 'reservation_id' => $root->id,
            'amount' => (string) $root->principal, 'revision' => $revision]);
        $operation = CommandOperation::factory()->make(['id' => $operationId, 'actor_key' => 'party:'.$root->party_id,
            'actor_user_id' => $investor['user']->id, 'command' => 'primary.confirm', 'target_type' => 'primary_reservation', 'target_id' => $root->id,
            'result' => is_string($result) ? [] : $result]);
        if (is_string($result)) {
            DB::table('command_operations')->insert([...$operation->getAttributes(), 'result' => $result, 'created_at' => now()]);
        } else {
            $operation->save();
        }
    };
    $this->exact = fn (array $ids): array => ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'operation_id' => $ids['operation_id'],
        'revision' => $ids['revision'], 'data' => ['reservation_id' => $ids['reservation_id'], 'commitment_id' => $ids['commitment_id'], 'amount' => $ids['amount']]];
    $this->receiptOnly = fn () => DB::statement('SET CONSTRAINTS primary_commitment_receipt_bound IMMEDIATE');
});

it('control: a real confirm passes the deferred receipt guard and every other deferred constraint', function (): void {
    $root = ($this->reserve)($this->investor);
    expect(($this->confirm)($this->investor, $root)['code'])->toBe('RESERVATION_CONFIRMED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(PrimaryCommitment::query()->count())->toBe(1);
});

it('control: install validates real confirmed history (requote then confirm at revision 3)', function (): void {
    $migration = require database_path('migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php');
    $migration->down();
    $root = ($this->reserve)($this->investor);
    $second = ($this->reserve)($this->investor, '2');
    $revised = function ($rights, array $campaign) {
        $terms = PrimaryReservationFixture::terms($rights, $campaign);

        return \App\Domain\Primary\PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion, 'synthetic-disclosure-2', $terms->earningsFee, $terms->payoutFee, $rights);
    };
    $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
    expect($this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $root->id, 1, $held->payload['terms']['disclosure_version'],
        $held->payload['disclosure_sha256'], (string) Str::uuid(), $revised)['code'])->toBe('RESERVATION_REQUOTED');
    $requoted = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->where('revision', 2)->sole();
    expect($this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $root->id, 2, $requoted->payload['terms']['disclosure_version'],
        $requoted->payload['disclosure_sha256'], (string) Str::uuid(), $revised)['code'])->toBe('RESERVATION_CONFIRMED')
        ->and(($this->confirm)($this->investor, $second)['code'])->toBe('RESERVATION_CONFIRMED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $migration->up();
    expect(PrimaryCommitment::query()->count())->toBe(2)->and(fn () => $migration->down())->toThrow(QueryException::class, 'forward migration');
});

it('refuses a receipt copied from another reservation of the same Party (only reservation_id differs)', function (): void {
    $target = ($this->reserve)($this->investor);
    $other = ($this->reserve)($this->investor);
    $real = ($this->confirm)($this->investor, $other);
    expect($real['code'])->toBe('RESERVATION_CONFIRMED')->and($target->principal)->toBe($other->principal);
    ($this->forge)($this->investor, $target, fn (array $ids): array => [...$real, 'operation_id' => $ids['operation_id'],
        'data' => [...$real['data'], 'commitment_id' => $ids['commitment_id']]]);
    expect($this->receiptOnly)->toThrow(QueryException::class, 'receipt must identify the retained purchase');
});

it('refuses the verbatim receipt of another reservation of the same Party', function (): void {
    $target = ($this->reserve)($this->investor);
    $other = ($this->reserve)($this->investor);
    $real = ($this->confirm)($this->investor, $other);
    ($this->forge)($this->investor, $target, fn (): array => $real);
    expect($this->receiptOnly)->toThrow(QueryException::class, 'receipt must identify the retained purchase');
});

it('refuses a receipt copied from another Party (only reservation_id differs)', function (): void {
    $target = ($this->reserve)($this->investor);
    $stranger = PrimaryReservationFixture::investor();
    $other = ($this->reserve)($stranger);
    $real = ($this->confirm)($stranger, $other);
    expect($real['code'])->toBe('RESERVATION_CONFIRMED');
    ($this->forge)($this->investor, $target, fn (array $ids): array => [...$real, 'operation_id' => $ids['operation_id'],
        'data' => [...$real['data'], 'commitment_id' => $ids['commitment_id']]]);
    expect($this->receiptOnly)->toThrow(QueryException::class, 'receipt must identify the retained purchase');
});

it('refuses a commitment bound to another reservation\'s real confirmation operation', function (): void {
    $target = ($this->reserve)($this->investor);
    $other = ($this->reserve)($this->investor);
    $real = ($this->confirm)($this->investor, $other);
    $previous = PrimaryReservationVersion::query()->where('primary_reservation_id', $target->id)->sole();
    $payload = [...$previous->payload, 'revision' => 2, 'state' => 'confirmed', 'operation_id' => $real['operation_id'], 'previous_sha256' => $previous->sha256];
    expect(fn () => DB::transaction(function () use ($target, $previous, $payload, $real): void {
        (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $target->id, 'revision' => 2, 'state' => 'confirmed',
            'operation_id' => $real['operation_id'], 'previous_sha256' => $previous->sha256, 'payload' => $payload,
            'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), 'created_at' => now('UTC')])->save();
    }))->toThrow(QueryException::class);
});

it('refuses receipt shape substitutions', function (Closure $mutate): void {
    $target = ($this->reserve)($this->investor);
    ($this->forge)($this->investor, $target, fn (array $ids): array => $mutate(($this->exact)($ids), $ids));
    expect($this->receiptOnly)->toThrow(QueryException::class, 'receipt must identify the retained purchase');
})->with([
    'status rejected' => [fn (array $r): array => [...$r, 'status' => 'rejected']],
    'code lower-case' => [fn (array $r): array => [...$r, 'code' => 'reservation_confirmed']],
    'code requoted' => [fn (array $r): array => [...$r, 'code' => 'RESERVATION_REQUOTED']],
    'operation_id missing' => [fn (array $r): array => array_diff_key($r, ['operation_id' => 1])],
    'operation_id upper-case' => [fn (array $r): array => [...$r, 'operation_id' => strtoupper($r['operation_id'])]],
    'revision missing' => [fn (array $r): array => array_diff_key($r, ['revision' => 1])],
    'revision numeric string' => [fn (array $r): array => [...$r, 'revision' => (string) $r['revision']]],
    'revision previous' => [fn (array $r): array => [...$r, 'revision' => $r['revision'] - 1]],
    'revision in array' => [fn (array $r): array => [...$r, 'revision' => [$r['revision']]]],
    'data missing' => [fn (array $r): array => array_diff_key($r, ['data' => 1])],
    'data wrapped in list' => [fn (array $r): array => [...$r, 'data' => [$r['data']]]],
    'amount missing' => [fn (array $r): array => [...$r, 'data' => array_diff_key($r['data'], ['amount' => 1])]],
    'amount integer' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'amount' => (int) $r['data']['amount']]]],
    'amount decimal string' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'amount' => $r['data']['amount'].'.00']]],
    'amount other units' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'amount' => '10000']]],
    'amount money object' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'amount' => ['currency' => 'RWF', 'amount' => $r['data']['amount']]]]],
    'commitment_id null' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'commitment_id' => null]]],
    'commitment_id missing' => [fn (array $r): array => [...$r, 'data' => array_diff_key($r['data'], ['commitment_id' => 1])]],
    'commitment_id in list' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'commitment_id' => [$r['data']['commitment_id']]]]],
    'commitment_id foreign' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'commitment_id' => strtolower((string) Str::ulid())]]],
    'reservation_id missing' => [fn (array $r): array => [...$r, 'data' => array_diff_key($r['data'], ['reservation_id' => 1])]],
    'fields moved to top level' => [fn (array $r): array => [...array_diff_key($r, ['data' => 1]), ...$r['data']]],
]);

it('control: accepts extra receipt keys the real confirm writes', function (): void {
    $target = ($this->reserve)($this->investor);
    ($this->forge)($this->investor, $target, fn (array $ids): array => [...($this->exact)($ids), 'extra' => true,
        'data' => [...($this->exact)($ids)['data'], 'entry_id' => 'x', 'terms' => ['a' => 1], 'expires_at' => 'y']]);
    ($this->receiptOnly)();
    expect(true)->toBeTrue();
});

it('residual: a float revision 2.0 is accepted as the integer revision', function (): void {
    $target = ($this->reserve)($this->investor);
    ($this->forge)($this->investor, $target, fn (array $ids): string => str_replace('"revision":'.$ids['revision'].',', '"revision":'.$ids['revision'].'.0,',
        json_encode(($this->exact)($ids), JSON_THROW_ON_ERROR)));
    ($this->receiptOnly)();
    expect(DB::selectOne("SELECT jsonb_typeof(result->'revision') AS t, result->>'revision' AS v FROM command_operations WHERE result->>'code' = 'RESERVATION_CONFIRMED'"))
        ->toMatchArray(['t' => 'number', 'v' => '2.0']);
});

it('refuses a receipt inserted after the commitment when the receipt trigger is forced early', function (): void {
    $target = ($this->reserve)($this->investor);
    $previous = PrimaryReservationVersion::query()->where('primary_reservation_id', $target->id)->sole();
    $operationId = strtolower((string) Str::ulid());
    $at = now('UTC');
    $payload = [...$previous->payload, 'revision' => 2, 'state' => 'confirmed', 'operation_id' => $operationId, 'previous_sha256' => $previous->sha256];
    (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $target->id, 'revision' => 2, 'state' => 'confirmed',
        'operation_id' => $operationId, 'previous_sha256' => $previous->sha256, 'payload' => $payload,
        'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), 'created_at' => $at])->save();
    $version = PrimaryReservationVersion::query()->where('operation_id', $operationId)->sole();
    (new PrimaryCommitment)->forceFill(['primary_reservation_id' => $target->id, 'primary_reservation_version_id' => $version->id,
        'operation_id' => $operationId, 'confirmed_at' => $at, 'created_at' => $at])->save();
    expect($this->receiptOnly)->toThrow(QueryException::class, 'receipt must identify the retained purchase');
});

it('refuses a receipt inserted after the commitment under an early SET CONSTRAINTS ALL IMMEDIATE', function (): void {
    $target = ($this->reserve)($this->investor);
    $previous = PrimaryReservationVersion::query()->where('primary_reservation_id', $target->id)->sole();
    $operationId = strtolower((string) Str::ulid());
    $at = now('UTC');
    $payload = [...$previous->payload, 'revision' => 2, 'state' => 'confirmed', 'operation_id' => $operationId, 'previous_sha256' => $previous->sha256];
    (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $target->id, 'revision' => 2, 'state' => 'confirmed',
        'operation_id' => $operationId, 'previous_sha256' => $previous->sha256, 'payload' => $payload,
        'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), 'created_at' => $at])->save();
    $version = PrimaryReservationVersion::query()->where('operation_id', $operationId)->sole();
    (new PrimaryCommitment)->forceFill(['primary_reservation_id' => $target->id, 'primary_reservation_version_id' => $version->id,
        'operation_id' => $operationId, 'confirmed_at' => $at, 'created_at' => $at])->save();
    expect(fn () => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'))->toThrow(QueryException::class);
});

it('keeps commitments and receipts immutable after the guard has passed', function (): void {
    $root = ($this->reserve)($this->investor);
    $real = ($this->confirm)($this->investor, $root);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $commitment = PrimaryCommitment::query()->sole();
    foreach ([
        fn () => DB::table('primary_commitments')->where('id', $commitment->id)->update(['operation_id' => strtolower((string) Str::ulid())]),
        fn () => DB::table('primary_commitments')->where('id', $commitment->id)->update(['id' => strtolower((string) Str::ulid())]),
        fn () => DB::table('primary_commitments')->where('id', $commitment->id)->delete(),
        fn () => DB::table('command_operations')->where('id', $real['operation_id'])->update(['result' => json_encode([...$real, 'code' => 'RESERVATION_REQUOTED'])]),
        fn () => DB::table('command_operations')->where('id', $real['operation_id'])->delete(),
        fn () => DB::table('primary_reservation_versions')->where('operation_id', $real['operation_id'])->update(['revision' => 9]),
    ] as $write) {
        expect(fn () => DB::transaction($write))->toThrow(QueryException::class, 'immutable');
    }
});

it('fires the receipt trigger for every committed INSERT (row level, deferred, enabled, no WHEN clause)', function (): void {
    $trigger = DB::selectOne("SELECT tgenabled, tgdeferrable, tginitdeferred, tgqual IS NULL AS unconditional, tgtype,
        pg_get_triggerdef(oid) AS def FROM pg_trigger WHERE tgname = 'primary_commitment_receipt_bound'");
    expect($trigger->tgenabled)->toBe('O')->and($trigger->tgdeferrable)->toBeTrue()->and($trigger->tginitdeferred)->toBeTrue()
        ->and($trigger->unconditional)->toBeTrue()
        ->and($trigger->def)->toContain('AFTER INSERT ON public.primary_commitments')->toContain('FOR EACH ROW');
});

/*
 * Mirror of K5 (not claimed by bff7a2d0): a requote (held) revision bound to a RESERVATION_CONFIRMED receipt naming a
 * commitment that does not exist. The journal would replay "confirmed" to the Investor while no purchase is retained.
 */
it('residual: a held requote revision can carry a RESERVATION_CONFIRMED receipt for a commitment that does not exist', function (): void {
    $target = ($this->reserve)($this->investor);
    ($this->forge)($this->investor, $target, $this->exact, held: true);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(PrimaryCommitment::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->sole()->result['code'])->toBe('RESERVATION_CONFIRMED');
});
