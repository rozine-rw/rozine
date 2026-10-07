"""Negative controls for POC selection and immutable validation evidence."""

import copy
import datetime
import importlib.util
import json
import unittest
import tempfile
from pathlib import Path
from unittest.mock import patch


def module(filename):
    spec = importlib.util.spec_from_file_location(filename, Path(__file__).with_name(filename))
    result = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(result)
    return result


policy = module("select-validation-scope.py")
runner = module("run-poc-tests.py")
budget = module("check-poc-budget.py")


class ScopeTests(unittest.TestCase):
    def test_only_poc_branches_use_poc(self):
        for env, expected in [
            ({"GITHUB_EVENT_NAME": "pull_request", "PR_BASE": "dev"}, "poc"),
            ({"GITHUB_EVENT_NAME": "push", "GITHUB_REF": "refs/heads/dev"}, "poc"),
            ({"GITHUB_EVENT_NAME": "pull_request", "PR_BASE": "uat"}, "poc"),
            ({"GITHUB_EVENT_NAME": "pull_request", "PR_BASE": "main"}, "full"),
            ({"GITHUB_EVENT_NAME": "push", "GITHUB_REF": "refs/heads/uat"}, "poc"),
            ({"GITHUB_EVENT_NAME": "push", "GITHUB_REF": "refs/heads/main"}, "full"),
            ({"GITHUB_EVENT_NAME": "workflow_dispatch", "VALIDATION": "full"}, "full"),
            ({"GITHUB_EVENT_NAME": "workflow_dispatch", "GITHUB_REF": "refs/heads/dev", "VALIDATION": "auto"}, "poc"),
            ({"GITHUB_EVENT_NAME": "workflow_dispatch", "GITHUB_REF": "refs/heads/uat", "VALIDATION": "poc"}, "poc"),
            ({"GITHUB_EVENT_NAME": "workflow_dispatch", "GITHUB_REF": "refs/heads/main", "VALIDATION": "auto"}, "full"),
            ({}, "full"),
        ]:
            with self.subTest(env=env):
                self.assertEqual(policy.requested_scope(env), expected)
        with self.assertRaises(ValueError):
            policy.requested_scope({"GITHUB_EVENT_NAME": "workflow_dispatch", "VALIDATION": "poc"})

    def test_catalog_is_explicit_and_all_files_exist(self):
        manifest = json.loads(Path("config/poc-test-manifest.json").read_text())
        for suite in ["php", "web"]:
            self.assertEqual(runner.selected_paths(manifest, suite), manifest[suite])
        for paths in [[], ["tests/missing.php"], ["tests/../composer.json"], ["composer.json"], ["tests/Unit/WalletMoneyTest.php"] * 2]:
            with self.subTest(paths=paths), self.assertRaises(ValueError):
                runner.selected_paths({"schema": 1, "scope": "poc-only", "php": paths}, "php")

    def test_selected_file_cannot_succeed_without_running_its_tests(self):
        with tempfile.TemporaryDirectory() as directory:
            report = Path(directory) / "report.xml"
            for content in ['<testsuites/>', '<testsuites><testsuite name="a" tests="0"/></testsuites>',
                            '<testsuites><testsuite name="a" tests="1"><skipped/></testsuite></testsuites>']:
                report.write_text(content)
                with self.subTest(content=content), self.assertRaises(ValueError):
                    runner.verify_results(report, ["a"], "web")
            report.write_text('<testsuites><testsuite name="a" tests="1"/></testsuites>')
            runner.verify_results(report, ["a"], "web")

    def test_budget_distinguishes_parallel_execution_queue_gaps_and_runner_sum(self):
        def job(name, start, end):
            return {"name": name, "status": "completed", "conclusion": "success", "started_at": f"2026-10-06T{start}Z", "completed_at": f"2026-10-06T{end}Z"}
        jobs = [job("Select CI scope", "11:00:00", "11:00:10"), job("Board sync offline tests", "11:00:00", "11:00:20"),
                job("POC PHP safety and static checks", "11:05:10", "11:09:10"), job("POC web safety and static checks", "11:05:10", "11:07:10")]
        jobs.append({"name": "Reuse POC validation evidence", "status": "completed", "conclusion": "skipped"})
        self.assertEqual(budget.measure(jobs), (250, 550, 390))
        jobs[0]["conclusion"] = "skipped"
        with self.assertRaises(ValueError):
            budget.measure(jobs)

    def setUp(self):
        self.run = {"id": 99, "run_attempt": 2, "status": "completed", "conclusion": "success",
                    "created_at": datetime.datetime.now(datetime.timezone.utc).isoformat(),
                    "event": "pull_request", "head_sha": "a" * 40, "workflow_id": 7,
                    "path": ".github/workflows/tests.yml", "head_repository": {"full_name": "rozine-rw/rozine"}}
        self.proof = {"schema": 2, "scope": "full", "required_jobs": policy.FULL_JOBS.copy(),
                      "repository": "rozine-rw/rozine", "run_id": "99", "run_attempt": "2",
                      "tested_sha": "b" * 40, "tree_sha": "c" * 40}
        self.commit = {"sha": "b" * 40, "tree": {"sha": "c" * 40}, "parents": [{"sha": "d" * 40}, {"sha": "a" * 40}]}
        self.jobs = [{"name": name, "conclusion": "success"} for name in [*policy.FULL_JOBS, "Record validation tree"]]

    def validate(self):
        policy.validate_proof(self.proof, self.run, self.jobs, self.commit, "c" * 40, "rozine-rw/rozine", "full")

    def test_full_proof_reuses_the_tree_despite_different_candidate_commit_identity(self):
        self.validate()
        self.run.update(event="workflow_dispatch", head_branch="dev", head_sha="b" * 40)
        self.validate()

    def test_full_proof_rejects_bad_or_partial_evidence(self):
        mutations = [
            ("proof", "schema", 1), ("proof", "scope", "poc"), ("proof", "required_jobs", policy.POC_JOBS),
            ("proof", "repository", "other/repo"), ("proof", "run_id", "100"),
            ("proof", "run_attempt", "1"), ("proof", "tree_sha", "e" * 40),
            ("proof", "tested_sha", "bad"), ("run", "status", "in_progress"),
            ("run", "conclusion", "cancelled"), ("run", "created_at", "2020-01-01T00:00:00Z"),
            ("run", "event", "unknown"), ("run", "head_sha", "e" * 40),
            ("commit", "sha", "e" * 40), ("commit", "tree", {"sha": "e" * 40}),
            ("commit", "parents", []),
        ]
        for target, key, value in mutations:
            with self.subTest(target=target, key=key):
                original = copy.deepcopy(getattr(self, target))
                getattr(self, target)[key] = value
                with self.assertRaises((ValueError, KeyError, TypeError)):
                    self.validate()
                setattr(self, target, original)
        for conclusion in ["failure", "skipped", "cancelled", None]:
            self.jobs[0]["conclusion"] = conclusion
            with self.subTest(conclusion=conclusion), self.assertRaises(ValueError):
                self.validate()
        self.jobs[0]["conclusion"] = "success"
        self.jobs.append(self.jobs[0].copy())
        with self.assertRaises(ValueError):
            self.validate()

    def test_poc_proof_requires_successful_publication_and_budget_steps(self):
        self.proof.update(scope="poc", required_jobs=policy.POC_JOBS, required_steps=policy.POC_STEPS)
        self.jobs = [{"name": name, "status": "completed", "started_at": "2026-10-06T11:00:00Z", "completed_at": "2026-10-06T11:00:20Z", "conclusion": "success", "steps": [{"name": step, "conclusion": "success"} for step in policy.POC_STEPS]} for name in ["Select CI scope", *policy.POC_JOBS]]
        policy.validate_proof(self.proof, self.run, self.jobs, self.commit, "c" * 40, "rozine-rw/rozine", "poc")
        php = next(job for job in self.jobs if job["name"] == "POC PHP safety and static checks")
        php["completed_at"] = "2026-10-06T11:10:01Z"
        with self.assertRaises(ValueError):
            policy.validate_proof(self.proof, self.run, self.jobs, self.commit, "c" * 40, "rozine-rw/rozine", "poc")
        php["completed_at"] = "2026-10-06T11:00:20Z"
        php["steps"][-1]["conclusion"] = "skipped"
        with self.assertRaises(ValueError):
            policy.validate_proof(self.proof, self.run, self.jobs, self.commit, "c" * 40, "rozine-rw/rozine", "poc")

    def test_newer_same_tree_failure_blocks_across_sha_branch_and_scope(self):
        for conclusion in ["failure", "cancelled", "skipped", "timed_out", None]:
            newer = dict(self.run, id=100, head_sha="e" * 40, event="workflow_dispatch", head_branch="uat", conclusion=conclusion)
            with self.subTest(conclusion=conclusion), patch.object(policy, "api", return_value={"tree": {"sha": "c" * 40}}):
                self.assertTrue(policy.newer_run_blocks("rozine-rw/rozine", newer, self.run, "c" * 40))
        newer["conclusion"] = "failure"
        with patch.object(policy, "api", return_value={"tree": {"sha": "e" * 40}}):
            self.assertFalse(policy.newer_run_blocks("rozine-rw/rozine", newer, self.run, "c" * 40))
        newer.update(event="pull_request")
        with patch.object(policy, "api", return_value={"tree": {"sha": "e" * 40}}), patch.object(policy, "download_evidence", side_effect=ValueError("unknown tested merge")):
            with self.assertRaises(ValueError):
                policy.newer_run_blocks("rozine-rw/rozine", newer, self.run, "c" * 40)

    def test_failed_latest_attempt_never_reuses_previous_attempt(self):
        latest = dict(self.run, run_attempt=3, conclusion="failure")
        self.assertTrue(policy.newer_run_blocks("rozine-rw/rozine", latest, self.run, "c" * 40))

    def test_trust_requires_repository_workflow_and_supported_event(self):
        self.assertTrue(policy.trusted_run(self.run, "rozine-rw/rozine", 7))
        for key, value in [("head_repository", {"full_name": "fork/repo"}), ("workflow_id", 8), ("path", "other.yml"), ("event", "pull_request_target")]:
            with self.subTest(key=key):
                self.assertFalse(policy.trusted_run(dict(self.run, **{key: value}), "rozine-rw/rozine", 7))

    def test_hosted_lookup_binds_artifact_attempt_jobs_commit_and_source_pr(self):
        self.proof["pull_number"] = "42"
        run = dict(self.run, head_branch="feature")
        env = {"GITHUB_REPOSITORY": "rozine-rw/rozine", "GITHUB_SHA": "f" * 40, "GITHUB_RUN_ID": "101"}
        pull = {"base": {"ref": "dev"}, "head": {"sha": "a" * 40, "repo": {"full_name": "rozine-rw/rozine"}}}

        def command(*args):
            if args == ("git", "rev-parse", "HEAD"):
                return "f" * 40
            if args == ("git", "rev-parse", "HEAD^{tree}"):
                return "c" * 40
            self.assertEqual(args[:4], ("gh", "run", "download", "99"))
            self.assertEqual(args[args.index("--name") + 1], "validation-tree-99-2")
            (Path(args[args.index("--dir") + 1]) / "tested-tree.json").write_text(json.dumps(self.proof))
            return ""

        for scenario in ["good", "expired", "wrong branch", "wrong repo", "malformed artifact", "changed config tree", "skipped gate", "wrong attempt"]:
            proof, jobs, source_pull = copy.deepcopy(self.proof), copy.deepcopy(self.jobs), copy.deepcopy(pull)
            artifacts = [{"name": "validation-tree-99-2", "expired": scenario == "expired"}]
            if scenario == "wrong branch":
                source_pull["base"]["ref"] = "main"
            elif scenario == "wrong repo":
                source_pull["head"]["repo"]["full_name"] = "other/repo"
            elif scenario == "malformed artifact":
                self.proof = []
            elif scenario == "changed config tree":
                self.proof["tree_sha"] = "e" * 40
            elif scenario == "wrong attempt":
                self.proof["run_attempt"] = "1"
            elif scenario == "skipped gate":
                jobs[0]["conclusion"] = "skipped"

            def api(endpoint):
                if endpoint.endswith("/workflows/tests.yml"):
                    return {"id": 7}
                if "/workflows/" in endpoint:
                    return {"workflow_runs": [run]}
                if "/artifacts?" in endpoint:
                    return {"artifacts": artifacts}
                if "/git/commits/" in endpoint:
                    return self.commit
                if "/pulls/" in endpoint:
                    return source_pull
                raise AssertionError(endpoint)

            with self.subTest(scenario=scenario), patch.object(policy, "command", side_effect=command), patch.object(policy, "api", side_effect=api), patch.object(policy, "pages", return_value=[{"jobs": jobs}]):
                self.assertEqual(policy.reusable_run(env, "full"), "99" if scenario == "good" else "")
            self.proof = proof


    def test_staging_full_poc_and_reuse_bind_original_scope(self):
        repository = "rozine-rw/rozine"
        env = {"GITHUB_REPOSITORY": repository, "CANDIDATE_SHA": "b" * 40, "EVIDENCE_RUN": "100"}
        run = dict(self.run, id=100, event="push", head_branch="uat", head_sha="b" * 40)
        for scope, reused in [("full", False), ("poc", False), ("poc", True)]:
            proof = dict(self.proof, scope=scope, tested_sha="b" * 40, run_id="100")
            if reused:
                proof.update(kind="reuse", source_run="99", source_attempt="2", required_jobs=policy.REUSE_JOBS, required_steps=policy.REUSE_STEPS)
            jobs = [{"name": name, "status": "completed", "started_at": "2026-10-06T11:00:00Z", "completed_at": "2026-10-06T11:00:20Z", "conclusion": "success", "steps": [{"name": step, "conclusion": "success"} for step in policy.REUSE_STEPS]}
                    for name in ["Select CI scope", "Board sync offline tests", "Reuse POC validation evidence"]]
            def api(endpoint):
                if endpoint.endswith("/workflows/tests.yml"):
                    return {"id": 7}
                return self.run if endpoint.endswith("/99") else run
            for scenario in ["good", "wrong candidate", "untrusted", "cancelled", "wrong receipt tree", "source attempt changed", "future source", "newer failure", "skipped reuse", "skipped provenance"]:
                original_run, original_proof, original_jobs = copy.deepcopy(run), copy.deepcopy(proof), copy.deepcopy(jobs)
                if scenario == "wrong candidate": run["head_sha"] = "e" * 40
                if scenario == "untrusted": run["head_repository"] = {"full_name": "fork/repo"}
                if scenario == "cancelled": run["conclusion"] = "cancelled"
                if scenario == "wrong receipt tree": proof["tree_sha"] = "e" * 40
                if scenario == "source attempt changed": proof["source_attempt"] = "1"
                if scenario == "future source": proof["source_run"] = "101"
                if scenario == "skipped reuse": jobs[-1]["conclusion"] = "skipped"
                if scenario == "skipped provenance": jobs[-1]["steps"][-1]["conclusion"] = "skipped"
                def evidence(*args):
                    if reused and len(args) < 3:
                        raise ValueError("no executed proof")
                    return proof
                def validate(*args):
                    if proof["tree_sha"] != "c" * 40:
                        raise ValueError("changed tree")
                expected_source = "99" if reused else "100"
                expected_good = scenario == "good" or (not reused and scenario in ("source attempt changed", "future source", "skipped reuse", "skipped provenance"))
                with self.subTest(scope=scope, reused=reused, scenario=scenario), patch.object(policy, "command", side_effect=["b" * 40, "c" * 40]), patch.object(policy, "api", side_effect=api), patch.object(policy, "download_evidence", side_effect=evidence), patch.object(policy, "validate_source", side_effect=validate), patch.object(policy, "run_jobs", return_value=jobs), patch.object(policy, "reusable_run", return_value="" if scenario == "newer failure" else expected_source):
                    if expected_good:
                        result = policy.staging_evidence(env)
                        self.assertEqual(result["scope"], scope)
                        self.assertEqual(result["source_run"], expected_source)
                    else:
                        with self.assertRaises(ValueError): policy.staging_evidence(env)
                run, proof, jobs = original_run, original_proof, original_jobs

    def test_staging_orchestration_validates_executed_and_original_reuse_proof(self):
        env = {"GITHUB_REPOSITORY": "rozine-rw/rozine", "CANDIDATE_SHA": "b" * 40, "EVIDENCE_RUN": "100"}
        original = dict(self.run, id=99, event="push", head_branch="dev", head_sha="b" * 40)
        candidate = dict(original, id=100, head_branch="uat")
        original_proof = dict(self.proof, scope="poc", run_id="99", required_jobs=policy.POC_JOBS, required_steps=policy.POC_STEPS)
        candidate_proof = dict(original_proof, run_id="100")
        jobs = [{"name": name, "status": "completed", "conclusion": "success", "started_at": "2026-10-06T11:00:00Z", "completed_at": "2026-10-06T11:00:20Z", "steps": [{"name": step, "conclusion": "success"} for step in policy.POC_STEPS]}
                for name in ["Select CI scope", *policy.POC_JOBS]]
        receipt = dict(candidate_proof, kind="reuse", source_run="99", source_attempt="2", required_jobs=policy.REUSE_JOBS, required_steps=policy.REUSE_STEPS)
        reused_jobs = [{"name": name, "status": "completed", "conclusion": "success", "started_at": "2026-10-06T11:00:00Z", "completed_at": "2026-10-06T11:00:20Z", "steps": [{"name": step, "conclusion": "success"} for step in policy.REUSE_STEPS]}
                       for name in policy.REUSE_JOBS]
        for reused in [False, True]:
            for failed in [False, True]:
                newer = dict(candidate, id=101, head_sha="e" * 40, conclusion="failure")
                runs = [candidate, original] + ([newer] if failed else [])
                def api(endpoint):
                    if endpoint.endswith("/workflows/tests.yml"): return {"id": 7}
                    if "/workflows/tests.yml/runs?" in endpoint: return {"workflow_runs": runs}
                    if "/git/commits/" in endpoint:
                        return dict(self.commit, parents=[{"sha": "a" * 40}])
                    if endpoint.endswith("/100"): return candidate
                    if endpoint.endswith("/99"): return original
                    raise AssertionError(endpoint)
                def evidence(repository, run, prefix="validation-tree"):
                    if run["id"] == 99: return original_proof
                    if reused:
                        if prefix == "validation-tree": raise ValueError("no original execution artifact")
                        return receipt
                    return candidate_proof
                def command(*args):
                    return "c" * 40 if args[-1] == "HEAD^{tree}" else "b" * 40
                def run_jobs(repository, run):
                    return reused_jobs if reused and run["id"] == 100 else jobs
                with self.subTest(reused=reused, failed=failed), patch.object(policy, "command", side_effect=command), patch.object(policy, "api", side_effect=api), patch.object(policy, "download_evidence", side_effect=evidence), patch.object(policy, "run_jobs", side_effect=run_jobs):
                    if failed:
                        with self.assertRaises(ValueError): policy.staging_evidence(env)
                    else:
                        self.assertEqual(policy.staging_evidence(env)["source_run"], "99" if reused else "100")

    def test_reuse_budget_includes_selection_board_and_receipt(self):
        jobs = [{"name": name, "status": "completed", "conclusion": "success", "started_at": "2026-10-06T11:00:00Z", "completed_at": "2026-10-06T11:00:20Z"}
                for name in ["Select CI scope", "Board sync offline tests", "Reuse POC validation evidence"]]
        self.assertEqual(budget.measure(jobs), (40, 20, 60))
        jobs[-1]["conclusion"] = "cancelled"
        with self.assertRaises(ValueError): budget.measure(jobs)


if __name__ == "__main__":
    unittest.main()
