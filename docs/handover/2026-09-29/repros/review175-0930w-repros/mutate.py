#!/usr/bin/env python3
"""Review #175 d701d879..33ba9040 mutation runner: one exact-string mutation, run a test set, restore.
Usage: mutate.py <worktree> <pr|repro> [ids...]"""
import os, re, subprocess, sys
W = sys.argv[1]; SET = sys.argv[2]
R = 'app/Infrastructure/Primary/EloquentPrimaryReservations.php'
S = 'app/Infrastructure/Business/EloquentPrimaryCampaignSource.php'
D = 'app/Domain/Primary/PrimaryReservation.php'
N = 'app/Domain/Primary/ReservationWindow.php'
PR = ['tests/Feature/PrimaryFundingCandidateTest.php', 'tests/Feature/PrimaryCampaignSourceTest.php', 'tests/Concurrency/PrimaryFundingCandidateConcurrencyTest.php']
RP = ['tests/Feature/Review175zTest.php', 'tests/Concurrency/Review175zConcurrencyTest.php']
READ = "$campaign = $this->campaigns->lockForFunding($campaignId);"
FUND = "return $this->lockedInput($campaignId, true, allowElapsed: true);"
RET = "            return new PrimaryFundingCandidate($campaign['id']"
M = [
 ('M1 reader uses lock() (revert)', R, READ, "$campaign = $this->campaigns->lock($campaignId);"),
 ('M2 reader uses lockRetained()', R, READ, "$campaign = $this->campaigns->lockRetained($campaignId);"),
 ('M3 lockForFunding refuses elapsed', S, FUND, "return $this->lockedInput($campaignId, true);"),
 ('M4 lockForFunding skips open checks', S, FUND, "return $this->lockedInput($campaignId, false);"),
 ('M5 allowElapsed defaults true (lock() too)', S, "bool $allowElapsed = false", "bool $allowElapsed = true"),
 ('M6 drop closure refusal', S, "($closure !== null || now()->lt", "(now()->lt"),
 ('M7 drop live_at refusal', S, "now()->lt($campaign->live_at) || ", ""),
 ('M8 restore post-cash deadline recheck', R, RET, "            if (now('UTC')->gte($campaign['expires_at'])) {\n                throw new CommandRejection('CAMPAIGN_CLOSED');\n            }\n\n" + RET),
 ('M9 domain confirm skips hold window', D, "        $this->window->requireOpen($at);\n        if ($this->state !== 'held') {\n            throw new PrimaryViolation('RESERVATION_NOT_HELD');\n        }\n        $this->terms->requireAcknowledged", "        if ($this->state !== 'held') {\n            throw new PrimaryViolation('RESERVATION_NOT_HELD');\n        }\n        $this->terms->requireAcknowledged"),
 ('M10 window expiry >= -> >', N, "return $at >= $this->expiresAt;", "return $at > $this->expiresAt;"),
]
only = sys.argv[3:]
env = dict(os.environ, TMPDIR=os.path.join(W, '..', '..', 'review175w', 'tmp'), DB_HOST='127.0.0.1', DB_PORT='5544', PAO_DISABLE='1')
ansi = re.compile(r'\x1b\[[0-9;]*m')
DOM = ['tests/Unit/PrimaryReservationTest.php', 'tests/Unit/PrimaryReservationWindowTest.php', 'tests/Feature/PrimaryConfirmationTest.php', 'tests/Feature/PrimaryReleaseTest.php']
tests = {'pr': PR, 'repro': RP, 'dom': DOM}[SET]
for name, path, old, new in M:
    if only and name.split()[0] not in only:
        continue
    fp = os.path.join(W, path); src = open(fp).read()
    if src.count(old) != 1:
        print((name, 'NOT-APPLIED (%d matches)' % src.count(old)), flush=True); continue
    open(fp, 'w').write(src.replace(old, new))
    try:
        p = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '-c', 'phpunit.pgsql.xml', '--no-tia', '--compact', *tests], cwd=W, env=env, capture_output=True, text=True, timeout=2400)
        out = ansi.sub('', p.stdout + p.stderr)
        summary = next((l.strip() for l in reversed(out.splitlines()) if l.strip().startswith('Tests:')), out.strip().splitlines()[-1] if out.strip() else '')
        failed = sorted({l.split('>')[-1].strip()[:90] for l in out.splitlines() if 'FAILED' in l})
        verdict = ('KILLED' if p.returncode else 'SURVIVED') + ' [' + summary + ']' + ((' by: ' + ' | '.join(failed[:4])) if failed else '')
    finally:
        open(fp, 'w').write(src)
    print((name, verdict), flush=True)
