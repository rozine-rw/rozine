#!/usr/bin/env python3
"""Review #175 mutation runner: apply one exact-string mutation, run the claimed tests, restore."""
import os, subprocess, sys, json
W = sys.argv[1]
R = 'app/Infrastructure/Primary/EloquentPrimaryReservations.php'
C = 'app/Console/Commands/ExpirePrimaryReservations.php'
S = 'app/Infrastructure/Business/EloquentBusinessCampaignStore.php'
Q = 'app/Infrastructure/Primary/EloquentCampaignReservationSummary.php'
RT = 'routes/console.php'
A_TESTS = ['tests/Feature/PrimaryExpirySweepTest.php', 'tests/Feature/BusinessCampaignProgressTest.php', 'tests/Concurrency/PrimaryReleaseConcurrencyTest.php']
B_TESTS = ['tests/Feature/BusinessCampaignProgressTest.php']
M = [
 ('A1 deadline <= -> <', R, "where('expires_at', '<=', $cutoff)", "where('expires_at', '<', $cutoff)", A_TESTS),
 ('A2 drop confirmed from terminal filter', R, "whereIn('state', ['confirmed', 'released', 'expired'])", "whereIn('state', ['released', 'expired'])", A_TESTS),
 ('A3 drop released from terminal filter', R, "whereIn('state', ['confirmed', 'released', 'expired'])", "whereIn('state', ['confirmed', 'expired'])", A_TESTS),
 ('A4 drop expired from terminal filter', R, "whereIn('state', ['confirmed', 'released', 'expired'])", "whereIn('state', ['confirmed', 'released'])", A_TESTS),
 ('A5 order by id only', R, "->orderBy('expires_at')->orderBy('id')->limit($limit)", "->orderBy('id')->limit($limit)", A_TESTS),
 ('A6 newest deadline first', R, "->orderBy('expires_at')->orderBy('id')->limit($limit)", "->orderByDesc('expires_at')->orderByDesc('id')->limit($limit)", A_TESTS),
 ('A7 no limit', R, "->orderBy('id')->limit($limit)->get()", "->orderBy('id')->get()", A_TESTS),
 ('A8 internal max 1000 -> 1001', R, "$limit > 1000", "$limit > 1001", A_TESTS),
 ('A9 internal max 1000 -> 999', R, "$limit > 1000", "$limit > 999", A_TESTS),
 ('A10 internal min < 1 -> < 0', R, "$limit < 1 ||", "$limit < 0 ||", A_TESTS),
 ('A11 count every candidate', R, "$candidate->id), 3) !== null", "$candidate->id), 3) !== false", A_TESTS),
 ('A12 swallow per-candidate failures', R, "            if (DB::transaction(fn (): ?ReservationRelease => $this->expire($candidate->business_campaign_id, $candidate->id), 3) !== null) {\n                $expired++;\n            }",
   "            try {\n                if (DB::transaction(fn (): ?ReservationRelease => $this->expire($candidate->business_campaign_id, $candidate->id), 3) !== null) {\n                    $expired++;\n                }\n            } catch (\\Throwable) {\n            }", A_TESTS),
 ('A13 one transaction for the batch', R, "        $expired = 0;\n        foreach ($candidates as $candidate) {\n            if (DB::transaction(fn (): ?ReservationRelease => $this->expire($candidate->business_campaign_id, $candidate->id), 3) !== null) {\n                $expired++;\n            }\n        }\n",
   "        $expired = 0;\n        DB::transaction(function () use ($candidates, &$expired): void {\n        foreach ($candidates as $candidate) {\n            if ($this->expire($candidate->business_campaign_id, $candidate->id) !== null) {\n                $expired++;\n            }\n        }\n        });\n", A_TESTS),
 ('A14 attempts 3 -> 1', R, "$candidate->id), 3) !== null", "$candidate->id), 1) !== null", A_TESTS),
 ('A15 command max 1000 -> 999', C, "'max_range' => 1000", "'max_range' => 999", A_TESTS),
 ('A16 command min 1 -> 2', C, "'min_range' => 1,", "'min_range' => 2,", A_TESTS),
 ('A17 drop withoutOverlapping', RT, "Schedule::command('primary:expire-reservations')->everyMinute()->withoutOverlapping(5);", "Schedule::command('primary:expire-reservations')->everyMinute();", A_TESTS),
 ('A18 default limit 100 -> 1000', C, "{--limit=100 :", "{--limit=1000 :", A_TESTS),
 ('A19 cutoff one minute early', R, "$cutoff = now('UTC')->format", "$cutoff = now('UTC')->subMinute()->format", A_TESTS),
 ('B1 funded_pct HalfUp', S, "->dividedBy($payload['principal'], 1, RoundingMode::Down)", "->dividedBy($payload['principal'], 1, RoundingMode::HalfUp)", B_TESTS),
 ('B2 funded_pct scale 2', S, "->dividedBy($payload['principal'], 1, RoundingMode::Down)", "->dividedBy($payload['principal'], 2, RoundingMode::Down)", B_TESTS),
 ('B3 drop remaining negative guard', S, "if ($remaining->isNegative() || $available->isNegative())", "if ($available->isNegative())", B_TESTS),
 ('B4 drop available negative guard', S, "if ($remaining->isNegative() || $available->isNegative())", "if ($remaining->isNegative())", B_TESTS),
 ('B5 recycle returned units', S, "->minus($summary['occupied_units'])", "->minus(BigInteger::of($summary['committed_units'])->plus($summary['held_units']))", B_TESTS),
 ('B6 remaining minus held too', S, "->minus($summary['committed_principal']);", "->minus($summary['committed_principal'])->minus($summary['held_principal']);", B_TESTS),
 ('B7 reserved includes overdue', S, "'amount' => $summary['held_principal']]", "'amount' => (string) BigInteger::of($summary['held_principal'])->plus($summary['expired_hold_principal'])]", B_TESTS),
 ('B8 phase funded at 100', S, "return ['phase' => 'raising', 'lifecycle' => 'live'", "return ['phase' => $summary['committed_principal'] === $payload['principal'] ? 'funded' : 'raising', 'lifecycle' => 'live'", B_TESTS),
 ('B9 summary instant +10min', S, "$this->reservations->read($campaignId, now('UTC')->toDateTimeImmutable())", "$this->reservations->read($campaignId, now('UTC')->addMinutes(10)->toDateTimeImmutable())", B_TESTS),
 ('B10 investors = count not distinct', Q, "COUNT(DISTINCT reservations.party_id) FILTER (WHERE versions.state = 'confirmed')", "COUNT(reservations.party_id) FILTER (WHERE versions.state = 'confirmed')", B_TESTS),
 ('B11 held boundary > -> >=', Q, "WHERE versions.state = 'held' AND reservations.expires_at > ?), 0)::text AS held_principal", "WHERE versions.state = 'held' AND reservations.expires_at >= ?), 0)::text AS held_principal", B_TESTS),
 ('B12 summary for any campaign', Q, "->where('reservations.business_campaign_id', $campaignId)", "", B_TESTS),
 ('B13 lifecycle fully_reserved when 0 available', S, "return ['phase' => 'raising', 'lifecycle' => 'live'", "return ['phase' => 'raising', 'lifecycle' => $available->isZero() ? 'fully_reserved' : 'live'", B_TESTS),
]
only = sys.argv[2:] 
env = dict(os.environ, TMPDIR=os.path.join(W, '..', 'tmp'), DB_PORT='5504')
results = []
for name, path, old, new, tests in M:
    if only and name.split()[0] not in only:
        continue
    fp = os.path.join(W, path); src = open(fp).read()
    if src.count(old) != 1:
        results.append((name, 'NOT-APPLIED (%d matches)' % src.count(old))); print(results[-1], flush=True); continue
    open(fp, 'w').write(src.replace(old, new))
    try:
        p = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '-c', 'phpunit.pgsql.xml', '--no-tia', '--compact', *tests], cwd=W, env=env, capture_output=True, text=True, timeout=900)
        out = (p.stdout + p.stderr).strip().splitlines()
        summary = next((l for l in reversed(out) if l.startswith('{"tool"')), out[-1] if out else '')
        try:
            j = json.loads(summary); verdict = ('KILLED' if p.returncode else 'SURVIVED') + ' (%s/%s passed)' % (j.get('passed'), j.get('tests'))
            if p.returncode:
                verdict += ' first=' + ' | '.join(sorted({(x.get('test','').split('::')[-1].replace('__pest_evaluable_it_','')[:70]) for x in (j.get('failures') or [])+(j.get('error_details') or [])}))
        except Exception:
            verdict = ('KILLED' if p.returncode else 'SURVIVED') + ' ' + summary[-200:]
    finally:
        open(fp, 'w').write(src)
    results.append((name, verdict)); print(results[-1], flush=True)
