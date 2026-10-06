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
            return {"name": name, "conclusion": "success", "started_at": f"2026-10-06T{start}Z", "completed_at": f"2026-10-06T{end}Z"}
        jobs = [job("Select CI scope", "11:00:00", "11:00:10"), job("Board sync offline tests", "11:00:00", "11:00:20"),
                job("POC PHP safety and static checks", "11:05:10", "11:09:10"), job("POC web safety and static checks", "11:05:10", "11:07:10")]
        self.assertEqual(budget.measure(jobs), (250, 550, 390))
        jobs[0]["conclusion"] = "skipped"
        with self.assertRaises(ValueError):
            budget.measure(jobs)

    def setUp(self):
        self.run = {"id": 99, "run_attempt": 2, "status": "completed", "conclusion": "success",
                    "created_at": datetime.datetime.now(datetime.timezone.utc).isoformat(),
                    "event": "pull_request", "head_sha": "a" * 40}
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

    def test_latest_failed_attempt_blocks_older_success(self):
        older = dict(self.run, id=98)
        latest = dict(self.run, conclusion="failure")
        env = {"GITHUB_REPOSITORY": "rozine-rw/rozine", "GITHUB_SHA": "f" * 40, "GITHUB_RUN_ID": "101"}
        with patch.object(policy, "command", side_effect=["f" * 40, "c" * 40]), patch.object(policy, "api", return_value={"workflow_runs": [older, latest]}) as api:
            self.assertEqual(policy.reusable_run(env, "full"), "")
            self.assertEqual(api.call_count, 1)

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
                source_pull["base"]["ref"] = "uat"
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


if __name__ == "__main__":
    unittest.main()
