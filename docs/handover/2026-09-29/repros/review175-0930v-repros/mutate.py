#!/usr/bin/env python3
"""Review #175 8d500fc7..d701d879 mutation runner: one exact-string mutation, run the PR's own tests, restore.
Usage: mutate.py <worktree> [ids...]  (runs only the PR's tests, never the Review175y repro files)."""
import os, re, subprocess, sys
W = sys.argv[1]
R = 'app/Infrastructure/Primary/EloquentPrimaryReservations.php'
S = 'app/Infrastructure/Business/EloquentBusinessCampaignStore.php'
G = 'database/migrations/2026_09_29_112938_create_primary_expiry_failures_table.php'
A = ['tests/Feature/PrimaryExpirySweepTest.php', 'tests/Concurrency/PrimaryReleaseConcurrencyTest.php']
B = ['tests/Feature/BusinessCampaignProgressTest.php', 'tests/Feature/BusinessCampaignTest.php', 'tests/Feature/BusinessCampaignClosureTransportTest.php']
MG = ['tests/Feature/BaselineInventoryTest.php', 'tests/Feature/PrimaryReservationSchemaTest.php', 'tests/Feature/PostgreSqlConfigurationTest.php'] + A
ORDER = "->orderByRaw('failures.last_attempted_at ASC NULLS FIRST')"
TRY_OPEN = "            try {\n                if (DB::transaction("
UPSERT = "DB::table('primary_expiry_failures')->upsert([['primary_reservation_id' => $candidate->id,\n                    'last_attempted_at' => now('UTC')->format('Y-m-d H:i:s.uP'), 'exception_class' => $exception::class]],\n                    ['primary_reservation_id'], ['last_attempted_at', 'exception_class']);"
LC = "$at->gte($payload['expires_at']) => 'closing_pending_settlement',"
SO = "BigInteger::of($summary['committed_principal'])->isEqualTo($payload['principal']) => 'sold_out_pending_settlement',"
FR = "$available->isZero() && BigInteger::of($summary['held_units'])->isPositive() => 'fully_reserved',"
IU = "$available->isZero() => 'inventory_unavailable',"
M = [
 ('A1 drop retry ordering (the P2-A1 fix)', R, ORDER, "", A),
 ('A2 failed roots first (NULLS LAST)', R, ORDER, "->orderByRaw('failures.last_attempted_at ASC NULLS LAST')", A),
 ('A3 newest failed attempt first', R, ORDER, "->orderByRaw('failures.last_attempted_at DESC NULLS FIRST')", A),
 ('A4 abort on first failure (no catch)', R, "            } catch (Throwable $exception) {", "            } catch (\\Error $exception) {", A),
 ('A5 swallow: never rethrow', R, "        if ($failure !== null) {\n            throw $failure;", "        if (false) {\n            throw $failure;", A),
 ('A6 rethrow the last failure, not the first', R, "$failure ??= $exception;", "$failure = $exception;", A),
 ('A7 do not record the failure', R, UPSERT, "", A),
 ('A8 record then stop the batch', R, "$failure ??= $exception;", "$failure ??= $exception;\n                break;", A),
 ('A9 upsert never refreshes last_attempted_at', R, "['primary_reservation_id'], ['last_attempted_at', 'exception_class']);", "['primary_reservation_id'], ['exception_class']);", A),
 ('A10 no error log', R, "Log::error('Primary reservation expiry failed.'", "Log::debug('Primary reservation expiry failed.'", A),
 ('A11 failure timestamp frozen at epoch', R, "'last_attempted_at' => now('UTC')->format('Y-m-d H:i:s.uP'), 'exception_class'", "'last_attempted_at' => '1970-01-01 00:00:00+00', 'exception_class'", A),
 ('A12 exception class not recorded', R, "'exception_class' => $exception::class]],", "'exception_class' => 'failed']],", A),
 ('B1 revert remaining (target - committed only)', S, "->minus($summary['committed_principal'])->minus($summary['held_principal']);", "->minus($summary['committed_principal']);", B),
 ('B2 unavailable always 0', S, "'unavailable' => (string) $unavailable]", "'unavailable' => '0']", B),
 ('B3 unavailable = occupied - committed', S, "->minus($summary['committed_units'])->minus($summary['held_units']);", "->minus($summary['committed_units']);", B),
 ('B4 lifecycle always live (the P2-B1 bug)', S, "'lifecycle' => $lifecycle, 'restriction'", "'lifecycle' => 'live', 'restriction'", B),
 ('B5 drop deadline state', S, LC, "", B),
 ('B6 deadline gte -> gt', S, "$at->gte($payload['expires_at'])", "$at->gt($payload['expires_at'])", B),
 ('B7 sold out outranks deadline', S, LC + "\n            " + SO, SO + "\n            " + LC, B),
 ('B8 fully_reserved ignores held>0', S, FR, "$available->isZero() => 'fully_reserved',", B),
 ('B9 inventory_unavailable before fully_reserved', S, FR + "\n            " + IU, IU + "\n            " + FR, B),
 ('B10 drop sold_out state', S, SO, "", B),
 ('B11 top-level lifecycle hardcoded live', S, "'lifecycle' => $closure['phase'] ?? $progress['lifecycle'],", "'lifecycle' => $closure['phase'] ?? 'live',", B),
 ('B12 drop unavailable negative guard', S, " || $unavailable->isNegative())", ")", B),
 ('B13 drop remaining negative guard', S, "if ($remaining->isNegative() || ", "if (", B),
 ('B14 sold_out by units (equivalent?)', S, "BigInteger::of($summary['committed_principal'])->isEqualTo($payload['principal'])", "BigInteger::of($summary['committed_units'])->isEqualTo($payload['quote']['units'])", B),
 ('B15 lifecycle instant +5min', S, "$at->gte($payload['expires_at'])", "$at->copy()->addMinutes(5)->gte($payload['expires_at'])", B),
 ('G1 no index on last_attempted_at', G, "$table->timestampTz('last_attempted_at', 6)->index();", "$table->timestampTz('last_attempted_at', 6);", MG),
 ('G2 cascade on delete', G, "->restrictOnDelete();", "->cascadeOnDelete();", MG),
 ('G3 no foreign key', G, "->primary()->constrained('primary_reservations')->restrictOnDelete();", "->primary();", MG),
 ('G4 no primary key', G, "$table->foreignUlid('primary_reservation_id')->primary()", "$table->foreignUlid('primary_reservation_id')", MG),
]
only = sys.argv[2:]
env = dict(os.environ, TMPDIR=os.path.join(W, '..', 'tmp'), DB_PORT='5541', PAO_DISABLE='1')
ansi = re.compile(r'\x1b\[[0-9;]*m')
for name, path, old, new, tests in M:
    if only and name.split()[0] not in only:
        continue
    fp = os.path.join(W, path); src = open(fp).read()
    if src.count(old) != 1:
        print((name, 'NOT-APPLIED (%d matches)' % src.count(old)), flush=True); continue
    open(fp, 'w').write(src.replace(old, new))
    try:
        p = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '-c', 'phpunit.pgsql.xml', '--no-tia', '--compact', *tests], cwd=W, env=env, capture_output=True, text=True, timeout=1200)
        out = ansi.sub('', p.stdout + p.stderr)
        summary = next((l.strip() for l in reversed(out.splitlines()) if l.strip().startswith('Tests:')), out.strip().splitlines()[-1] if out.strip() else '')
        failed = sorted({l.split('>')[-1].strip()[:80] for l in out.splitlines() if 'FAILED' in l})
        verdict = ('KILLED' if p.returncode else 'SURVIVED') + ' [' + summary + ']' + ((' by: ' + ' | '.join(failed[:3])) if failed else '')
    finally:
        open(fp, 'w').write(src)
    print((name, verdict), flush=True)
