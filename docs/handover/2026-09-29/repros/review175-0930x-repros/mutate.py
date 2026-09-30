#!/usr/bin/env python3
"""Review #175 33ba9040..3bb74427 mutation runner: one exact-string mutation, run a test set with --bail, restore.
Usage: mutate.py <worktree> <scratch> <pr|repro> [ids...]"""
import os, re, subprocess, sys
W = sys.argv[1]; X = sys.argv[2]; SET = sys.argv[3]
F = 'app/Infrastructure/Primary/EloquentPrimaryFunding.php'
A = 'app/Domain/Primary/FundingAdmission.php'
R = 'app/Infrastructure/Primary/RetainedPrimaryFunding.php'
B = 'app/Infrastructure/Business/EloquentBusinessCampaignStore.php'
S = 'app/Infrastructure/Business/EloquentPrimaryCampaignSource.php'
G = 'database/migrations/2026_09_30_054318_create_primary_campaign_fundings.php'
PR = ['tests/Feature/PrimaryCampaignFundingTest.php', 'tests/Unit/FundingAdmissionTest.php', 'tests/Concurrency/PrimaryFundingLockConcurrencyTest.php']
RP = ['tests/Feature/Review175aaTest.php']
M = [
 ('M01 lock() drops the caller-transaction refusal', F, "if (DB::transactionLevel() === 0) {", "if (false) {"),
 ('M02 lock() skips lockBusiness before admission', F, "            $this->campaigns->lockBusiness($campaignId);\n            $admission = $admit($campaignId);", "            $admission = $admit($campaignId);"),
 ('M03 admission runs after the campaign lock', F, "            $admission = $admit($campaignId);\n            if ($admission === []) {\n                throw new CommandRejection('POLICY_INPUT_REQUIRED');\n            }\n            $campaign = $this->campaigns->lockForFunding($campaignId);", "            $campaign = $this->campaigns->lockForFunding($campaignId);\n            $admission = $admit($campaignId);\n            if ($admission === []) {\n                throw new CommandRejection('POLICY_INPUT_REQUIRED');\n            }"),
 ('M04 empty admission not refused up front', F, "if ($admission === []) {", "if (false) {"),
 ('M05 admission refusal ignored', F, "if ($refusal !== null) {", "if (false) {"),
 ('M06 retry skips the retained-set comparison', F, "if ($this->json->encode(['commitments' => $retained['commitments']]) !== $this->json->encode(['commitments' => $purchases])) {", "if (false) {"),
 ('M07 retry skips the candidate recheck (find before lockFundingCandidate)', F, "            $candidate = $this->reservations->lockFundingCandidate($campaignId);\n            $purchases = $this->purchases($candidate);\n            $retained = $this->fundings->find($campaignId);\n            if ($retained !== null) {", "            $retained = $this->fundings->find($campaignId);\n            if ($retained !== null) {\n                return $retained;\n            }\n            $candidate = $this->reservations->lockFundingCandidate($campaignId);\n            $purchases = $this->purchases($candidate);\n            if ($retained !== null) {"),
 ('M08 admission: campaign binding not checked', A, "($admission['campaign_id'] ?? null) !== $campaignId || ", ""),
 ('M09 admission: publication binding not checked', A, " || ($admission['publication_sha256'] ?? null) !== $publicationSha256", ""),
 ('M10 admission: empty evidence accepted', A, " || $check['evidence'] === []", ""),
 ('M11 admission: non-array evidence accepted', A, " || ! is_array($check['evidence'] ?? null) || $check['evidence'] === []", ""),
 ('M12 admission: any non-failed status passes', A, "if (($check['status'] ?? null) !== 'passed') {", "if (false) {"),
 ('M13 admission: only the first check is validated', A, "foreach (['eligibility', 'policy', 'connections', 'destination'] as $name) {", "foreach (['eligibility'] as $name) {"),
 ('M14 admission: destination not required', A, "['eligibility', 'policy', 'connections', 'destination'] as $name", "['eligibility', 'policy', 'connections'] as $name"),
 ('M15 find(): digest not verified', R, "if (! hash_equals($funding->sha256, hash('sha256', $this->json->encode($payload)))\n            || ", "if ("),
 ('M16 find(): retained admission not revalidated', R, "\n            || FundingAdmission::rejectionReason($payload['admission'], $campaignId, $funding->publication_sha256) !== null", ""),
 ('M17 find(): membership rows not compared', R, " || $bindings !== $expected) {", ") {"),
 ('M18 find(): purchases not rechecked against roots', R, "            $this->requirePurchase($funding, $purchase);", "            // mutated"),
 ('M19 find(): exposure binding not checked', R, "\n            || ($payload['exposure_reservation_id'] ?? null) !== $funding->exposure_reservation_id", ""),
 ('M20 find(): recorded_at not checked', R, " || ($payload['recorded_at'] ?? null) !== $funding->created_at->utc()->format('Y-m-d\\TH:i:s.u\\Z')", ""),
 ('M21 find(): confirmed state not required', R, " || $version->state !== 'confirmed'", ""),
 ('M22 find(): cash amount not bound to principal', R, " || $purchase['cash']['amount'] !== $root->principal", ""),
 ('M23 find(): cash origin operation not bound', R, "\n            || $purchase['cash']['origin_operation_id'] !== $root->origin_operation_id", ""),
 ('M24 find(): root campaign/publication not bound', R, "\n            || $root->business_campaign_id !== $funding->business_campaign_id || $root->publication_sha256 !== $funding->publication_sha256", ""),
 ('M25 find(): party not bound', R, "\n            || $purchase['party_id'] !== $root->party_id || ", "\n            || "),
 ('M26 find(): ordinals not bound', R, "\n            || $purchase['ordinals'] !== ($root->payload['ordinals'] ?? null) || ", "\n            || "),
 ('M27 find(): disclosure digest not bound', R, " || $purchase['disclosure_sha256'] !== ($version->payload['disclosure_sha256'] ?? null)", ""),
 ('M28 page: can_cancel ignores funding', B, "$closure === null && $this->fundings->find($campaign->id) === null && now()", "$closure === null && now()"),
 ('M29 page: no funded lifecycle', B, "            $funding !== null => 'funded_pending_disbursement',\n", ""),
 ('M30 page: phase stays raising', B, "'phase' => $funding === null ? 'raising' : 'funded'", "'phase' => 'raising'"),
 ('M31 expireDue ignores funding', B, " || $this->fundings->find($campaign->id) !== null || now()->lt($campaign->expires_at)) {", " || now()->lt($campaign->expires_at)) {"),
 ('M32 close() ignores funding', B, "        if ($this->fundings->find($campaign->id) !== null) {\n            throw new CommandRejection('CAMPAIGN_FUNDED'", "        if (false) {\n            throw new CommandRejection('CAMPAIGN_FUNDED'"),
 ('M33 source lock() admits a funded campaign', S, "if ($requireOpen && ! $allowElapsed && $this->fundings->find($campaign->id) !== null) {", "if (false) {"),
 ('M34 lockForFunding also refuses a funded campaign (breaks retry)', S, "if ($requireOpen && ! $allowElapsed && $this->fundings->find", "if ($requireOpen && $this->fundings->find"),
 ('S01 install without the up-front LOCK TABLE', G, "            DB::statement('LOCK TABLE business_profiles, business_campaigns, primary_reservations, primary_commitments, investor_wallets, ledger_entries IN SHARE ROW EXCLUSIVE MODE');\n", ""),
 ('S02 funding insert does not lock the Business', G, "                    PERFORM 1 FROM business_profiles WHERE id = NEW.business_id FOR UPDATE;\n                    SELECT * INTO campaign", "                    SELECT * INTO campaign"),
 ('S03 funding insert does not lock roots/commitments/wallets', G, "                    PERFORM 1 FROM primary_reservations WHERE business_campaign_id = campaign.id ORDER BY id FOR UPDATE;\n                    PERFORM 1 FROM primary_commitments WHERE primary_reservation_id IN\n                        (SELECT id FROM primary_reservations WHERE business_campaign_id = campaign.id) ORDER BY id FOR UPDATE;\n                    PERFORM 1 FROM investor_wallets WHERE party_id IN\n                        (SELECT party_id FROM primary_reservations WHERE business_campaign_id = campaign.id) ORDER BY party_id FOR UPDATE;\n", ""),
 ('S04 funding insert: closed campaign accepted', G, "IF EXISTS (SELECT 1 FROM business_campaign_closures WHERE business_campaign_id = campaign.id)\n                        OR NEW.created_at", "IF NEW.created_at"),
 ('S05 funding insert: before live accepted', G, "                        OR NEW.created_at < campaign.live_at\n", ""),
 ('S06 funding insert: later confirmation accepted', G, "AND c.confirmed_at > NEW.created_at) THEN", "AND false) THEN"),
 ('S07 member: campaign not bound', G, "IF reservation.business_campaign_id IS DISTINCT FROM funding.business_campaign_id\n                        OR commitment", "IF commitment"),
 ('S08 member: commitment not bound to reservation', G, "                        OR commitment.primary_reservation_id IS DISTINCT FROM reservation.id\n", ""),
 ('S09 member: origin operation not bound', G, "                        OR NEW.origin_operation_id IS DISTINCT FROM reservation.origin_operation_id\n", ""),
 ('S10 member: wallet not bound to Party', G, "                        OR NOT EXISTS (SELECT 1 FROM investor_wallets WHERE id = NEW.wallet_id AND party_id = reservation.party_id)\n", ""),
 ('S11 member: hold entry not bound', G, "                        OR NOT EXISTS (SELECT 1 FROM ledger_entries WHERE id = NEW.hold_entry_id AND kind = 'primary_hold'\n                            AND source_type = 'primary_reservation' AND source_id = reservation.id AND wallet_id = NEW.wallet_id\n                            AND origin_operation_id = NEW.origin_operation_id AND currency = 'RWF')\n", ""),
 ('S12 member: commit entry kind not bound', G, "WHERE id = NEW.commit_entry_id AND kind = 'primary_commit'\n", "WHERE id = NEW.commit_entry_id\n"),
 ('S13 complete: empty membership accepted', G, "IF NOT EXISTS (SELECT 1 FROM primary_funding_commitments WHERE funding_id = funding.id)\n                        OR (SELECT", "IF (SELECT"),
 ('S14 complete: principal sum not checked', G, "WHERE m.funding_id = funding.id) <> funding.principal", "WHERE m.funding_id = funding.id) < 0"),
 ('S15 complete: unlisted campaign reservation accepted', G, "AND NOT EXISTS (SELECT 1 FROM primary_funding_commitments m WHERE m.funding_id = funding.id AND m.reservation_id = r.id))", "AND false)"),
 ('S16 complete: latest state not required confirmed', G, "ORDER BY revision DESC LIMIT 1) IS DISTINCT FROM 'confirmed'", "ORDER BY revision DESC LIMIT 1) IS NULL"),
 ('S17 complete: refund/other ledger movement accepted', G, "AND e.source_id = r.id AND e.kind NOT IN ('primary_hold', 'primary_commit'))", "AND e.source_id = r.id AND false)"),
 ('S18 complete: committed credit line not required', G, "WHERE l.entry_id = m.commit_entry_id AND l.direction = 'credit' AND a.kind = 'investor_committed'\n                                        AND a.wallet_id = m.wallet_id AND l.amount = r.principal)", "WHERE l.entry_id = m.commit_entry_id)"),
 ('S19 complete: held debit line not required', G, "WHERE l.entry_id = m.commit_entry_id AND l.direction = 'debit' AND a.kind = 'investor_held'\n                                        AND a.wallet_id = m.wallet_id AND l.amount = r.principal)", "WHERE l.entry_id = m.commit_entry_id)"),
 ('S20 no completeness trigger on the funding row', G, "                CREATE CONSTRAINT TRIGGER primary_funding_complete AFTER INSERT ON primary_campaign_fundings\n                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_complete_primary_funding();\n", ""),
 ('S21 no completeness trigger on membership rows', G, "                CREATE CONSTRAINT TRIGGER primary_funding_members_complete AFTER INSERT ON primary_funding_commitments\n                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_complete_primary_funding();\n", ""),
 ('S22 no gate on new reservation roots', G, "                CREATE TRIGGER primary_funding_reservation_gate BEFORE INSERT ON primary_reservations\n                    FOR EACH ROW EXECUTE FUNCTION refuse_funded_primary_admission();\n", ""),
 ('S23 no gate on held/confirmed versions', G, "                CREATE TRIGGER primary_funding_version_gate BEFORE INSERT ON primary_reservation_versions\n                    FOR EACH ROW EXECUTE FUNCTION refuse_funded_primary_admission();\n", ""),
 ('S24 version gate covers held only', G, "IF NEW.state NOT IN ('held', 'confirmed') THEN RETURN NEW; END IF;", "IF NEW.state NOT IN ('held') THEN RETURN NEW; END IF;"),
 ('S25 version gate covers confirmed only', G, "IF NEW.state NOT IN ('held', 'confirmed') THEN RETURN NEW; END IF;", "IF NEW.state NOT IN ('confirmed') THEN RETURN NEW; END IF;"),
 ('S26 no closure gate', G, "                CREATE TRIGGER primary_funding_closure_gate BEFORE INSERT ON business_campaign_closures\n                    FOR EACH ROW EXECUTE FUNCTION refuse_unfunded_close_after_funding();\n", ""),
 ('S27 no refund gate', G, "                CREATE TRIGGER primary_funding_refund_gate BEFORE INSERT ON ledger_entries\n                    FOR EACH ROW EXECUTE FUNCTION refuse_unilateral_funded_refund();\n", ""),
 ('S28 funding row mutable', G, "                CREATE TRIGGER primary_funding_immutable BEFORE UPDATE OR DELETE ON primary_campaign_fundings\n                    FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();\n", ""),
 ('S29 membership rows mutable', G, "                CREATE TRIGGER primary_funding_commitments_immutable BEFORE UPDATE OR DELETE ON primary_funding_commitments\n                    FOR EACH ROW EXECUTE FUNCTION reject_primary_record_mutation();\n", ""),
 ('S30 funding row deletable (UPDATE only)', G, "CREATE TRIGGER primary_funding_immutable BEFORE UPDATE OR DELETE ON", "CREATE TRIGGER primary_funding_immutable BEFORE UPDATE ON"),
 ('S31 membership rows updatable (DELETE only)', G, "CREATE TRIGGER primary_funding_commitments_immutable BEFORE UPDATE OR DELETE ON", "CREATE TRIGGER primary_funding_commitments_immutable BEFORE DELETE ON"),
 ('S32 down() drops retained funding', G, "IF EXISTS (SELECT 1 FROM primary_campaign_fundings) THEN\n                        RAISE EXCEPTION 'Retained funding requires", "IF false THEN\n                        RAISE EXCEPTION 'Retained funding requires"),
 ('S33 one funding per exposure not unique', G, "$table->ulid('exposure_reservation_id')->unique();", "$table->ulid('exposure_reservation_id');"),
 ('S34 publication FK dropped', G, "                $table->foreign(['business_campaign_id', 'publication_sha256'], 'primary_funding_publication')\n                    ->references(['id', 'sha256'])->on('business_campaigns')->restrictOnDelete();\n", ""),
]
only = sys.argv[4:]
env = dict(os.environ, TMPDIR=os.path.join(X, 'tmp'), DB_HOST='127.0.0.1', DB_PORT='5546', PAO_DISABLE='1')
ansi = re.compile(r'\x1b\[[0-9;]*m')
tests = {'pr': PR, 'repro': RP}[SET]
for name, path, old, new in M:
    if only and name.split()[0] not in only:
        continue
    fp = os.path.join(W, path); src = open(fp).read()
    if src.count(old) != 1:
        print((name, 'NOT-APPLIED (%d matches)' % src.count(old)), flush=True); continue
    open(fp, 'w').write(src.replace(old, new))
    try:
        p = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '-c', os.environ.get('MUT_CFG', 'phpunit.pgsql.xml'), '--no-tia', '--bail', *(['--filter', os.environ['MUT_FILTER']] if os.environ.get('MUT_FILTER') else []), *tests], cwd=W, env=env, capture_output=True, text=True, timeout=3000)
        out = ansi.sub('', p.stdout + p.stderr)
        summary = next((l.strip() for l in reversed(out.splitlines()) if l.strip().startswith('Tests:')), out.strip().splitlines()[-1] if out.strip() else '')
        failed = sorted({l.split('>')[-1].strip()[:110] for l in out.splitlines() if 'FAILED' in l})
        verdict = ('KILLED' if p.returncode else 'SURVIVED') + ' [' + summary + ']' + ((' by: ' + ' | '.join(failed[:2])) if failed else '')
    except subprocess.TimeoutExpired:
        verdict = 'TIMEOUT'
    finally:
        open(fp, 'w').write(src)
    print((name, verdict), flush=True)
