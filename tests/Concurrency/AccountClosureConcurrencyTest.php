<?php

declare(strict_types=1);

use App\Infrastructure\Identity\EloquentAccountClosures;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Two accounts of one Party closing at the same time must not both see the other as still open and
 * skip ending the Party's roles (review of #755). Each close takes the Party row first, so they queue.
 */

/** @return array{party: string, first: User, second: User} */
function sharedPartyAccounts(): array
{
    $party = Party::factory()->create();
    $first = User::factory()->create(['party_id' => $party->id]);
    $second = User::factory()->create(['party_id' => $party->id]);
    DB::table('role_memberships')->insert(['id' => strtolower((string) Str::ulid()), 'party_id' => $party->id,
        'role' => 'auditor', 'status' => 'active', 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);

    return ['party' => $party->id, 'first' => $first, 'second' => $second];
}

it('waits for the Party lock before deciding whether this is the last open account', function (): void {
    ['party' => $party, 'second' => $second] = sharedPartyAccounts();
    config(['database.connections.closure_contender' => config('database.connections.pgsql')]);
    $contender = DB::connection('closure_contender');
    DB::statement("SET lock_timeout = '500ms'");
    try {
        $contender->beginTransaction();
        $contender->table('parties')->where('id', $party)->lockForUpdate()->first();

        expect(fn () => app(EloquentAccountClosures::class)->close($second->id, null))->toThrow(QueryException::class, '55P03');
        expect(User::query()->whereKey($second->id)->value('closed_at'))->toBeNull();
    } finally {
        if ($contender->transactionLevel() > 0) {
            $contender->rollBack();
        }
        DB::purge('closure_contender');
        DB::statement('SET lock_timeout = 0');
    }

    expect(app(EloquentAccountClosures::class)->close($second->id, null))->toBe([]);
});

it('ends the Party roles when both accounts close one after the other in either order', function (bool $firstFirst): void {
    ['party' => $party, 'first' => $first, 'second' => $second] = sharedPartyAccounts();
    [$a, $b] = $firstFirst ? [$first, $second] : [$second, $first];

    app(EloquentAccountClosures::class)->close($a->id, null);
    expect(DB::table('role_memberships')->where('party_id', $party)->value('status'))->toBe('active');
    app(EloquentAccountClosures::class)->close($b->id, null);

    expect(DB::table('role_memberships')->where('party_id', $party)->value('status'))->toBe('revoked');
})->with(['first then second' => true, 'second then first' => false]);
