<?php

declare(strict_types=1);

/*
 * Review probes for PR #175 at f1e99b16 (reverse K5 binding, 2026_09_28_212446). Copy into tests/Feature/.
 * Each `it` asserts the SAFE behaviour unless prefixed "residual:" (documents what 212446 does not bind)
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
    /*
     * Raw purchase evidence for $root. $journalFirst inserts the receipt before the version/commitment.
     * $receipt(ids) builds the result (array, or raw JSON string); $operation overrides journal columns.
     */
    $this->forge = function (PrimaryReservationRecord $root, Closure $receipt, bool $held = false, array $operation = [], bool $journalFirst = false, ?Closure $between = null): array {
        $previous = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
        $operationId = strtolower((string) Str::ulid());
        $commitmentId = strtolower((string) Str::ulid());
        $at = now('UTC');
        $revision = $previous->revision + 1;
        $state = $held ? 'held' : 'confirmed';
        $ids = ['operation_id' => $operationId, 'commitment_id' => $commitmentId, 'reservation_id' => $root->id,
            'amount' => (string) $root->principal, 'revision' => $revision];
        $journal = function () use ($root, $receipt, $ids, $operation): void {
            $result = $receipt($ids);
            $row = CommandOperation::factory()->make([...['id' => $ids['operation_id'], 'actor_key' => 'party:'.$root->party_id,
                'actor_user_id' => $this->investor['user']->id, 'command' => 'primary.confirm', 'target_type' => 'primary_reservation',
                'target_id' => $root->id], ...$operation, 'result' => is_string($result) ? [] : $result]);
            if (is_string($result)) {
                DB::table('command_operations')->insert([...$row->getAttributes(), 'result' => $result, 'created_at' => now()]);
            } else {
                $row->save();
            }
        };
        if ($journalFirst) {
            $journal();
            if ($between !== null) {
                $between();
            }
        }
        $payload = [...$previous->payload, 'revision' => $revision, 'state' => $state, 'operation_id' => $operationId, 'previous_sha256' => $previous->sha256];
        (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $root->id, 'revision' => $revision, 'state' => $state,
            'operation_id' => $operationId, 'previous_sha256' => $previous->sha256, 'payload' => $payload,
            'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), 'created_at' => $at])->save();
        $version = PrimaryReservationVersion::query()->where('operation_id', $operationId)->sole();
        if (! $held) {
            (new PrimaryCommitment)->forceFill(['id' => $commitmentId, 'primary_reservation_id' => $root->id, 'primary_reservation_version_id' => $version->id,
                'operation_id' => $operationId, 'confirmed_at' => $at, 'created_at' => $at])->save();
        }
        if (! $journalFirst) {
            $journal();
        }

        return $ids;
    };
    $this->exact = fn (array $ids): array => ['status' => 'completed', 'code' => 'RESERVATION_CONFIRMED', 'operation_id' => $ids['operation_id'],
        'revision' => $ids['revision'], 'data' => ['reservation_id' => $ids['reservation_id'], 'commitment_id' => $ids['commitment_id'], 'amount' => $ids['amount']]];
    $this->operationOnly = fn () => DB::statement('SET CONSTRAINTS primary_confirmation_operation_bound IMMEDIATE');
    /* Forged confirmations carry no cash movement, so "purchase" controls force only the receipt, operation and actor guards. */
    $this->bindings = fn () => DB::statement('SET CONSTRAINTS primary_confirmation_operation_bound, primary_commitment_receipt_bound, primary_version_command_bound IMMEDIATE');
});

it('control: the exact forged purchase passes 212446 and every other deferred constraint in both insertion orders', function (bool $journalFirst): void {
    $target = ($this->reserve)($this->investor);
    ($this->forge)($target, $this->exact, journalFirst: $journalFirst);
    ($this->bindings)();
    expect(PrimaryCommitment::query()->count())->toBe(1);
})->with(['evidence first' => false, 'journal first' => true]);

it('control: a real confirm, a real requote and a real release commit under 212446', function (): void {
    $root = ($this->reserve)($this->investor);
    $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
    $revised = function ($rights, array $campaign) {
        $terms = PrimaryReservationFixture::terms($rights, $campaign);

        return \App\Domain\Primary\PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion, 'synthetic-disclosure-2', $terms->earningsFee, $terms->payoutFee, $rights);
    };
    expect($this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $root->id, 1, $held->payload['terms']['disclosure_version'],
        $held->payload['disclosure_sha256'], (string) Str::uuid(), $revised)['code'])->toBe('RESERVATION_REQUOTED');
    $requoted = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->where('revision', 2)->sole();
    expect($this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $root->id, 2, $requoted->payload['terms']['disclosure_version'],
        $requoted->payload['disclosure_sha256'], (string) Str::uuid(), $revised)['code'])->toBe('RESERVATION_CONFIRMED');
    $other = ($this->reserve)($this->investor);
    expect($this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $other->id, 1, (string) Str::uuid())['code'])->toBe('RESERVATION_RELEASED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(PrimaryCommitment::query()->count())->toBe(1);
});

it('declares a deferred, enabled, row-level AFTER INSERT trigger keyed only on the confirmation code', function (): void {
    $trigger = DB::selectOne("SELECT tgenabled, tgdeferrable, tginitdeferred, pg_get_triggerdef(oid) AS def FROM pg_trigger
        WHERE tgname = 'primary_confirmation_operation_bound'");
    expect($trigger->tgenabled)->toBe('O')->and($trigger->tgdeferrable)->toBeTrue()->and($trigger->tginitdeferred)->toBeTrue()
        ->and($trigger->def)->toContain('AFTER INSERT ON public.command_operations')->toContain('FOR EACH ROW')
        ->toContain("WHEN (((new.result ->> 'code'::text) = 'RESERVATION_CONFIRMED'::text))")
        ->and((int) DB::selectOne("SELECT count(*) AS n FROM pg_trigger WHERE tgname = 'command_operations_immutable' AND tgenabled = 'O'")->n)->toBe(1);
});

it('refuses RESERVATION_CONFIRMED under any command other than primary.confirm, even over a real purchase', function (string $command): void {
    $target = ($this->reserve)($this->investor);
    ($this->forge)($target, $this->exact, operation: ['command' => $command]);
    expect($this->operationOnly)->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
})->with(['primary.reserve', 'primary.release', 'wallet.deposit', 'fixture.save', 'primary.confirm.v2']);

it('refuses a receipt bound to another Party\'s purchase (actor substitution) at commit', function (): void {
    $target = ($this->reserve)($this->investor);
    $stranger = PrimaryReservationFixture::investor();
    ($this->forge)($target, $this->exact, operation: ['actor_key' => 'party:'.$stranger['party']->id, 'actor_user_id' => $stranger['user']->id]);
    expect($this->bindings)->toThrow(QueryException::class, 'Party command and target binding');
});

it('residual: 212446 on its own does not bind the journal actor to the reservation Party (152823 does)', function (): void {
    $target = ($this->reserve)($this->investor);
    $stranger = PrimaryReservationFixture::investor();
    ($this->forge)($target, $this->exact, operation: ['actor_key' => 'party:'.$stranger['party']->id, 'actor_user_id' => $stranger['user']->id]);
    ($this->operationOnly)();
    expect($this->bindings)->toThrow(QueryException::class, 'Party command and target binding');
});

it('refuses principal, revision and identity substitutions in the receipt (212446 alone)', function (Closure $mutate): void {
    $target = ($this->reserve)($this->investor);
    ($this->forge)($target, fn (array $ids) => $mutate(($this->exact)($ids), $ids));
    expect($this->operationOnly)->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
})->with([
    'amount integer of the same value' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'amount' => (int) $r['data']['amount']]]],
    'amount float of the same value' => [fn (array $r, array $ids): string => str_replace('"amount":"'.$ids['amount'].'"', '"amount":'.$ids['amount'].'.0', json_encode($r, JSON_THROW_ON_ERROR))],
    'amount extra precision' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'amount' => $r['data']['amount'].'.0']]],
    'amount leading zero' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'amount' => '0'.$r['data']['amount']]]],
    'amount padded' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'amount' => ' '.$r['data']['amount']]]],
    'amount exponent' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'amount' => '1.5e4']]],
    'revision plus one' => [fn (array $r): array => [...$r, 'revision' => $r['revision'] + 1]],
    'revision minus one' => [fn (array $r): array => [...$r, 'revision' => $r['revision'] - 1]],
    'revision string' => [fn (array $r): array => [...$r, 'revision' => (string) $r['revision']]],
    'revision null' => [fn (array $r): array => [...$r, 'revision' => null]],
    'operation_id of another op' => [fn (array $r): array => [...$r, 'operation_id' => strtolower((string) Str::ulid())]],
    'commitment_id upper-case' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'commitment_id' => strtoupper($r['data']['commitment_id'])]]],
    'reservation_id upper-case' => [fn (array $r): array => [...$r, 'data' => [...$r['data'], 'reservation_id' => strtoupper($r['data']['reservation_id'])]]],
    'status missing' => [fn (array $r): array => array_diff_key($r, ['status' => 1])],
]);

it('refuses a receipt whose commitment belongs to another reservation of the same Party', function (): void {
    $target = ($this->reserve)($this->investor);
    $other = ($this->reserve)($this->investor);
    ($this->forge)($other, fn (array $ids): array => [...($this->exact)($ids), 'data' => [...($this->exact)($ids)['data'], 'reservation_id' => $target->id]],
        operation: ['target_id' => $target->id]);
    expect($this->operationOnly)->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
});

it('cannot bind one operation to two reservations (unique operation_id on versions and commitments)', function (): void {
    $target = ($this->reserve)($this->investor);
    $other = ($this->reserve)($this->investor);
    $ids = ($this->forge)($target, $this->exact);
    $previous = PrimaryReservationVersion::query()->where('primary_reservation_id', $other->id)->sole();
    $payload = [...$previous->payload, 'revision' => 2, 'state' => 'confirmed', 'operation_id' => $ids['operation_id'], 'previous_sha256' => $previous->sha256];
    expect(fn () => DB::transaction(fn () => (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $other->id, 'revision' => 2,
        'state' => 'confirmed', 'operation_id' => $ids['operation_id'], 'previous_sha256' => $previous->sha256, 'payload' => $payload,
        'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), 'created_at' => now('UTC')])->save()))
        ->toThrow(QueryException::class, 'unique');
});

it('refuses a held requote carrying RESERVATION_CONFIRMED in both insertion orders, including under ALL IMMEDIATE', function (bool $journalFirst): void {
    $target = ($this->reserve)($this->investor);
    expect(fn () => DB::transaction(function () use ($target, $journalFirst): void {
        ($this->forge)($target, $this->exact, held: true, journalFirst: $journalFirst);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
    expect(CommandOperation::query()->where('command', 'primary.confirm')->count())->toBe(0);
})->with(['evidence first' => false, 'journal first' => true]);

it('journal first: forcing ALL IMMEDIATE before the evidence exists refuses (the guard is only satisfiable at commit)', function (): void {
    $target = ($this->reserve)($this->investor);
    expect(fn () => DB::transaction(fn () => ($this->forge)($target, $this->exact, journalFirst: true,
        between: fn () => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'))))->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
});

it('journal first: forcing only 212446 IMMEDIATE and then writing the evidence is refused at the receipt, not retroactively accepted', function (): void {
    $target = ($this->reserve)($this->investor);
    expect(fn () => DB::transaction(fn () => ($this->forge)($target, $this->exact, journalFirst: true,
        between: $this->operationOnly)))->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase');
});

it('residual: primary.confirm receipts that do not use the exact code are not checked by 212446 (held requote naming a phantom commitment)', function (string $code): void {
    $target = ($this->reserve)($this->investor);
    ($this->forge)($target, fn (array $ids): array => [...($this->exact)($ids), 'code' => $code], held: true);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect(PrimaryCommitment::query()->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->sole()->result['data']['commitment_id'])->not->toBeNull();
})->with(['RESERVATION_REQUOTED', 'reservation_confirmed', 'RESERVATION_CONFIRMED ']);

it('residual: a float revision 2.0 still satisfies 212446', function (): void {
    $target = ($this->reserve)($this->investor);
    ($this->forge)($target, fn (array $ids): string => str_replace('"revision":'.$ids['revision'].',', '"revision":'.$ids['revision'].'.0,',
        json_encode(($this->exact)($ids), JSON_THROW_ON_ERROR)));
    ($this->bindings)();
    expect(DB::selectOne("SELECT result->>'revision' AS v FROM command_operations WHERE result->>'code' = 'RESERVATION_CONFIRMED'")->v)->toBe('2.0');
});

it('install audit aborts on a retained held-requote CONFIRMED receipt and leaves no function, trigger or rewrite behind', function (): void {
    $migration = require database_path('migrations/2026_09_28_212446_bind_primary_confirmation_operations_to_purchases.php');
    $migration->down();
    $target = ($this->reserve)($this->investor);
    ($this->forge)($target, $this->exact, held: true);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $before = DB::table('command_operations')->orderBy('id')->get()->toJson();
    expect(fn () => $migration->up())->toThrow(QueryException::class, 'Confirmation operation requires its retained purchase')
        ->and(DB::selectOne("SELECT count(*) AS n FROM pg_trigger WHERE tgname = 'primary_confirmation_operation_bound'")->n)->toBe(0)
        ->and(DB::selectOne("SELECT to_regprocedure('require_primary_confirmation_operation()') AS f")->f)->toBeNull()
        ->and(DB::table('command_operations')->orderBy('id')->get()->toJson())->toBe($before);
});
