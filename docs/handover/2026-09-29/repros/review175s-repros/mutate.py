import subprocess, sys, os, json, time
ROOT = sys.argv[1]
F = os.path.join(ROOT, 'app/Infrastructure/Business/EloquentBusinessCampaignStore.php')
orig = open(F).read()
PR = ['tests/Feature/PrimaryCampaignClosureGateTest.php', 'tests/Feature/BusinessCampaignClosureTest.php', 'tests/Concurrency/PrimaryCampaignClosureConcurrencyTest.php']
OURS = ['tests/Feature/Review175sSweepTest.php', 'tests/Concurrency/Review175sConcurrencyTest.php']
catch_block = """                } catch (CommandRejection $exception) {
                    if ($exception->reason !== 'CAMPAIGN_SETTLEMENT_REQUIRED') {
                        throw $exception;
                    }"""
M = {
 'M1 swallow every CommandRejection': [("if ($exception->reason !== 'CAMPAIGN_SETTLEMENT_REQUIRED') {", "if (false) {")],
 'M2 catch Throwable and log': [(catch_block, """                } catch (\\Throwable $exception) {
                    if (false) {
                        throw $exception;
                    }"""), ("'code' => $exception->reason,", "'code' => 'CAMPAIGN_SETTLEMENT_REQUIRED',")],
 'M3 cursor never advances': [("                $cursor = $candidate;\n", "")],
 'M4 batch ignores closures so far': [("->limit($limit - $expired)", "->limit($limit)")],
 'M5 expires_at > becomes >=': [("where('expires_at', '>', $cursor->expires_at)", "where('expires_at', '>=', $cursor->expires_at)")],
 'M6 id > becomes >=': [("->where('id', '>', $cursor->id)", "->where('id', '>=', $cursor->id)")],
 'M7 drop same-expiry tie-break': [("""                $query->where(fn (Builder $query): Builder => $query->where('expires_at', '>', $cursor->expires_at)
                    ->orWhere(fn (Builder $query): Builder => $query->where('expires_at', $cursor->expires_at)->where('id', '>', $cursor->id)));""", """                $query->where('expires_at', '>', $cursor->expires_at);""")],
 'M8 single pass': [("while ($expired < $limit) {", "for ($pass = 0; $pass < 1 && $expired < $limit; $pass++) {")],
 'M9 no notice': [("                    Log::notice('Campaign expiry deferred until commitments are settled.', [\n                        'campaign_id' => $candidate->id, 'business_id' => $candidate->business_id, 'code' => $exception->reason,\n                    ]);\n", "")],
 'M10 notice without business_id': [("'business_id' => $candidate->business_id, 'code' => $exception->reason,", "'code' => $exception->reason,")],
 'M11 cutoff one second early': [("$cutoff = now();", "$cutoff = now()->subSecond();")],
 'M12 one transaction across the loop': [("        $expired = 0;\n        while", "        $expired = 0;\n        DB::beginTransaction();\n        while"), ("        return $expired;\n    }\n\n    /**\n     * Legacy closure", "        DB::commit();\n\n        return $expired;\n    }\n\n    /**\n     * Legacy closure")],
 'M13 deferral consumes the limit': [("""                    Log::notice('Campaign expiry deferred""", """                    $expired++;
                    Log::notice('Campaign expiry deferred""")],
 'M14 already-closed candidate counts': [("""                            return 0;
                        }
                        $this->close($campaign, 'expired'""", """                            return 1;
                        }
                        $this->close($campaign, 'expired'""")],
 'M15 no id ordering': [("->orderBy('expires_at')->orderBy('id')->limit", "->orderBy('expires_at')->limit")],
 'M16 rethrow only settlement': [("if ($exception->reason !== 'CAMPAIGN_SETTLEMENT_REQUIRED') {", "if ($exception->reason === 'CAMPAIGN_SETTLEMENT_REQUIRED') {")],
 'M17 break after first deferral': [("""                        'campaign_id' => $candidate->id, 'business_id' => $candidate->business_id, 'code' => $exception->reason,
                    ]);""", """                        'campaign_id' => $candidate->id, 'business_id' => $candidate->business_id, 'code' => $exception->reason,
                    ]);
                    break 2;""")],
}
env = dict(os.environ, DB_PORT='5499', DB_HOST='127.0.0.1')
def run(tests):
    try:
        p = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--compact', *tests], cwd=ROOT, env=env, capture_output=True, text=True, timeout=300)
        out = p.stdout + p.stderr
        for line in out.splitlines():
            if line.startswith('{"tool":"pest","result"'):
                d = json.loads(line)
                return d['result'], d.get('failed', 0), [f['test'].split('__pest_evaluable_')[-1][:70] for f in d.get('failures', [])][:4]
        return 'exit%d' % p.returncode, None, [out[-300:]]
    except subprocess.TimeoutExpired:
        return 'timeout', None, []
only = sys.argv[2:] 
for name, reps in M.items():
    if only and name.split()[0] not in only:
        continue
    src = orig
    for a, b in reps:
        assert a in src, (name, a[:60])
        src = src.replace(a, b, 1)
    open(F, 'w').write(src)
    try:
        pr = run(PR)
        ours = run(OURS)
    finally:
        open(F, 'w').write(orig)
    print(json.dumps({'mutation': name, 'pr': pr, 'ours': ours}), flush=True)
