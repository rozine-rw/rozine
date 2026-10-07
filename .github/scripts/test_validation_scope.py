"""Negative controls for POC selection and immutable validation evidence."""

import copy
import contextlib
import datetime
import importlib.util
import io
import json
import os
import subprocess
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
lint = module("check-workflow-lint.py")


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

    def test_dispatch_and_promotion_reuse_only_poc_while_full_always_executes(self):
        contexts = [
            ({"GITHUB_EVENT_NAME": "workflow_dispatch", "GITHUB_REF": "refs/heads/dev", "VALIDATION": "auto"}, True),
            ({"GITHUB_EVENT_NAME": "workflow_dispatch", "GITHUB_REF": "refs/heads/uat", "VALIDATION": "poc"}, True),
            ({"GITHUB_EVENT_NAME": "pull_request", "PR_BASE": "uat", "PR_HEAD_BRANCH": "dev"}, True),
            ({"GITHUB_EVENT_NAME": "workflow_dispatch", "GITHUB_REF": "refs/heads/uat", "VALIDATION": "full"}, False),
            ({"GITHUB_EVENT_NAME": "workflow_dispatch", "GITHUB_REF": "refs/heads/main", "VALIDATION": "auto"}, False),
        ]
        for context, reused in contexts:
            with self.subTest(context=context), tempfile.TemporaryDirectory() as directory:
                output = Path(directory) / "output"
                env = dict(context, GITHUB_OUTPUT=str(output))
                with patch.dict(policy.os.environ, env, clear=True), patch.object(policy.sys, "argv", ["select-validation-scope.py"]), patch.object(policy, "reusable_run", return_value=("99", "2")) as lookup:
                    policy.main()
                self.assertEqual(lookup.call_count, int(reused))
                self.assertIn("source_attempt=" + ("2" if reused else "") + "\n", output.read_text())
                self.assertIn("source_run=" + ("99" if reused else "") + "\n", output.read_text())
                self.assertIn("full=" + ("false" if reused else "true") + "\n", output.read_text())

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
        rerun = dict(newer, id=98, conclusion="failure", run_started_at="2099-01-01T00:00:00Z")
        with patch.object(policy, "api", return_value={"tree": {"sha": "c" * 40}}):
            self.assertTrue(policy.newer_run_blocks("rozine-rw/rozine", rerun, self.run, "c" * 40))
        rerun.update(run_started_at="2000-01-01T00:00:00Z", updated_at="2099-01-01T00:00:00Z")
        with patch.object(policy, "api", return_value={"tree": {"sha": "c" * 40}}):
            self.assertTrue(policy.newer_run_blocks("rozine-rw/rozine", rerun, self.run, "c" * 40))
        newer["conclusion"] = "failure"
        with patch.object(policy, "api", return_value={"tree": {"sha": "e" * 40}}):
            self.assertFalse(policy.newer_run_blocks("rozine-rw/rozine", newer, self.run, "c" * 40))
        newer.update(event="pull_request")
        with patch.object(policy, "api", return_value={"tree": {"sha": "e" * 40}}), patch.object(policy, "download_evidence", side_effect=ValueError("unknown tested merge")):
            with self.assertRaises(ValueError):
                policy.newer_run_blocks("rozine-rw/rozine", newer, self.run, "c" * 40)

    def test_failed_merge_tree_provenance_cannot_hide_newer_failure(self):
        newer = dict(self.run, id=100, conclusion="failure", head_sha="e" * 40)
        proof = dict(self.proof, run_id="100")
        tested = dict(self.commit, parents=[{"sha": "a" * 40}, {"sha": "e" * 40}])
        def api(endpoint):
            return {"tree": {"sha": "f" * 40}} if endpoint.endswith("e" * 40) else tested
        for scenario in ["good", "wrong run", "wrong attempt", "forged tree", "wrong parent"]:
            original_proof, original_tested = copy.deepcopy(proof), copy.deepcopy(tested)
            if scenario == "wrong run": proof["run_id"] = "99"
            if scenario == "wrong attempt": proof["run_attempt"] = "1"
            if scenario == "forged tree": proof["tree_sha"] = "f" * 40
            if scenario == "wrong parent": tested["parents"][1]["sha"] = "a" * 40
            with self.subTest(scenario=scenario), patch.object(policy, "api", side_effect=api), patch.object(policy, "download_evidence", return_value=proof):
                if scenario == "good":
                    self.assertTrue(policy.newer_run_blocks("rozine-rw/rozine", newer, self.run, "c" * 40))
                else:
                    with self.assertRaises(ValueError): policy.newer_run_blocks("rozine-rw/rozine", newer, self.run, "c" * 40)
            proof, tested = original_proof, original_tested

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
            artifacts = [{"id": 1, "name": "validation-tree-99-2", "expired": scenario == "expired"}]
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
                    return {"total_count": 1, "workflow_runs": [run]}
                if "/artifacts?" in endpoint:
                    return {"total_count": len(artifacts), "artifacts": artifacts}
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
                    if "/workflows/tests.yml/runs?" in endpoint: return {"total_count": len(runs), "workflow_runs": runs}
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

    def test_complete_history_detects_newer_failed_rerun_outside_first_page(self):
        old_failed = dict(self.run, id=77, conclusion="failure", updated_at="2099-01-01T00:00:00Z")
        first_runs = [dict(self.run, id=value) for value in range(99, 199)]
        first = {"total_count": 101, "workflow_runs": first_runs}
        last = {"total_count": 101, "workflow_runs": [old_failed]}
        env = {"GITHUB_REPOSITORY": "rozine-rw/rozine", "GITHUB_SHA": "f" * 40, "SOURCE_RUN": "99", "SOURCE_ATTEMPT": "2"}
        def api(endpoint):
            return first if "/runs?" in endpoint else {"id": 7}
        with patch.object(policy, "command", side_effect=["f" * 40, "c" * 40]), patch.object(policy, "api", side_effect=api), patch.object(policy, "pages", return_value=[first, last]), patch.object(policy, "validate_source", return_value=self.proof):
            self.assertEqual(policy.reusable_run(env, "full"), "")
        for scenario in ["missing page", "duplicate IDs", "changed count", "bound exceeded"]:
            responses = copy.deepcopy([first, last])
            first_response = copy.deepcopy(first)
            if scenario == "missing page": responses = [first]
            if scenario == "duplicate IDs": responses[-1]["workflow_runs"] = [first_runs[0]]
            if scenario == "changed count": responses[-1]["total_count"] = 102
            if scenario == "bound exceeded": first_response["total_count"] = 5001
            with self.subTest(scenario=scenario), patch.object(policy, "api", return_value=first_response), patch.object(policy, "pages", return_value=responses):
                with self.assertRaises(ValueError): policy.workflow_history("rozine-rw/rozine")

    def test_source_attempt_is_pinned_and_changed_attempt_refuses_receipt(self):
        env = {"GITHUB_REPOSITORY": "rozine-rw/rozine", "GITHUB_SHA": "f" * 40, "SOURCE_RUN": "99", "SOURCE_ATTEMPT": "2"}
        for conclusion in ["success", "failure", None]:
            changed = dict(self.run, run_attempt=3, conclusion=conclusion)
            def api(endpoint):
                return {"total_count": 1, "workflow_runs": [changed]} if "/runs?" in endpoint else {"id": 7}
            with self.subTest(conclusion=conclusion), patch.object(policy, "command", side_effect=["f" * 40, "c" * 40]), patch.object(policy, "api", side_effect=api), patch.object(policy, "validate_source") as validate, patch.object(policy, "record") as record:
                with self.assertRaises(ValueError): policy.record_reuse(env)
                validate.assert_not_called()
                record.assert_not_called()
        with tempfile.TemporaryDirectory() as directory, patch.object(policy, "reusable_run", return_value="99") as validate, patch.object(policy, "record") as record, patch.object(policy, "Path", wraps=Path) as paths:
            path = Path(directory) / "tested-tree.json"
            path.write_text(json.dumps(self.proof))
            paths.return_value = path
            policy.record_reuse(env)
            self.assertEqual(json.loads(path.read_text())["source_attempt"], "2")
            validate.assert_called_once_with(env, "poc")
            record.assert_called_once_with(env, "poc")

    def test_reuse_budget_includes_selection_board_and_receipt(self):
        jobs = [{"name": name, "status": "completed", "conclusion": "success", "started_at": "2026-10-06T11:00:00Z", "completed_at": "2026-10-06T11:00:20Z"}
                for name in ["Select CI scope", "Board sync offline tests", "Reuse POC validation evidence"]]
        self.assertEqual(budget.measure(jobs), (40, 20, 60))
        jobs[-1]["conclusion"] = "cancelled"
        with self.assertRaises(ValueError): budget.measure(jobs)


class MergeIdentityTests(unittest.TestCase):
    def setUp(self):
        self.repository = "rozine-rw/rozine"
        self.source = {"id": 99, "run_attempt": 2, "status": "completed", "conclusion": "success",
                       "created_at": datetime.datetime.now(datetime.timezone.utc).isoformat(),
                       "event": "pull_request", "head_sha": "a" * 40, "workflow_id": 7,
                       "path": ".github/workflows/tests.yml", "head_repository": {"full_name": self.repository}}
        self.run = dict(self.source, id=100, head_sha="e" * 40, conclusion="failure")
        self.proof = {"schema": 1, "kind": "pr-merge-identity", "scope": "identity-only",
                      "repository": self.repository, "run_id": "100", "run_attempt": "2", "pull_number": "246",
                      "tested_sha": "b" * 40, "tree_sha": "f" * 40, "base_sha": "d" * 40, "head_sha": "e" * 40}
        self.commit = {"sha": "b" * 40, "tree": {"sha": "f" * 40},
                       "parents": [{"sha": "d" * 40}, {"sha": "e" * 40}]}
        self.latest = copy.deepcopy(self.run)
        self.jobs = [{"name": "Select CI scope", "steps": [
            {"name": name, "conclusion": "success"} for name in policy.MERGE_IDENTITY_STEPS]}]
        self.artifacts = [{"id": 1, "name": "pr-merge-identity-100-2", "expired": False}]

    def api(self, endpoint):
        if "/artifacts?" in endpoint:
            return {"total_count": len(self.artifacts), "artifacts": self.artifacts}
        if endpoint.endswith("/100"):
            return self.latest
        if endpoint.endswith("e" * 40):
            return {"tree": {"sha": "9" * 40}}
        if endpoint.endswith("b" * 40):
            return self.commit
        raise AssertionError(endpoint)

    def command(self, *args):
        self.assertEqual(args[:4], ("gh", "run", "download", "100"))
        self.assertEqual(args[args.index("--name") + 1], "pr-merge-identity-100-2")
        (Path(args[args.index("--dir") + 1]) / "merge-identity.json").write_text(json.dumps(self.proof))
        return ""

    def classify(self):
        with patch.object(policy, "api", side_effect=self.api), patch.object(policy, "command", side_effect=self.command), patch.object(policy, "run_jobs", return_value=self.jobs):
            return policy.newer_run_blocks(self.repository, self.run, self.source, "c" * 40)

    def test_actual_artifact_lookup_excludes_only_proven_different_merge_trees(self):
        for conclusion in ["failure", "cancelled", "skipped", "timed_out", None]:
            for matching in [False, True]:
                self.run.update(conclusion=conclusion, status="in_progress" if conclusion is None else "completed")
                self.latest = copy.deepcopy(self.run)
                self.proof["tree_sha"] = self.commit["tree"]["sha"] = "c" * 40 if matching else "f" * 40
                with self.subTest(conclusion=conclusion, matching=matching):
                    self.assertEqual(self.classify(), matching)

    def test_identity_is_never_successful_validation_proof(self):
        with self.assertRaises(ValueError):
            policy.validate_proof(self.proof, self.run, self.jobs, self.commit, "f" * 40, self.repository, "poc")

    def test_unknown_legacy_run_cannot_be_excluded(self):
        self.artifacts = []
        with self.assertRaises(policy.MissingEvidence): self.classify()

    def test_expired_duplicate_unreadable_legacy_evidence_never_falls_back(self):
        for scenario in ["expired", "duplicate", "malformed", "download failure", "API failure"]:
            with self.subTest(scenario=scenario):
                legacy = {"id": 2, "name": "validation-tree-100-2", "expired": scenario == "expired"}
                artifacts = [legacy, dict(legacy, id=3)] if scenario == "duplicate" else [legacy]
                def download(*args):
                    if scenario == "download failure": raise subprocess.CalledProcessError(1, "gh")
                    (Path(args[args.index("--dir") + 1]) / "tested-tree.json").write_text("not json")
                    return ""
                def api(endpoint):
                    if "/artifacts?" in endpoint:
                        if scenario == "API failure": raise subprocess.CalledProcessError(1, "gh")
                        return {"total_count": len(artifacts), "artifacts": artifacts}
                    return {"tree": {"sha": "9" * 40}}
                with patch.object(policy, "api", side_effect=api), patch.object(policy, "command", side_effect=download), patch.object(policy, "immutable_merge_identity") as fallback:
                    with self.assertRaises((ValueError, subprocess.SubprocessError)):
                        policy.newer_run_blocks(self.repository, self.run, self.source, "c" * 40)
                    fallback.assert_not_called()

    def test_identity_binding_native_commit_and_publication_controls(self):
        for scenario in ["schema", "kind", "scope", "repository", "run_id", "run_attempt", "head_sha", "pull_number",
                         "tested_sha", "tree_sha", "base_sha", "parents", "duplicate job", "duplicate step", "failed record", "pending upload", "stale", "fork", "wrong workflow"]:
            original = copy.deepcopy((self.proof, self.commit, self.jobs, self.run))
            if scenario in self.proof: self.proof[scenario] = "wrong"
            elif scenario == "parents": self.commit["parents"].reverse()
            elif scenario == "duplicate job": self.jobs *= 2
            elif scenario == "duplicate step": self.jobs[0]["steps"].append(self.jobs[0]["steps"][0])
            elif scenario == "failed record": self.jobs[0]["steps"][0]["conclusion"] = "failure"
            elif scenario == "pending upload": self.jobs[0]["steps"][1]["conclusion"] = None
            elif scenario == "stale":
                self.run.update(created_at="2000-01-01T00:00:00Z", updated_at=self.source["created_at"])
            elif scenario == "fork": self.run["head_repository"]["full_name"] = "fork/repo"
            elif scenario == "wrong workflow": self.run["path"] = "other.yml"
            with self.subTest(scenario=scenario), self.assertRaises(ValueError): self.classify()
            self.proof, self.commit, self.jobs, self.run = original

    def test_native_api_cannot_confirm_a_forged_tree_or_parent(self):
        for key in ["sha", "tree", "parents"]:
            original = copy.deepcopy(self.commit)
            self.commit[key] = {"sha": "c" * 40} if key == "tree" else ([] if key == "parents" else "c" * 40)
            with self.subTest(key=key), self.assertRaises(ValueError): self.classify()
            self.commit = original

    def test_expired_duplicate_and_nonobject_identity_cannot_exclude_a_run(self):
        original = copy.deepcopy((self.artifacts, self.proof))
        for scenario in ["expired", "duplicate", "nonobject"]:
            if scenario == "expired": self.artifacts[0]["expired"] = True
            if scenario == "duplicate": self.artifacts.append(dict(self.artifacts[0], id=2))
            if scenario == "nonobject": self.proof = []
            with self.subTest(scenario=scenario), self.assertRaises(ValueError): self.classify()
            self.artifacts, self.proof = copy.deepcopy(original)

    def test_attempt_and_run_identity_are_rechecked_after_artifact_resolution(self):
        for key, value in [("run_attempt", 3), ("head_sha", "a" * 40), ("event", "push"), ("workflow_id", 8),
                           ("path", "other.yml"), ("head_repository", {"full_name": "fork/repo"})]:
            self.latest = dict(self.run, **{key: value})
            with self.subTest(key=key), self.assertRaisesRegex(ValueError, "changed during"):
                self.classify()

    def test_validation_artifact_appearing_during_resolution_refuses_exclusion(self):
        inventory = {"total_count": 1, "artifacts": self.artifacts}
        changed = {"total_count": 2, "artifacts": self.artifacts + [{"id": 2, "name": "validation-tree-100-2", "expired": False}]}
        calls = iter([inventory, inventory, changed])
        def api(endpoint):
            return next(calls) if "/artifacts?" in endpoint else self.api(endpoint)
        with patch.object(policy, "api", side_effect=api), patch.object(policy, "command", side_effect=self.command), patch.object(policy, "run_jobs", return_value=self.jobs):
            with self.assertRaisesRegex(ValueError, "appeared during"):
                policy.newer_run_blocks(self.repository, self.run, self.source, "c" * 40)

    def test_same_head_and_feature_tree_still_block_without_identity_lookup(self):
        for same_head in [False, True]:
            run = dict(self.run, head_sha=self.source["head_sha"] if same_head else self.run["head_sha"])
            with patch.object(policy, "api", return_value={"tree": {"sha": "c" * 40}}), patch.object(policy, "download_evidence") as download:
                self.assertTrue(policy.newer_run_blocks(self.repository, run, self.source, "c" * 40))
                download.assert_not_called()

    def test_complete_artifact_pagination_required_before_absence(self):
        first = {"total_count": 2, "artifacts": [{"id": 1, "name": "unrelated"}]}
        last = {"total_count": 2, "artifacts": [{"id": 2, "name": "validation-tree-100-2", "expired": True}]}
        with patch.object(policy, "api", return_value=first), patch.object(policy, "pages", return_value=[first, last]):
            with self.assertRaises(ValueError) as error: policy.download_evidence(self.repository, self.run)
            self.assertNotIsInstance(error.exception, policy.MissingEvidence)
        for scenario in ["missing page", "duplicate IDs", "changed count", "bound exceeded"]:
            responses, response = copy.deepcopy([first, last]), copy.deepcopy(first)
            if scenario == "missing page": responses = [first]
            if scenario == "duplicate IDs": responses[-1]["artifacts"][0]["id"] = 1
            if scenario == "changed count": responses[-1]["total_count"] = 3
            if scenario == "bound exceeded": response["total_count"] = 1001
            with self.subTest(scenario=scenario), patch.object(policy, "api", return_value=response), patch.object(policy, "pages", return_value=responses):
                with self.assertRaises(ValueError): policy.artifact_inventory(self.repository, self.run)

    def test_selector_reuses_original_only_when_failed_pr_merge_is_proven_unrelated(self):
        env = {"GITHUB_REPOSITORY": self.repository, "GITHUB_SHA": "8" * 40, "SOURCE_RUN": "99"}
        for matching in [False, True]:
            self.proof["tree_sha"] = self.commit["tree"]["sha"] = "c" * 40 if matching else "f" * 40
            def command(*args):
                if args[:2] == ("git", "rev-parse"): return "c" * 40 if args[-1] == "HEAD^{tree}" else "8" * 40
                return self.command(*args)
            def api(endpoint):
                return {"id": 7} if endpoint.endswith("/workflows/tests.yml") else self.api(endpoint)
            with self.subTest(matching=matching), patch.object(policy, "command", side_effect=command), patch.object(policy, "api", side_effect=api), patch.object(policy, "workflow_history", return_value=[self.run, self.source]), patch.object(policy, "validate_source"), patch.object(policy, "run_jobs", return_value=self.jobs):
                self.assertEqual(policy.reusable_run(env, "poc", with_attempt=True), ("", "") if matching else ("99", "2"))

    def test_writer_records_actual_shallow_merge_and_rejects_event_or_checkout_mismatch(self):
        script = str(Path(policy.__file__).resolve())
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            repo, checkout = root / "repo", root / "checkout"
            repo.mkdir()
            def git(*args, cwd=repo):
                return subprocess.check_output(["git", *args], cwd=cwd, text=True, stderr=subprocess.PIPE).strip()
            git("init", "-b", "base")
            git("config", "user.email", "test@example.invalid")
            git("config", "user.name", "Test")
            (repo / "base").write_text("base")
            git("add", ".")
            git("commit", "-m", "base")
            git("checkout", "-b", "feature")
            (repo / "feature").write_text("feature")
            git("add", ".")
            git("commit", "-m", "feature")
            head = git("rev-parse", "HEAD")
            git("checkout", "base")
            (repo / "advance").write_text("advance")
            git("add", ".")
            git("commit", "-m", "advance base")
            base = git("rev-parse", "HEAD")
            git("merge", "--no-ff", "feature", "-m", "test merge")
            tested = git("rev-parse", "HEAD")
            git("clone", "--depth", "1", repo.as_uri(), str(checkout))
            self.assertEqual(git("rev-parse", "--is-shallow-repository", cwd=checkout), "true")
            self.assertEqual(git("show", "-s", "--format=%P", "HEAD", cwd=checkout), "")
            event = {"pull_request": {"number": 246, "head": {"sha": head, "repo": {"full_name": self.repository}}, "base": {"sha": base, "repo": {"full_name": self.repository}}}}
            event_path = root / "event.json"
            env = dict(os.environ, GITHUB_EVENT_NAME="pull_request", GITHUB_EVENT_PATH=str(event_path), GITHUB_REPOSITORY=self.repository, GITHUB_RUN_ID="100", GITHUB_RUN_ATTEMPT="2", GITHUB_SHA=tested)
            preflight_event = copy.deepcopy(event)
            preflight_event["pull_request"]["base"]["ref"] = "dev"
            preflight_event["pull_request"]["head"]["ref"] = "feature"
            event_path.write_text(json.dumps(preflight_event))
            preflight_env = dict(env, PR_BASE="dev", PR_HEAD_REPOSITORY=self.repository, GITHUB_OUTPUT=str(root / "outputs"))
            preflight = subprocess.run(["python3", script, "record-coordination-identity"], cwd=checkout, env=preflight_env, capture_output=True, text=True, timeout=45)
            self.assertEqual(preflight.returncode, 0, preflight.stderr)
            identity = json.loads((checkout / "ci-coordination-identity/coordination-identity.json").read_text())
            self.assertEqual((identity["tested_sha"], identity["base_sha"], identity["head_sha"]), (tested, base, head))
            self.assertIn("coordinate=true", (root / "outputs").read_text())
            for scenario in ["good", "changed head", "changed base", "fork", "wrong checkout", "wrong event"]:
                current, current_env = copy.deepcopy(event), dict(env)
                if scenario == "changed head": current["pull_request"]["head"]["sha"] = "a" * 40
                if scenario == "changed base": current["pull_request"]["base"]["sha"] = "a" * 40
                if scenario == "fork": current["pull_request"]["head"]["repo"]["full_name"] = "fork/repo"
                if scenario == "wrong checkout": current_env["GITHUB_SHA"] = "a" * 40
                if scenario == "wrong event": current_env["GITHUB_EVENT_NAME"] = "push"
                event_path.write_text(json.dumps(current))
                result = subprocess.run(["python3", script, "record-merge-identity"], cwd=checkout, env=current_env, capture_output=True, text=True, timeout=45)
                with self.subTest(scenario=scenario):
                    self.assertEqual(result.returncode == 0, scenario == "good", result.stderr)
                    if scenario == "good":
                        proof = json.loads((checkout / "ci-merge-identity/merge-identity.json").read_text())
                        self.assertEqual((proof["tested_sha"], proof["tree_sha"], proof["base_sha"], proof["head_sha"]), (tested, git("rev-parse", "HEAD^{tree}", cwd=checkout), base, head))


class CanonicalCoordinationTests(unittest.TestCase):
    """A native API/artifact simulation; this does not simulate lock acquisition."""

    def setUp(self):
        self.repository = "rozine-rw/rozine"
        self.tree = "c" * 40
        now = datetime.datetime.now(datetime.timezone.utc)
        def run(number, event, sha, branch, seconds, status="in_progress", conclusion=None):
            stamp = (now - datetime.timedelta(seconds=seconds)).isoformat()
            return {"id": number, "run_attempt": 1, "created_at": stamp, "updated_at": stamp,
                    "status": status, "conclusion": conclusion, "event": event, "head_sha": sha,
                    "head_branch": branch, "workflow_id": 7, "path": ".github/workflows/tests.yml",
                    "head_repository": {"full_name": self.repository}}
        self.runs = {100: run(100, "pull_request", "a" * 40, "feature", 120, "completed", "success"),
                     200: run(200, "push", "d" * 40, "dev", 10),
                     201: run(201, "pull_request", "d" * 40, "dev", 6)}
        self.commits = {"b" * 40: {"sha": "b" * 40, "tree": {"sha": self.tree}, "parents": [{"sha": "9" * 40}, {"sha": "a" * 40}]},
                        "d" * 40: {"sha": "d" * 40, "tree": {"sha": self.tree}, "parents": [{"sha": "8" * 40}]},
                        "e" * 40: {"sha": "e" * 40, "tree": {"sha": self.tree}, "parents": [{"sha": "f" * 40}, {"sha": "d" * 40}]},
                        "a" * 40: {"sha": "a" * 40, "tree": {"sha": "7" * 40}}}
        self.artifacts = {number: {} for number in self.runs}
        self.jobs = {number: [] for number in self.runs}
        for number in self.runs:
            self.identity(number)
        self.publish(100, self.decision(100, "execute", 100, 100))
        self.complete_validation(100)
        self.jobs[200].append({"name": "Select CI scope", "status": "in_progress", "conclusion": None})
        self.jobs[201].append({"name": "Select CI scope", "status": "queued", "conclusion": None})
        self.current = 200

    def job(self, name, duration=20, steps=()):
        return {"name": name, "status": "completed", "conclusion": "success", "started_at": "2026-10-07T09:00:00Z",
                "completed_at": f"2026-10-07T09:00:{duration:02d}Z", "steps": [{"name": step, "conclusion": "success"} for step in steps]}

    def identity(self, number):
        run = self.runs[number]
        tested = {100: "b" * 40, 200: "d" * 40, 201: "e" * 40}.get(number, run["head_sha"])
        proof = {"schema": 1, "protocol": 1, "kind": "poc-coordination-identity", "scope": "poc",
                 "repository": self.repository, "run_id": str(number), "run_attempt": str(run["run_attempt"]),
                 "tested_sha": tested, "tree_sha": self.tree, "event": run["event"], "head_sha": run["head_sha"]}
        if run["event"] == "pull_request":
            proof.update(base_ref="dev" if number == 100 else "uat", pull_number="10" if number == 100 else "250", base_sha=self.commits[tested]["parents"][0]["sha"])
        self.artifacts[number]["ci-coordination-identity"] = proof
        self.jobs[number].append(self.job(policy.IDENTITY_JOB, steps=policy.IDENTITY_STEPS))
        return proof

    def decision(self, number, mode, owner, source):
        proof = {key: self.artifacts[number]["ci-coordination-identity"][key] for key in
                 ("schema", "protocol", "scope", "repository", "run_id", "run_attempt", "tested_sha", "tree_sha")}
        proof.update(kind="poc-coordination-decision", mode=mode, owner_run=str(owner), owner_attempt="1", source_run=str(source), source_attempt="1")
        return proof

    def publish(self, number, decision):
        self.artifacts[number]["ci-coordination-decision"] = decision
        self.jobs[number] = [job for job in self.jobs[number] if job["name"] != "Select CI scope"] + [self.job("Select CI scope", steps=policy.DECISION_STEPS)]

    def complete_validation(self, number):
        run = self.runs[number]
        identity = self.artifacts[number]["ci-coordination-identity"]
        self.artifacts[number]["validation-tree"] = {"schema": 2, "scope": "poc", "repository": self.repository,
            "tested_sha": identity["tested_sha"], "tree_sha": self.tree, "run_id": str(number), "run_attempt": str(run["run_attempt"]),
            "pull_number": identity.get("pull_number", ""), "required_jobs": policy.POC_JOBS, "required_steps": policy.POC_STEPS}
        self.jobs[number] += [self.job(name, steps=policy.POC_STEPS if name == "POC PHP safety and static checks" else ()) for name in policy.POC_JOBS]
        run.update(status="completed", conclusion="success")

    def env(self, number=None, **extra):
        number = self.current if number is None else number
        return {"GITHUB_REPOSITORY": self.repository, "GITHUB_SHA": self.artifacts[number]["ci-coordination-identity"]["tested_sha"],
                "GITHUB_RUN_ID": str(number), "GITHUB_RUN_ATTEMPT": str(self.runs[number]["run_attempt"]), "COORDINATION_ACTIVE": "true", **extra}

    def api(self, endpoint):
        if endpoint.endswith("/workflows/tests.yml"): return {"id": 7}
        if "/workflows/tests.yml/runs?" in endpoint: return {"total_count": len(self.runs), "workflow_runs": list(self.runs.values())}
        if "/git/commits/" in endpoint: return self.commits[endpoint.rsplit("/", 1)[-1]]
        if "/pulls/" in endpoint:
            return {"base": {"ref": "dev" if endpoint.endswith("/10") else "uat"}, "head": {"sha": "a" * 40 if endpoint.endswith("/10") else "d" * 40, "ref": "feature" if endpoint.endswith("/10") else "dev", "repo": {"full_name": self.repository}}}
        if "/artifacts?" in endpoint:
            number = int(endpoint.split("/runs/")[1].split("/")[0])
            artifacts = [{"id": number * 10 + index, "name": f"{prefix}-{number}-{self.runs[number]['run_attempt']}", "expired": False} for index, prefix in enumerate(self.artifacts[number])]
            return {"total_count": len(artifacts), "artifacts": artifacts}
        return self.runs[int(endpoint.rsplit("/", 1)[-1])]

    def pages(self, endpoint):
        if "/jobs?" in endpoint:
            number = int(endpoint.split("/runs/")[1].split("/")[0])
            return [{"jobs": self.jobs[number]}]
        return [self.api(endpoint)]

    def command(self, *args):
        if args[:2] == ("git", "rev-parse"):
            return self.tree if args[-1] == "HEAD^{tree}" else self.env()["GITHUB_SHA"]
        self.assertEqual(args[:3], ("gh", "run", "download"))
        number = int(args[3]);name = args[args.index("--name") + 1]
        prefix = name.rsplit("-", 2)[0]
        filename = {"ci-coordination-identity": "coordination-identity.json", "ci-coordination-decision": "coordination-decision.json"}.get(prefix, "tested-tree.json")
        (Path(args[args.index("--dir") + 1]) / filename).write_text(json.dumps(self.artifacts[number][prefix]))
        return ""

    @contextlib.contextmanager
    def server(self):
        with patch.object(policy, "api", side_effect=self.api), patch.object(policy, "pages", side_effect=self.pages), patch.object(policy, "command", side_effect=self.command), tempfile.TemporaryDirectory() as directory:
            previous = os.getcwd()
            try:
                os.chdir(directory)
                yield
            finally:
                os.chdir(previous)

    def test_simultaneous_dev_promotion_reuse_one_original_and_pin_receipt_owner(self):
        with self.server():
            first = policy.canonical_selection(self.env())
            self.assertEqual((first["mode"], first["source_run"]), ("reuse", "100"))
            self.publish(200, first)
            self.current = 201
            second = policy.canonical_selection(self.env())
            self.assertEqual((second["mode"], second["owner_run"], second["source_run"]), ("await", "200", "100"))
            self.publish(201, second)
            self.current = 200
            self.assertEqual(policy.reusable_run(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"), "poc"), "100")
            self.current = 201
            with patch.object(policy.time, "sleep", side_effect=lambda _: self.runs[200].update(status="completed", conclusion="success")):
                policy.await_canonical_owner(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"))
            self.assertEqual(policy.reusable_run(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"), "poc"), "100")

    def test_simultaneous_runs_without_usable_proof_execute_only_one_owner(self):
        self.artifacts[100].pop("validation-tree")
        with self.server():
            first = policy.canonical_selection(self.env())
            self.assertEqual((first["mode"], first["source_run"]), ("execute", "200"))
            self.publish(200, first)
            self.current = 201
            second = policy.canonical_selection(self.env())
            self.assertEqual((second["mode"], second["owner_run"], second["source_run"]), ("await", "200", "200"))

    def test_ready_promotion_can_own_validation_for_earlier_queued_push(self):
        self.artifacts[100].pop("validation-tree")
        self.jobs[200][-1]["status"] = "queued"
        self.jobs[201][-1]["status"] = "in_progress"
        self.current = 201
        with self.server():
            first = policy.canonical_selection(self.env())
            self.assertEqual(first["mode"], "execute")
            self.publish(201, first)
            self.current = 200
            second = policy.canonical_selection(self.env())
            self.assertEqual((second["mode"], second["source_run"]), ("await", "201"))

    def test_missing_decision_from_started_or_completed_selector_is_not_no_owner(self):
        for status in ["in_progress", "completed"]:
            self.jobs[201][-1].update(status=status, conclusion="success" if status == "completed" else None)
            with self.subTest(status=status), self.server(), self.assertRaises(policy.CoordinationRefusal):
                policy.canonical_selection(self.env())

    def test_consumer_waits_for_follower_decision_publication_before_source_revalidation(self):
        self.publish(200, self.decision(200, "reuse", 200, 100))
        self.jobs[201][-1]["status"] = "in_progress"
        with self.server(), patch.object(policy.time, "sleep", side_effect=lambda _: self.publish(201, self.decision(201, "await", 200, 100))):
            self.assertEqual(policy.reusable_run(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"), "poc"), "100")

    def test_failed_cancelled_skipped_owner_makes_consumer_fail_without_takeover(self):
        self.publish(200, self.decision(200, "execute", 200, 200))
        self.publish(201, self.decision(201, "await", 200, 200))
        self.current = 201
        for conclusion in ["failure", "cancelled", "skipped", "timed_out"]:
            self.runs[200].update(status="completed", conclusion=conclusion, updated_at=self.runs[201]["created_at"])
            with self.subTest(conclusion=conclusion), self.server():
                with self.assertRaisesRegex(policy.CoordinationRefusal, "owner_unsuccessful"):
                    policy.await_canonical_owner(self.env(SOURCE_RUN="200", SOURCE_ATTEMPT="1"))
                with self.assertRaisesRegex(policy.CoordinationRefusal, "no_takeover"):
                    policy.canonical_selection(self.env())

    def test_owner_attempt_change_and_pinned_source_change_are_refused(self):
        self.publish(200, self.decision(200, "execute", 200, 200))
        self.publish(201, self.decision(201, "await", 200, 200))
        self.current = 201
        with self.server():
            with self.assertRaisesRegex(policy.CoordinationRefusal, "source_pin_changed"):
                policy.await_canonical_owner(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"))
            self.runs[200]["run_attempt"] = 2
            with self.assertRaisesRegex(policy.CoordinationRefusal, "attempt_or_workflow_changed"):
                policy.await_canonical_owner(self.env(SOURCE_RUN="200", SOURCE_ATTEMPT="1"))

    def test_owner_wait_is_bounded_and_timeout_never_executes_tests(self):
        self.publish(200, self.decision(200, "execute", 200, 200))
        self.publish(201, self.decision(201, "await", 200, 200))
        self.current = 201
        with self.server(), patch.object(policy.time, "monotonic", side_effect=[0, 511]), self.assertRaisesRegex(policy.CoordinationRefusal, "wait_timeout"):
            policy.await_canonical_owner(self.env(SOURCE_RUN="200", SOURCE_ATTEMPT="1"))
        self.assertNotIn("validation-tree", self.artifacts[201])

    def test_waiter_cannot_target_another_waiter_or_a_different_tree(self):
        self.publish(200, self.decision(200, "await", 201, 100))
        self.publish(201, self.decision(201, "await", 200, 100))
        self.current = 201
        with self.server(), self.assertRaisesRegex(policy.CoordinationRefusal, "cycle_mismatch"):
            policy.await_canonical_owner(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"))

    def test_terminal_failed_follower_still_blocks_original_proof(self):
        self.publish(200, self.decision(200, "reuse", 200, 100))
        self.publish(201, self.decision(201, "await", 200, 100))
        self.runs[201].update(status="completed", conclusion="failure")
        with self.server(), self.assertRaisesRegex(policy.CoordinationRefusal, "newer_matching_run_blocks_reuse"):
            policy.reusable_run(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"), "poc")

    def test_unknown_matching_executing_peer_never_becomes_a_second_owner(self):
        self.artifacts[201].clear()
        self.jobs[201] = [self.job("POC PHP safety and static checks")]
        for usable in [True, False]:
            if not usable:
                self.artifacts[100].pop("validation-tree")
            with self.subTest(usable_source=usable), self.server(), self.assertRaisesRegex(policy.CoordinationRefusal, "unknown_matching_pending_peer"):
                policy.canonical_selection(self.env())
            self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_follower_failure_cancellation_and_attempt_change_during_source_verification_refuse(self):
        self.publish(200, self.decision(200, "reuse", 200, 100))
        self.publish(201, self.decision(201, "await", 200, 100))
        original = policy.validate_source
        for transition in ["failure", "cancelled", "attempt"]:
            self.runs[201].update(status="in_progress", conclusion=None, run_attempt=1)
            def change_after_source(*args):
                proof = original(*args)
                if transition == "attempt":
                    self.runs[201]["run_attempt"] = 2
                else:
                    self.runs[201].update(status="completed", conclusion=transition)
                return proof
            with self.subTest(transition=transition), self.server(), patch.object(policy, "validate_source", side_effect=change_after_source), self.assertRaises(policy.CoordinationRefusal):
                policy.reusable_run(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"), "poc")

    def test_selector_waits_bounded_for_independent_preflight_to_finish(self):
        self.jobs[201][0].update(status="in_progress", conclusion=None)
        with self.server(), patch.object(policy.time, "sleep", side_effect=lambda _: self.jobs[201][0].update(status="completed", conclusion="success")):
            self.assertEqual(policy.canonical_selection(self.env())["mode"], "reuse")

    def test_receipt_owner_failure_cannot_be_hidden_by_original_source_success(self):
        self.publish(200, self.decision(200, "reuse", 200, 100))
        self.publish(201, self.decision(201, "await", 200, 100))
        self.runs[200].update(status="completed", conclusion="failure")
        self.current = 201
        with self.server(), self.assertRaisesRegex(policy.CoordinationRefusal, "owner_unsuccessful"):
            policy.await_canonical_owner(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"))

    def test_future_number_owner_produces_original_proof_and_consumer_receipt(self):
        self.publish(201, self.decision(201, "execute", 201, 201))
        self.complete_validation(201)
        self.publish(200, self.decision(200, "await", 201, 201))
        self.current = 200
        with self.server():
            env = self.env(SOURCE_RUN="201", SOURCE_ATTEMPT="1")
            policy.await_canonical_owner(env)
            policy.record_reuse(env)
            receipt = json.loads(Path("ci-evidence/tested-tree.json").read_text())
            self.assertEqual((receipt["source_run"], receipt["canonical_owner_run"]), ("201", "201"))
            self.assertEqual(receipt["coordination_protocol"], 1)

    def test_malformed_decision_pins_and_cycles_refuse_ownership(self):
        original = copy.deepcopy(self.artifacts)
        for field, value in [("tree_sha", "f" * 40), ("protocol", 2), ("owner_attempt", "0"), ("source_run", ""), ("owner_run", "201")]:
            self.publish(201, self.decision(201, "await", 200, 100))
            self.artifacts[201]["ci-coordination-decision"][field] = value
            with self.subTest(field=field), self.server(), self.assertRaises(policy.CoordinationRefusal):
                policy.canonical_selection(self.env())
            self.artifacts = copy.deepcopy(original)

    def test_protocol_scope_native_tree_parents_and_publication_provenance_controls(self):
        original = copy.deepcopy((self.artifacts, self.commits, self.jobs))
        for scenario in ["scope", "protocol", "attempt", "repository", "native tree", "native parents", "failed identity upload", "malformed identity"]:
            identity = self.artifacts[201]["ci-coordination-identity"]
            if scenario == "scope": identity["scope"] = "full"
            if scenario == "protocol": identity["protocol"] = 2
            if scenario == "attempt": identity["run_attempt"] = "2"
            if scenario == "repository": identity["repository"] = "fork/repo"
            if scenario == "native tree": self.commits["e" * 40]["tree"]["sha"] = "f" * 40
            if scenario == "native parents": self.commits["e" * 40]["parents"].reverse()
            if scenario == "failed identity upload": self.jobs[201][0]["steps"][-1]["conclusion"] = "failure"
            if scenario == "malformed identity": self.artifacts[201]["ci-coordination-identity"] = []
            with self.subTest(scenario=scenario), self.server(), self.assertRaises((ValueError, TypeError)):
                policy.canonical_selection(self.env())
            self.artifacts, self.commits, self.jobs = copy.deepcopy(original)

    def test_refusal_diagnostics_do_not_emit_api_error_text_or_secrets(self):
        output = io.StringIO()
        with contextlib.redirect_stderr(output):
            policy.diagnostic("source_proof_not_usable", self.runs[100])
        self.assertEqual(output.getvalue(), "CI coordination: source_proof_not_usable run=100 attempt=1\n")

    def test_budget_includes_identity_and_consumer_wait_execution(self):
        jobs = [self.job(name) for name in [policy.IDENTITY_JOB, *policy.REUSE_JOBS]]
        self.assertEqual(budget.measure(jobs), (60, 20, 80))
        jobs[-1]["completed_at"] = "2026-10-07T09:09:40Z"
        with self.assertRaises(ValueError): policy.validate_completed_budget(jobs, reused=True)


class WorkflowQueueCompatibilityTests(unittest.TestCase):
    def test_only_documented_queue_nodes_can_resolve_old_actionlint_diagnostic(self):
        path = Path('.github/workflows/tests.yml')
        lines = path.read_text().splitlines()
        numbers = [index + 1 for index, line in enumerate(lines) if line.strip() == 'queue: max']
        self.assertEqual(len(numbers), 2)
        errors = [{"filepath": str(path), "line": number, "kind": "syntax-check", "message": lint.QUEUE_ERROR} for number in numbers]
        self.assertEqual(lint.remaining_errors(json.dumps(errors), 1, path), [])
        for scenario in ["other diagnostic", "other file", "wrong position", "other error kind"]:
            error = dict(errors[0])
            if scenario == "other diagnostic": error['message'] = 'invalid expression or unknown action'
            if scenario == "other file": error['filepath'] = '.github/workflows/deploy-prod.yml'
            if scenario == "wrong position": error['line'] = 1
            if scenario == "other error kind": error['kind'] = 'expression'
            with self.subTest(scenario=scenario):
                self.assertEqual(lint.remaining_errors(json.dumps([error]), 1, path), [error])

    def test_canceling_invalid_value_and_unexpected_context_are_never_accepted(self):
        for block in ['concurrency:\n  group: x\n  cancel-in-progress: true\n  queue: max',
                      'concurrency:\n  group: x\n  cancel-in-progress: false\n  queue: invalid',
                      'jobs:\n  unrelated:\n    concurrency:\n      group: x\n      cancel-in-progress: false\n      queue: max']:
            with self.subTest(block=block):
                self.assertFalse(lint.valid_queue_node(block.splitlines(), len(block.splitlines())))

    def test_malformed_output_or_tool_failure_cannot_become_lint_success(self):
        for output, code in [('not json', 1), ('{}', 1), ('[]', 1), ('[]', 2), ('[{}]', 1)]:
            with self.subTest(output=output, code=code), self.assertRaises(ValueError):
                lint.remaining_errors(output, code, Path('.github/workflows/tests.yml'))


if __name__ == "__main__":
    unittest.main()
