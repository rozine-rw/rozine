"""Local read scheduling and cache controls; no GitHub or application requests."""

import contextlib
import copy
import ast
import itertools
import re
import threading
import time
import unittest
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from unittest.mock import patch

import test_validation_scope as fixtures

policy = fixtures.policy


class ImmutableReadTests(unittest.TestCase):
    def test_single_flight_copies_results_and_separates_repositories(self):
        cache = policy.ImmutableCommitReads()
        entered, release = threading.Event(), threading.Event()
        result = {"sha": "a" * 40, "tree": {"sha": "b" * 40}}

        def api(endpoint):
            entered.set()
            self.assertTrue(release.wait(2))
            return copy.deepcopy(result)

        with patch.object(policy, "api", side_effect=api) as calls, ThreadPoolExecutor(max_workers=4) as pool:
            futures = [pool.submit(cache.read, "owner/repo", result["sha"]) for _ in range(4)]
            self.assertTrue(entered.wait(2))
            release.set()
            answers = [future.result(timeout=2) for future in futures]
            answers[0]["tree"]["sha"] = "c" * 40
            self.assertEqual(answers[1], result)
            cache.read("another/repo", result["sha"])
        self.assertEqual(calls.call_count, 2)

    def test_each_selection_starts_cold_and_restores_context_on_error(self):
        def select(env):
            policy.immutable_commit("owner/repo", "a" * 40)
            policy.immutable_commit("owner/repo", "a" * 40)
            if env.get("fail"):
                raise ValueError("refuse")

        with patch.object(policy, "api", return_value={}) as calls, patch.object(policy, "select_canonical_candidate", side_effect=select):
            policy.canonical_selection({})
            with self.assertRaisesRegex(ValueError, "refuse"):
                policy.canonical_selection({"fail": True})
            self.assertIsNone(policy._commit_reads.get())
        self.assertEqual(calls.call_count, 2)

    def test_cache_hit_cannot_outlive_a_real_read_deadline(self):
        cache = policy.ImmutableCommitReads()
        with patch.object(policy, "api", return_value={}) as calls:
            cache.read("owner/repo", "a" * 40)
            token = policy._recovery_read_deadline.set(1)
            try:
                with patch.object(policy.time, "monotonic", return_value=2), self.assertRaisesRegex(policy.CoordinationRefusal, "recovery_evidence_lookup_timeout"):
                    cache.read("owner/repo", "a" * 40)
            finally:
                policy._recovery_read_deadline.reset(token)
        self.assertEqual(calls.call_count, 1)

    def test_failed_immutable_read_is_never_a_success(self):
        cache = policy.ImmutableCommitReads()
        with patch.object(policy, "api", side_effect=ValueError("bad commit")) as calls:
            for _ in range(2):
                with self.assertRaisesRegex(ValueError, "bad commit"):
                    cache.read("owner/repo", "a" * 40)
        self.assertEqual(calls.call_count, 1)

    def test_waiting_on_another_reader_cannot_extend_a_deadline(self):
        cache = policy.ImmutableCommitReads()
        entered, release = threading.Event(), threading.Event()

        def api(endpoint):
            entered.set()
            self.assertTrue(release.wait(3))
            return {}

        with patch.object(policy, 'api', side_effect=api), ThreadPoolExecutor(max_workers=1) as pool:
            owner = pool.submit(cache.read, 'owner/repo', 'a' * 40)
            self.assertTrue(entered.wait(3))
            token = policy._recovery_read_deadline.set(time.monotonic() + .02)
            try:
                with self.assertRaisesRegex(policy.CoordinationRefusal, 'recovery_evidence_lookup_timeout'):
                    cache.read('owner/repo', 'a' * 40)
            finally:
                policy._recovery_read_deadline.reset(token)
                release.set()
            self.assertEqual(owner.result(timeout=3), {})


class HistoricalPipelineTests(unittest.TestCase):
    def peers(self, count):
        runs = [{"id": number, "run_attempt": 1} for number in range(count)]
        inventory = {f"ci-coordination-identity-{run['id']}-1": {} for run in runs}
        return runs, inventory

    def test_refills_while_first_read_is_slow_and_preserves_order_and_context(self):
        runs, inventory = self.peers(20)
        release, fifth = threading.Event(), threading.Event()
        lock = threading.Lock()
        active, peak = 0, 0
        result, errors = [], []
        cache = policy.ImmutableCommitReads()

        def read(repository, run, **kwargs):
            nonlocal active, peak
            self.assertIs(policy._commit_reads.get(), cache)
            with lock:
                active += 1
                peak = max(peak, active)
            try:
                if run["id"] == 0:
                    self.assertTrue(release.wait(5))
                if run["id"] == 4:
                    fifth.set()
                return run["id"]
            finally:
                with lock:
                    active -= 1

        def consume():
            token = policy._commit_reads.set(cache)
            try:
                result.extend(policy.historical_identities("owner/repo", runs, inventory))
            except BaseException as error:
                errors.append(error)
            finally:
                policy._commit_reads.reset(token)

        with patch.object(policy, "coordination_identity", side_effect=read):
            worker = threading.Thread(target=consume)
            worker.start()
            try:
                self.assertTrue(fifth.wait(3), "a batch barrier prevented the fifth read")
            finally:
                release.set()
                worker.join(5)
        self.assertFalse(worker.is_alive())
        self.assertEqual(errors, [])
        self.assertEqual([identity for _, identity in result], list(range(20)))
        self.assertLessEqual(peak, 4)
        self.assertEqual(active, 0)

    def test_early_refusal_stops_scheduling_and_joins_readers(self):
        runs, inventory = self.peers(277)
        called = []

        def read(repository, run, **kwargs):
            called.append(run["id"])
            if run["id"] == 0:
                raise ValueError("invalid identity")
            return run["id"]

        with patch.object(policy, "coordination_identity", side_effect=read), self.assertRaisesRegex(ValueError, "invalid identity"):
            with contextlib.closing(policy.historical_identities("owner/repo", runs, inventory)) as identities:
                list(identities)
        self.assertLessEqual(len(called), 4)

    def test_delayed_277_peer_fixture_matches_batch_results(self):
        runs, inventory = self.peers(277)

        def read(repository, run, **kwargs):
            time.sleep(.012 if run["id"] % 4 == 0 else .001)
            return {"tree_sha": str(run["id"])}

        with patch.object(policy, "coordination_identity", side_effect=read):
            before = time.monotonic()
            old = []
            with ThreadPoolExecutor(max_workers=4) as pool:
                for start in range(0, len(runs), 4):
                    batch = runs[start:start + 4]
                    old.extend(zip(batch, pool.map(lambda run: read("owner/repo", run), batch)))
            batch_seconds = time.monotonic() - before
            before = time.monotonic()
            new = list(policy.historical_identities("owner/repo", runs, inventory))
            pipeline_seconds = time.monotonic() - before
        self.assertEqual(new, old)
        print(f"277-peer synthetic scheduling: batch={batch_seconds:.3f}s pipeline={pipeline_seconds:.3f}s (not hosted timing)")


class SelectionPerformanceTests(fixtures.NativeCoordinationFixture):
    def test_a_cached_commit_never_hides_failed_jobs_or_changed_attempts(self):
        for change in ("failed job", "changed attempt"):
            self.setUp()
            source = copy.deepcopy(self.runs[100])
            token = policy._commit_reads.set(policy.ImmutableCommitReads())
            try:
                with self.server():
                    artifact = policy.coordination_artifact_index(self.repository)['ci-coordination-identity-100-1']
                    policy.coordination_identity(self.repository, source, artifact=artifact)
                    if change == "failed job":
                        self.jobs[100][0]["conclusion"] = "failure"
                    else:
                        self.runs[100]["run_attempt"] += 1
                    with self.assertRaises(policy.CoordinationRefusal):
                        policy.coordination_identity(self.repository, source, artifact=artifact)
            finally:
                policy._commit_reads.reset(token)

    def test_277_repeated_commit_peers_keep_every_mutable_check_and_decision(self):
        for number in range(300, 577):
            fixtures.CanonicalCoordinationTests.failed_peer(self, number)
            sha = f"{300 + number % 13:040x}"
            self.runs[number]["head_sha"] = sha
            self.artifacts[number]["ci-coordination-identity"].update(head_sha=sha, tested_sha=sha)
        native = policy.api

        def measure(cached):
            with self.server(), patch.object(policy, "api", wraps=self.api) as calls, patch.object(policy, "pages", wraps=self.pages) as jobs:
                scope = contextlib.nullcontext() if cached else patch.object(policy, "ImmutableCommitReads", return_value=None)
                with scope:
                    result = policy.canonical_selection(self.env())
                commits = sum("/git/commits/" in call.args[0] for call in calls.call_args_list)
                attempts = sum("/actions/runs/" in call.args[0] and "/artifacts" not in call.args[0] for call in calls.call_args_list)
                return result, commits, attempts, jobs.call_count

        before, after = measure(False), measure(True)
        self.assertEqual(before[0], after[0])
        self.assertEqual(before[2:], after[2:], "native attempts and jobs must still be read freshly")
        self.assertLess(after[1], before[1] - 250)
        self.assertIs(policy.api, native)
        print(f"277-peer native commit reads: uncached={before[1]} cached={after[1]}; attempt/jobs unchanged={after[2:]}")


class WorkflowCacheTests(unittest.TestCase):
    def test_only_successful_trusted_target_runs_can_publish_analysis_cache(self):
        workflow = (Path(__file__).parents[1] / 'workflows/tests.yml').read_text()
        save = workflow.split('- name: Save trusted PHPStan dependency cache\n', 1)[1].split('- name:', 1)[0]
        expression = re.search(r'if: \$\{\{ (.+) \}\}', save)[1]
        allowed_nodes = (ast.Expression, ast.BoolOp, ast.And, ast.Or, ast.Compare, ast.Eq, ast.Constant)
        for event, ref, outcome in itertools.product(
                ('push', 'workflow_dispatch', 'pull_request', 'pull_request_target', 'workflow_run'),
                ('refs/heads/dev', 'refs/heads/uat', 'refs/heads/main', 'refs/tags/dev', 'refs/heads/feature'),
                ('success', 'failure', 'skipped', 'cancelled', '')):
            code = expression
            for name, value in {'github.event_name': event, 'github.ref': ref, 'steps.php-static.outcome': outcome}.items():
                code = code.replace(name, repr(value))
            tree = ast.parse(code.replace('&&', 'and').replace('||', 'or'), mode='eval')
            self.assertTrue(all(isinstance(node, allowed_nodes) for node in ast.walk(tree)))
            actual = eval(compile(tree, '<cache publication condition>', 'eval'), {'__builtins__': {}})
            expected = event in ('push', 'workflow_dispatch') and ref in ('refs/heads/dev', 'refs/heads/uat') and outcome == 'success'
            self.assertEqual(actual, expected, (event, ref, outcome))


if __name__ == "__main__":
    unittest.main()
