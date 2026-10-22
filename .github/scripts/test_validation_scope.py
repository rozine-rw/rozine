"""Negative controls for POC selection and immutable validation evidence."""

import copy
import contextlib
import datetime
import importlib.util
import io
import json
import os
import subprocess
import threading
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


class ApiFailureDiagnosticTests(unittest.TestCase):
    def test_api_failure_reports_only_bounded_status_and_preserves_original_exception(self):
        for status in (401, 403, 429, 503, None):
            secret = "Authorization: Bearer ghp_private_secret https://private.example/secret-endpoint"
            stderr = secret + (f" (HTTP {status})" if status else " transport failed") + "x" * 10000
            error = subprocess.CalledProcessError(7, ["gh", "api", "private-endpoint"], stderr=stderr)
            output = io.StringIO()
            stdout = io.StringIO()
            with self.subTest(status=status), patch.object(policy.subprocess, "check_output", side_effect=error) as call, contextlib.redirect_stderr(output), contextlib.redirect_stdout(stdout), self.assertRaises(subprocess.CalledProcessError) as caught:
                policy.command("gh", "api", "private-endpoint")
            self.assertIs(caught.exception, error)
            self.assertEqual(caught.exception.returncode, 7)
            call.assert_called_once()
            self.assertEqual(output.getvalue(), "CI coordination: gh_api_failed " + json.dumps({
                "classification": "http_error" if status else "unclassified", "http_status": status, "returncode": 7,
            }, sort_keys=True) + "\n")
            self.assertLess(len(output.getvalue()), 160)
            self.assertNotIn(secret, output.getvalue())
            self.assertEqual(stdout.getvalue(), "")

    def test_successful_command_is_unchanged_and_silent(self):
        output = io.StringIO()
        with patch.object(policy.subprocess, "check_output", return_value="  success\n") as call, contextlib.redirect_stderr(output):
            self.assertEqual(policy.command("gh", "api", "private-endpoint", timeout=12), "success")
        call.assert_called_once_with(("gh", "api", "private-endpoint"), text=True, stderr=subprocess.PIPE, timeout=12)
        self.assertEqual(output.getvalue(), "")

    def test_other_command_failures_remain_unchanged_and_silent(self):
        error = subprocess.CalledProcessError(3, ["gh", "run", "download"], stderr="private secret")
        output = io.StringIO()
        with patch.object(policy.subprocess, "check_output", side_effect=error), contextlib.redirect_stderr(output), self.assertRaises(subprocess.CalledProcessError) as caught:
            policy.command("gh", "run", "download")
        self.assertIs(caught.exception, error)
        self.assertEqual(output.getvalue(), "")

    def test_unavailable_diagnostic_output_does_not_replace_api_failure(self):
        for stderr in (None, b"secret bytes", "secret text"):
            for failure in (OSError("unavailable"), ValueError("closed")):
                error = subprocess.CalledProcessError(2, ["gh", "api"], stderr=stderr)
                with self.subTest(stderr=stderr, failure=failure), patch.object(policy.subprocess, "check_output", side_effect=error), patch.object(policy, "diagnostic", side_effect=failure), self.assertRaises(subprocess.CalledProcessError) as caught:
                    policy.command("gh", "api", "private-endpoint")
                self.assertIs(caught.exception, error)


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

    def test_poc_catalog_covers_audit_completion_and_monthly_dispatch(self):
        manifest = json.loads(Path("config/poc-test-manifest.json").read_text())
        self.assertIn("tests/Feature/AuditAssignmentCompletionTest.php", runner.selected_paths(manifest, "php"))
        self.assertIn("tests/Feature/MonthlyAuditSchedulerTest.php", runner.selected_paths(manifest, "php"))

    def test_poc_catalog_retains_staff_approval_boundaries(self):
        manifest = json.loads(Path("config/poc-test-manifest.json").read_text())
        paths = runner.selected_paths(manifest, "php")
        for path in ["tests/Feature/StaffSuperadminApprovalTest.php", "tests/Feature/StaffDirectoryTest.php",
                     "tests/Feature/StaffAccessTest.php", "tests/Concurrency/StaffSuperadminApprovalConcurrencyTest.php"]:
            with self.subTest(path=path):
                self.assertIn(path, paths)

    def test_poc_transactional_features_precede_committed_concurrency_fixtures(self):
        manifest = json.loads(Path("config/poc-test-manifest.json").read_text())
        paths = runner.selected_paths(manifest, "php")
        features = [index for index, path in enumerate(paths) if path.startswith("tests/Feature/")]
        concurrency = [index for index, path in enumerate(paths) if path.startswith("tests/Concurrency/")]
        self.assertLess(max(features), min(concurrency))

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
        def api(endpoint, *, timeout=45):
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

            def api(endpoint, *, timeout=45):
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
            def api(endpoint, *, timeout=45):
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
                def api(endpoint, *, timeout=45):
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
        def api(endpoint, *, timeout=45):
            return (last if endpoint.endswith("&page=2") else first) if "/runs?" in endpoint else {"id": 7}
        with patch.object(policy, "command", side_effect=["f" * 40, "c" * 40]), patch.object(policy, "api", side_effect=api) as reads, patch.object(policy, "validate_source", return_value=self.proof), patch.object(policy, "newer_run_blocks", wraps=policy.newer_run_blocks) as blocks:
            self.assertEqual(policy.reusable_run(env, "full"), "")
            self.assertTrue(any(call.args[1]["id"] == 77 for call in blocks.call_args_list))
            self.assertTrue(any(call.args[0].endswith("&page=2") for call in reads.call_args_list))
        for scenario in ["missing page", "duplicate IDs", "changed count", "bound exceeded"]:
            responses = copy.deepcopy([first, last])
            first_response = copy.deepcopy(first)
            if scenario == "missing page": responses = [first]
            if scenario == "duplicate IDs": responses[-1]["workflow_runs"] = [first_runs[0]]
            if scenario == "changed count": responses[-1]["total_count"] = 102
            if scenario == "bound exceeded": first_response["total_count"] = 5001
            def read(endpoint, *, timeout=45):
                return responses[-1] if endpoint.endswith("&page=2") and len(responses) > 1 else first_response
            with self.subTest(scenario=scenario), patch.object(policy, "api", side_effect=read), patch.object(policy.time, "sleep"):
                with self.assertRaises(ValueError): policy.workflow_history("rozine-rw/rozine")

    def test_source_attempt_is_pinned_and_changed_attempt_refuses_receipt(self):
        env = {"GITHUB_REPOSITORY": "rozine-rw/rozine", "GITHUB_SHA": "f" * 40, "SOURCE_RUN": "99", "SOURCE_ATTEMPT": "2"}
        for conclusion in ["success", "failure", None]:
            changed = dict(self.run, run_attempt=3, conclusion=conclusion)
            def api(endpoint, *, timeout=45):
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

    def test_failed_rerun_seen_by_anchor_cannot_reuse_the_initial_success(self):
        for changed_id in [99, 100]:
            first = {"total_count": 101, "workflow_runs": [dict(self.run, id=value) for value in range(99, 199)]}
            last = {"total_count": 101, "workflow_runs": [dict(self.run, id=199)]}
            fresh = copy.deepcopy(first)
            fresh["workflow_runs"][changed_id - 99].update(run_attempt=3, conclusion="failure", updated_at="2099-01-01T00:00:00Z")
            responses = iter([first, last, fresh, fresh, last, fresh])
            env = {"GITHUB_REPOSITORY": "rozine-rw/rozine", "GITHUB_SHA": "f" * 40, "SOURCE_RUN": "99", "SOURCE_ATTEMPT": "2"}
            def api(endpoint, *, timeout=45):
                return next(responses) if "/runs?" in endpoint else {"id": 7}
            with self.subTest(changed_id=changed_id), patch.object(policy, "command", side_effect=["f" * 40, "c" * 40]), patch.object(policy, "api", side_effect=api) as reads, patch.object(policy, "validate_source", return_value=self.proof) as validate, patch.object(policy.time, "sleep") as sleep:
                self.assertEqual(policy.reusable_run(env, "full"), "")
                self.assertEqual(sum("/runs?" in call.args[0] for call in reads.call_args_list), 6)
                sleep.assert_called_once_with(1)
                if changed_id == 99:
                    validate.assert_not_called()
                else:
                    validate.assert_called_once()

    def test_reuse_budget_includes_selection_board_and_receipt(self):
        jobs = [{"name": name, "status": "completed", "conclusion": "success", "started_at": "2026-10-06T11:00:00Z", "completed_at": "2026-10-06T11:00:20Z"}
                for name in ["Select CI scope", "Board sync offline tests", "Reuse POC validation evidence"]]
        self.assertEqual(budget.measure(jobs), (40, 20, 60))
        jobs[-1]["conclusion"] = "cancelled"
        with self.assertRaises(ValueError): budget.measure(jobs)


class WorkflowHistoryTests(unittest.TestCase):
    @staticmethod
    def page(number, total=101, offset=0):
        return {"total_count": total, "workflow_runs": [
            {"id": value + offset} for value in range((number - 1) * 100 + 1, min(number * 100, total) + 1)]}

    def setUp(self):
        self.first = self.page(1)
        self.last = self.page(2)

    def read(self, endpoint, *, timeout=45):
        return self.last if endpoint.endswith("&page=2") else self.first

    def test_stable_empty_single_and_multi_page_histories_need_no_retry(self):
        for total in [0, 2, 100, 101, 200, 5000]:
            def read(endpoint, *, timeout=45):
                number = int(endpoint.rsplit("&page=", 1)[1]) if "&page=" in endpoint else 1
                return self.page(number, total)
            with self.subTest(total=total), patch.object(policy, "api", side_effect=read) as api, patch.object(policy, "pages") as paginated, patch.object(policy.time, "sleep") as sleep:
                self.assertEqual(policy.workflow_history("rozine-rw/rozine"), [{"id": value} for value in range(1, total + 1)])
                expected = 1 if total <= 100 else (total + 99) // 100 + 1
                self.assertEqual(api.call_count, expected)
                self.assertTrue(all("?per_page=100" in call.args[0] and not any(filter in call.args[0] for filter in ["status=", "created=", "head_sha=", "branch="]) for call in api.call_args_list))
                paginated.assert_not_called()
                sleep.assert_not_called()

    def test_unstable_pages_are_discarded_before_a_complete_fresh_read(self):
        for scenario in ["missing page", "duplicate IDs", "changed count", "insertion between pages", "stable-count replacement"]:
            first, last, anchor = copy.deepcopy(self.first), copy.deepcopy(self.last), copy.deepcopy(self.first)
            if scenario == "missing page": last["workflow_runs"] = []
            if scenario == "duplicate IDs": last["workflow_runs"] = [{"id": 1}]
            if scenario == "changed count": last["total_count"] = 102
            if scenario == "insertion between pages": anchor["total_count"] = 102
            if scenario == "stable-count replacement": anchor["workflow_runs"][0] = {"id": 999}
            responses = [first, last]
            if scenario in ("insertion between pages", "stable-count replacement"): responses.append(anchor)
            # New complete snapshot must be returned; none of the old page IDs may leak.
            fresh = [self.page(1, offset=200), self.page(2, offset=200), self.page(1, offset=200)]
            with self.subTest(scenario=scenario), patch.object(policy, "api", side_effect=responses + fresh) as api, patch.object(policy.time, "sleep") as sleep:
                self.assertEqual(policy.workflow_history("rozine-rw/rozine"), [{"id": value} for value in range(201, 302)])
                self.assertEqual(api.call_count, len(responses) + 3)
                sleep.assert_called_once_with(1)

    def test_persistent_instability_stops_after_three_complete_reads(self):
        for scenario in ["missing page", "duplicate IDs", "changed count", "first page drift"]:
            last = copy.deepcopy(self.last)
            if scenario == "missing page": last["workflow_runs"] = []
            if scenario == "duplicate IDs": last["workflow_runs"] = [{"id": 1}]
            if scenario == "changed count": last["total_count"] = 102
            responses = [self.first, last]
            if scenario == "first page drift": responses.append(self.page(1, offset=1000))
            with self.subTest(scenario=scenario), patch.object(policy, "api", side_effect=responses * 3) as api, patch.object(policy.time, "sleep") as sleep, self.assertRaisesRegex(policy.CoordinationRefusal, "workflow_history_incomplete_or_changed"):
                policy.workflow_history("rozine-rw/rozine")
            self.assertEqual((api.call_count, sleep.call_count), (len(responses) * 3, 2))

    def test_anchor_metadata_drift_discards_the_whole_snapshot(self):
        metadata = {"run_attempt": 2, "status": "completed", "conclusion": "success",
                    "created_at": "2026-10-12T11:00:00Z", "run_started_at": "2026-10-12T11:01:00Z", "updated_at": "2026-10-12T11:02:00Z",
                    "event": "pull_request", "workflow_id": 7, "path": ".github/workflows/tests.yml",
                    "head_sha": "a" * 40, "head_branch": "dev", "head_repository": {"full_name": "rozine-rw/rozine"},
                    "pull_requests": [{"number": 310}], "actor": {"login": "reviewer"}}
        mutations = {"run_attempt": 3, "status": "in_progress", "conclusion": "failure",
                     "created_at": "2026-10-13T11:00:00Z", "run_started_at": "2026-10-13T11:01:00Z", "updated_at": "2026-10-13T11:02:00Z",
                     "event": "push", "workflow_id": 8, "path": ".github/workflows/other.yml",
                     "head_sha": "b" * 40, "head_branch": "uat", "head_repository": {"full_name": "fork/repo"},
                     "pull_requests": [{"number": 309}], "actor": {"login": "another"}}
        for key, value in mutations.items():
            first = copy.deepcopy(self.first)
            first["workflow_runs"][0].update(metadata)
            fresh = copy.deepcopy(first)
            fresh["workflow_runs"][0][key] = value
            with self.subTest(key=key), patch.object(policy, "api", side_effect=[first, self.last, fresh, fresh, self.last, fresh]) as api, patch.object(policy.time, "sleep") as sleep:
                self.assertEqual(policy.workflow_history("rozine-rw/rozine"), fresh["workflow_runs"] + self.last["workflow_runs"])
                self.assertEqual(api.call_count, 6)
                sleep.assert_called_once_with(1)

    def test_persistent_metadata_drift_refuses_after_three_whole_snapshots(self):
        first = copy.deepcopy(self.first)
        first["workflow_runs"][0].update(run_attempt=2, conclusion="success")
        anchor = copy.deepcopy(first)
        anchor["workflow_runs"][0].update(run_attempt=3, conclusion="failure")
        with patch.object(policy, "api", side_effect=[first, self.last, anchor] * 3) as api, patch.object(policy.time, "sleep") as sleep, self.assertRaisesRegex(policy.CoordinationRefusal, "workflow_history_incomplete_or_changed"):
            policy.workflow_history("rozine-rw/rozine")
        self.assertEqual((api.call_count, sleep.call_count), (9, 2))

    def test_metadata_retry_cannot_reset_the_shared_deadline(self):
        clock = [0]
        timeouts = []
        first = copy.deepcopy(self.first)
        first["workflow_runs"][0].update(run_attempt=2, conclusion="success")
        anchor = copy.deepcopy(first)
        anchor["workflow_runs"][0].update(run_attempt=3, conclusion="failure")
        responses = iter([first, self.last, anchor])
        def read(endpoint, *, timeout=45):
            timeouts.append(timeout)
            clock[0] += 15
            return next(responses)
        with patch.object(policy.time, "monotonic", side_effect=lambda: clock[0]), patch.object(policy, "api", side_effect=read), patch.object(policy.time, "sleep") as sleep, self.assertRaisesRegex(policy.CoordinationRefusal, "workflow_history_incomplete_or_changed"):
            policy.workflow_history("rozine-rw/rozine")
        self.assertEqual(timeouts, [45, 30, 15])
        sleep.assert_not_called()

    def test_one_deadline_bounds_page_and_anchor_reads_and_refuses_a_late_complete_read(self):
        for late in [False, True]:
            clock = [0]
            calls = []
            def read(endpoint, *, timeout=45):
                calls.append(timeout)
                clock[0] += 20 if len(calls) <= 2 else (6 if late else 0)
                return self.read(endpoint)
            with self.subTest(late=late), patch.object(policy.time, "monotonic", side_effect=lambda: clock[0]), patch.object(policy, "api", side_effect=read), patch.object(policy.time, "sleep") as sleep:
                if late:
                    with self.assertRaisesRegex(policy.CoordinationRefusal, "workflow_history_incomplete_or_changed"):
                        policy.workflow_history("rozine-rw/rozine")
                else:
                    self.assertEqual(policy.workflow_history("rozine-rw/rozine"), self.first["workflow_runs"] + self.last["workflow_runs"])
            self.assertEqual(calls, [45, 25, 5])
            sleep.assert_not_called()

    def test_retry_uses_remaining_time_instead_of_restarting_the_deadline(self):
        clock = [0]
        calls = []
        responses = [self.first, dict(self.last, workflow_runs=[]), self.first, self.last, self.first]
        def read(endpoint, *, timeout=45):
            calls.append(timeout)
            clock[0] += [5, 5, 15, 5, 1][len(calls) - 1]
            return responses[len(calls) - 1]
        def sleep(seconds): clock[0] += seconds
        with patch.object(policy.time, "monotonic", side_effect=lambda: clock[0]), patch.object(policy, "api", side_effect=read), patch.object(policy.time, "sleep", side_effect=sleep) as wait:
            self.assertEqual(policy.workflow_history("rozine-rw/rozine"), self.first["workflow_runs"] + self.last["workflow_runs"])
        self.assertEqual(calls, [45, 40, 34, 19, 14])
        wait.assert_called_once_with(1)

    def test_an_incomplete_late_read_cannot_start_another_snapshot(self):
        clock = [0]
        calls = []
        def read(endpoint, *, timeout=45):
            calls.append(timeout)
            clock[0] += 23
            return dict(self.read(endpoint), workflow_runs=[])
        with patch.object(policy.time, "monotonic", side_effect=lambda: clock[0]), patch.object(policy, "api", side_effect=read), patch.object(policy.time, "sleep") as sleep, self.assertRaisesRegex(policy.CoordinationRefusal, "workflow_history_incomplete_or_changed"):
            policy.workflow_history("rozine-rw/rozine")
        self.assertEqual(calls, [45, 22])
        sleep.assert_not_called()

    def test_hard_errors_in_first_page_later_page_or_anchor_are_never_retried(self):
        for position in [1, 2, 3]:
            for error in [subprocess.CalledProcessError(1, "gh api", stderr="private API detail"), subprocess.TimeoutExpired("gh api", 45)]:
                calls = []
                def read(endpoint, *, timeout=45):
                    calls.append(endpoint)
                    if len(calls) == position: raise error
                    return self.read(endpoint)
                with self.subTest(position=position, error=error), patch.object(policy, "api", side_effect=read), patch.object(policy.time, "sleep") as sleep, self.assertRaises(subprocess.SubprocessError):
                    policy.workflow_history("rozine-rw/rozine")
                self.assertEqual(len(calls), position)
                sleep.assert_not_called()

    def test_invalid_totals_refuse_before_submitting_pages(self):
        for total in [-1, 5001, "2", None, True, False]:
            with self.subTest(total=total), patch.object(policy, "api", return_value=dict(self.first, total_count=total)) as api, patch.object(policy, "ThreadPoolExecutor") as readers, patch.object(policy.time, "sleep") as sleep, self.assertRaises(ValueError):
                policy.workflow_history("rozine-rw/rozine")
            api.assert_called_once()
            readers.assert_not_called()
            sleep.assert_not_called()

    def test_a_missing_id_is_a_hard_refusal_not_partial_history(self):
        for row in [{}, {"id": None}, {"id": True}, {"id": "101"}, {"id": 0}, {"id": -1}, None]:
            last = dict(self.last, workflow_runs=[row])
            def read(endpoint, *, timeout=45):
                return last if endpoint.endswith("&page=2") else self.first
            with self.subTest(row=row), patch.object(policy, "api", side_effect=read) as api, patch.object(policy.time, "sleep") as sleep, self.assertRaisesRegex(policy.CoordinationRefusal, "workflow_history_invalid_run_identity"):
                policy.workflow_history("rozine-rw/rozine")
            self.assertEqual(api.call_count, 2)
            sleep.assert_not_called()

    def test_four_bounded_workers_collect_all_pages_in_order(self):
        lock, barrier = threading.Lock(), threading.Barrier(4)
        active = peak = 0
        calls = []
        def read(endpoint, *, timeout=45):
            nonlocal active, peak
            number = int(endpoint.rsplit("&page=", 1)[1]) if "&page=" in endpoint else 1
            with lock:
                active += 1
                peak = max(peak, active)
                calls.append((number, timeout))
            try:
                if 2 <= number <= 5: barrier.wait(timeout=2)
                return self.page(number, 1040)
            finally:
                with lock: active -= 1
        with patch.object(policy, "api", side_effect=read):
            rows = policy.workflow_history("rozine-rw/rozine")
        self.assertEqual(rows, [{"id": value} for value in range(1, 1041)])
        self.assertEqual(peak, 4)
        self.assertEqual(sorted(number for number, _ in calls), [1, 1, *range(2, 12)])
        self.assertTrue(all(0 < timeout <= 45 for _, timeout in calls))

    def test_every_run_field_is_preserved_for_provenance_and_failure_checks(self):
        rows = [
            {"id": number, "run_attempt": 3, "status": "completed", "conclusion": "failure",
             "head_sha": "a" * 40, "created_at": "2026-10-06T00:00:00Z", "updated_at": "2026-10-13T00:00:00Z",
             "head_repository": {"full_name": "rozine-rw/rozine"}, "extra": {"immutable": [1, 2, 3]}}
            for number in range(1, 102)]
        def read(endpoint, *, timeout=45):
            return {"total_count": len(rows), "workflow_runs": copy.deepcopy(rows[100:] if endpoint.endswith("&page=2") else rows[:100])}
        with patch.object(policy, "api", side_effect=read):
            self.assertEqual(policy.workflow_history("rozine-rw/rozine"), rows)

    def test_out_of_order_completion_does_not_change_the_result_set_or_order(self):
        third = threading.Event()
        completed = []
        def read(endpoint, *, timeout=45):
            number = int(endpoint.rsplit("&page=", 1)[1]) if "&page=" in endpoint else 1
            if number == 2 and not third.wait(timeout=2): raise RuntimeError("pages were serialized")
            completed.append(number)
            if number == 3: third.set()
            return self.page(number, 301)
        with patch.object(policy, "api", side_effect=read):
            self.assertEqual(policy.workflow_history("rozine-rw/rozine"), [{"id": value} for value in range(1, 302)])
        self.assertLess(completed.index(3), completed.index(2))

    def test_reader_errors_and_collection_expiry_cancel_queued_reads(self):
        for error in [subprocess.CalledProcessError(1, "gh api"), TimeoutError("collection expired")]:
            with self.subTest(error=error), patch.object(policy, "api", return_value=self.page(1, 1040)) as api, patch.object(policy, "ThreadPoolExecutor") as factory, patch.object(policy, "as_completed", side_effect=error), patch.object(policy.time, "sleep") as sleep:
                readers = factory.return_value
                with self.assertRaises(subprocess.SubprocessError if isinstance(error, subprocess.SubprocessError) else policy.CoordinationRefusal):
                    policy.workflow_history("rozine-rw/rozine")
                factory.assert_called_once_with(max_workers=4)
                readers.shutdown.assert_called_once_with(wait=True, cancel_futures=True)
                api.assert_called_once()
                sleep.assert_not_called()

    def test_a_failed_worker_does_not_drain_the_remaining_request_queue(self):
        four_started, cancelled, release = threading.Event(), threading.Event(), threading.Event()
        lock = threading.Lock()
        started = []
        error = subprocess.CalledProcessError(1, "gh api", stderr="private API detail")
        original = policy.ThreadPoolExecutor
        class ObservedReaders(original):
            def submit(self, function, number):
                future = super().submit(function, number)
                if number == 2:
                    # Keep the failed worker occupied until the collector has
                    # cancelled its queue, so scheduling races cannot mask it.
                    future.add_done_callback(lambda finished: cancelled.wait(timeout=2))
                return future
            def shutdown(self, wait=True, *, cancel_futures=False):
                super().shutdown(wait=False, cancel_futures=cancel_futures)
                cancelled.set()
                release.set()
                super().shutdown(wait=wait)
        def read(endpoint, *, timeout=45):
            number = int(endpoint.rsplit("&page=", 1)[1]) if "&page=" in endpoint else 1
            if number == 1: return self.page(1, 1040)
            with lock:
                started.append(number)
                if len(started) == 4: four_started.set()
            if number == 2:
                if not four_started.wait(timeout=2): raise RuntimeError("reader concurrency missing")
                raise error
            if not release.wait(timeout=2): raise RuntimeError("worker cleanup missing")
            return self.page(number, 1040)
        with patch.object(policy, "api", side_effect=read), patch.object(policy, "ThreadPoolExecutor", ObservedReaders), patch.object(policy.time, "sleep") as sleep, self.assertRaises(subprocess.CalledProcessError):
            policy.workflow_history("rozine-rw/rozine")
        self.assertEqual(sorted(started), [2, 3, 4, 5])
        sleep.assert_not_called()


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
                def api(endpoint, *, timeout=45):
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
        def api(endpoint, *, timeout=45):
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
            def api(endpoint, *, timeout=45):
                return {"id": 7} if endpoint.endswith("/workflows/tests.yml") else self.api(endpoint)
            with self.subTest(matching=matching), patch.object(policy, "command", side_effect=command), patch.object(policy, "api", side_effect=api), patch.object(policy, "workflow_history", return_value=[self.run, self.source]), patch.object(policy, "validate_source"), patch.object(policy, "run_jobs", return_value=self.jobs):
                self.assertEqual(policy.reusable_run(env, "poc", with_attempt=True), ("", "") if matching else ("99", "2"))

    def test_preflight_mismatch_diagnostics_are_bounded_public_and_keep_the_refusal(self):
        with tempfile.TemporaryDirectory() as directory, contextlib.chdir(directory):
            root = Path(directory)
            pull = {"number": 326,
                    "head": {"sha": "e" * 40, "ref": "feature\n::error::" + "x" * 2000,
                             "repo": {"full_name": self.repository}},
                    "base": {"sha": "c" * 40, "ref": "dev", "repo": {"full_name": self.repository}},
                    "body": "PRIVATE_BODY_MUST_NOT_BE_LOGGED"}
            event = root / "event.json"
            event.write_text(json.dumps({"pull_request": pull, "token": "PRIVATE_EVENT_MUST_NOT_BE_LOGGED"}))
            env = {"GITHUB_EVENT_NAME": "pull_request", "PR_BASE": "dev", "PR_HEAD_REPOSITORY": self.repository,
                   "GITHUB_REPOSITORY": self.repository, "GITHUB_SHA": "b" * 40,
                   "GITHUB_RUN_ID": "LOCAL_MODELED_NOT_CI", "GITHUB_RUN_ATTEMPT": "0",
                   "GITHUB_EVENT_PATH": str(event), "GITHUB_OUTPUT": str(root / "output"),
                   "GH_TOKEN": "PRIVATE_TOKEN_MUST_NOT_BE_LOGGED"}

            def command(*args):
                if args == ("git", "rev-parse", "HEAD"): return "b" * 40
                if args == ("git", "rev-parse", "HEAD^{tree}"): return "f" * 40
                if args == ("git", "cat-file", "-p", "HEAD"):
                    return "tree " + "f" * 40 + "\nparent " + "d" * 40 + "\nparent " + "e" * 40 + "\n\nmerge"
                raise AssertionError(args)

            output = io.StringIO()
            with patch.object(policy, "command", side_effect=command), patch.dict(os.environ, env), contextlib.redirect_stderr(output):
                with self.assertRaises(policy.CoordinationRefusal) as refusal:
                    policy.record_coordination_identity(os.environ)
            self.assertEqual(str(refusal.exception), "preflight_original_pr_merge_mismatch")
            lines = output.getvalue().splitlines()
            self.assertEqual(len(lines), 1)
            self.assertLess(len(lines[0]), 8192)
            details = json.loads(lines[0].removeprefix("CI identity mismatch: "))
            self.assertEqual(details["checkout_parents"], ["d" * 40, "e" * 40])
            self.assertEqual(details["event_base_sha"], "c" * 40)
            self.assertEqual(details["event_head_sha"], "e" * 40)
            self.assertEqual(details["repository"], self.repository)
            self.assertEqual(len(details["event_head_ref"]), 256)
            self.assertNotIn("PRIVATE_", lines[0])
            self.assertFalse((root / "ci-coordination-identity").exists())

            broken = io.StringIO()
            broken.close()
            with patch.object(policy, "command", side_effect=command), contextlib.redirect_stderr(broken):
                with self.assertRaisesRegex(policy.CoordinationRefusal, "^preflight_original_pr_merge_mismatch$"):
                    policy.record_coordination_identity(env)

            pull["base"]["sha"] = "d" * 40
            event.write_text(json.dumps({"pull_request": pull}))
            with patch.object(policy, "command", side_effect=command), patch.object(policy, "identity_mismatch_diagnostic") as diagnostic:
                policy.record_coordination_identity(env)
                diagnostic.assert_not_called()
            self.assertTrue((root / "ci-coordination-identity/coordination-identity.json").exists())

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


class PreflightClassificationTests(unittest.TestCase):
    def setUp(self):
        self.repository = "rozine-rw/rozine"
        self.source = {"id": 99, "run_attempt": 2, "status": "completed", "conclusion": "success",
                       "created_at": datetime.datetime.now(datetime.timezone.utc).isoformat(), "event": "pull_request",
                       "head_sha": "a" * 40, "head_branch": "feature", "workflow_id": 7, "path": ".github/workflows/tests.yml",
                       "head_repository": {"full_name": self.repository}}
        self.run = dict(self.source, id=100, head_sha="e" * 40, conclusion="failure")
        self.proof = {"schema": 1, "protocol": 1, "kind": "poc-coordination-identity", "scope": "poc",
                      "repository": self.repository, "run_id": "100", "run_attempt": "2", "event": "pull_request",
                      "pull_number": "246", "tested_sha": "b" * 40, "tree_sha": "f" * 40,
                      "base_sha": "d" * 40, "head_sha": "e" * 40, "base_ref": "dev"}
        self.commit = {"sha": "b" * 40, "tree": {"sha": "f" * 40}, "parents": [{"sha": "d" * 40}, {"sha": "e" * 40}]}
        self.latest = copy.deepcopy(self.run)
        self.jobs = [{"name": policy.IDENTITY_JOB, "status": "completed", "conclusion": "success",
                      "steps": [{"name": name, "conclusion": "success"} for name in policy.IDENTITY_STEPS]}]
        self.artifacts = [{"id": 1, "name": "ci-coordination-identity-100-2", "expired": False}]
        self.proofs = {"ci-coordination-identity": self.proof}
        self.download_error = None

    def api(self, endpoint, *, timeout=45):
        if endpoint.endswith("/workflows/tests.yml"): return {"id": 7}
        if "/artifacts?" in endpoint: return {"total_count": len(self.artifacts), "artifacts": self.artifacts}
        if endpoint.endswith("/100"): return self.latest
        if endpoint.endswith("e" * 40): return {"tree": {"sha": "9" * 40}}
        if endpoint.endswith("b" * 40): return self.commit
        raise AssertionError(endpoint)

    def command(self, *args, **kwargs):
        if args[:2] == ("git", "rev-parse"): return "c" * 40 if args[-1] == "HEAD^{tree}" else "8" * 40
        self.assertEqual(args[:4], ("gh", "run", "download", "100"))
        if self.download_error: raise self.download_error
        prefix = args[args.index("--name") + 1].rsplit("-", 2)[0]
        filename = {"ci-coordination-identity": "coordination-identity.json", "pr-merge-identity": "merge-identity.json"}.get(prefix, "tested-tree.json")
        proof = self.proofs[prefix]
        (Path(args[args.index("--dir") + 1]) / filename).write_text(proof if isinstance(proof, str) else json.dumps(proof))
        return ""

    @contextlib.contextmanager
    def server(self):
        with patch.object(policy, "api", side_effect=self.api), patch.object(policy, "command", side_effect=self.command), patch.object(policy, "run_jobs", return_value=self.jobs), patch.object(policy.time, "sleep"):
            yield

    def classify(self):
        with self.server():
            return policy.newer_run_blocks(self.repository, self.run, self.source, "c" * 40)

    def test_only_verified_different_trees_exclude_each_newer_pr_conclusion(self):
        for conclusion in ["failure", "cancelled", "skipped", "timed_out", None]:
            for matching in [False, True]:
                self.run.update(conclusion=conclusion, status="in_progress" if conclusion is None else "completed")
                self.latest = copy.deepcopy(self.run)
                self.proof["tree_sha"] = self.commit["tree"]["sha"] = "c" * 40 if matching else "f" * 40
                with self.subTest(conclusion=conclusion, matching=matching): self.assertEqual(self.classify(), matching)

    def test_preflight_never_counts_as_successful_test_evidence(self):
        with self.assertRaises(ValueError):
            policy.validate_proof(self.proof, self.run, self.jobs, self.commit, "f" * 40, self.repository, "poc")

    def test_absent_expired_duplicate_or_unreadable_preflight_never_excludes(self):
        for scenario in ["absent", "expired", "duplicate", "malformed", "download failure"]:
            original = copy.deepcopy((self.artifacts, self.proof))
            if scenario == "absent": self.artifacts = []
            if scenario == "expired": self.artifacts[0]["expired"] = True
            if scenario == "duplicate": self.artifacts.append(dict(self.artifacts[0], id=2))
            if scenario == "malformed": self.proofs["ci-coordination-identity"] = "not json"
            if scenario == "download failure": self.download_error = subprocess.CalledProcessError(1, "gh")
            with self.subTest(scenario=scenario), self.assertRaises((ValueError, subprocess.SubprocessError)): self.classify()
            self.artifacts, self.proof = original
            self.proofs["ci-coordination-identity"] = self.proof
            self.download_error = None

    def test_present_invalid_higher_priority_proof_never_falls_back(self):
        for prefix in ["validation-tree", "pr-merge-identity"]:
            for scenario in ["binding", "expired", "duplicate", "malformed", "download failure"]:
                later = {"id": 2, "name": prefix + "-100-2", "expired": scenario == "expired"}
                self.artifacts.append(later)
                if scenario == "duplicate": self.artifacts.append(dict(later, id=3))
                self.proofs[prefix] = "not json" if scenario == "malformed" else {"schema": "invalid"}
                self.download_error = subprocess.CalledProcessError(1, "gh") if scenario == "download failure" else None
                with self.subTest(prefix=prefix, scenario=scenario), self.server(), patch.object(policy, "preflight_merge_identity") as fallback, self.assertRaises((ValueError, subprocess.SubprocessError)):
                    policy.newer_run_blocks(self.repository, self.run, self.source, "c" * 40)
                fallback.assert_not_called()
                self.artifacts = self.artifacts[:1]
                self.download_error = None

    def test_binding_native_commit_and_publication_controls_cannot_be_bypassed(self):
        for scenario in ["schema", "protocol", "kind", "scope", "repository", "run_id", "run_attempt", "event", "head_sha", "pull_number", "tested_sha", "tree_sha", "base_sha", "base_ref", "native sha", "native tree", "parents", "duplicate job", "duplicate step", "failed upload", "pending upload", "fork", "wrong workflow"]:
            original = copy.deepcopy((self.proof, self.commit, self.jobs, self.run, self.latest))
            if scenario in self.proof: self.proof[scenario] = "invalid"
            elif scenario == "native sha": self.commit["sha"] = "9" * 40
            elif scenario == "native tree": self.commit["tree"]["sha"] = "9" * 40
            elif scenario == "parents": self.commit["parents"].reverse()
            elif scenario == "duplicate job": self.jobs *= 2
            elif scenario == "duplicate step": self.jobs[0]["steps"].append(self.jobs[0]["steps"][0])
            elif scenario == "failed upload": self.jobs[0]["steps"][1]["conclusion"] = "failure"
            elif scenario == "pending upload": self.jobs[0]["steps"][1]["conclusion"] = None
            elif scenario == "fork": self.run["head_repository"]["full_name"] = "fork/repo"
            elif scenario == "wrong workflow": self.run["path"] = "other.yml"
            with self.subTest(scenario=scenario), self.assertRaises(ValueError): self.classify()
            self.proof, self.commit, self.jobs, self.run, self.latest = original
            self.proofs["ci-coordination-identity"] = self.proof

    def test_pending_preflight_publication_never_excludes_a_live_run(self):
        self.run.update(status="in_progress", conclusion=None)
        self.latest = copy.deepcopy(self.run)
        self.jobs[0].update(status="in_progress", conclusion=None)
        self.jobs[0]["steps"][1]["conclusion"] = None
        with self.assertRaises(policy.PreflightPublicationPending): self.classify()

    def test_preflight_preserves_nonnegative_thirty_day_age_bound(self):
        now = datetime.datetime.now(datetime.timezone.utc)
        for age in [1, 29, 31, -1]:
            self.run.update(created_at=(now - datetime.timedelta(days=age)).isoformat(), updated_at=now.isoformat())
            self.latest = copy.deepcopy(self.run)
            with self.subTest(age=age):
                if 0 <= age <= 30: self.assertFalse(self.classify())
                else:
                    with self.assertRaisesRegex(ValueError, "stale"): self.classify()

    def test_later_publication_refuses_preflight_classification(self):
        for prefix in ["validation-tree", "pr-merge-identity"]:
            reads = []
            def api(endpoint, **kwargs):
                if "/artifacts?" in endpoint:
                    reads.append(endpoint)
                    artifacts = self.artifacts + ([{"id": 2, "name": prefix + "-100-2", "expired": True}] if len(reads) == 4 else [])
                    return {"total_count": len(artifacts), "artifacts": artifacts}
                return self.api(endpoint, **kwargs)
            with self.subTest(prefix=prefix), self.server(), patch.object(policy, "api", side_effect=api), self.assertRaisesRegex(ValueError, "appeared during"):
                policy.newer_run_blocks(self.repository, self.run, self.source, "c" * 40)
            self.assertEqual(len(reads), 4)

    def test_incomplete_inventory_never_proves_absence_before_or_after_preflight(self):
        for read in [1, 2, 4]:
            calls = []
            def api(endpoint, **kwargs):
                if "/artifacts?" in endpoint:
                    calls.append(endpoint)
                    if len(calls) == read: return {"total_count": 2, "artifacts": self.artifacts}
                return self.api(endpoint, **kwargs)
            with self.subTest(read=read), self.server(), patch.object(policy, "api", side_effect=api), patch.object(policy, "pages", return_value=[{"total_count": 2, "artifacts": self.artifacts}]), self.assertRaisesRegex(ValueError, "incomplete or changed"):
                policy.newer_run_blocks(self.repository, self.run, self.source, "c" * 40)

    def test_final_attempt_check_catches_changes_after_inventory(self):
        for key, value in [("run_attempt", 3), ("head_sha", "a" * 40), ("event", "push"), ("workflow_id", 8), ("path", "other.yml"), ("head_repository", {"full_name": "fork/repo"})]:
            reads = []
            def api(endpoint, **kwargs):
                if endpoint.endswith("/100"):
                    reads.append(endpoint)
                    return dict(self.run, **{key: value}) if len(reads) == 2 else self.run
                return self.api(endpoint, **kwargs)
            with self.subTest(key=key), self.server(), patch.object(policy, "api", side_effect=api), self.assertRaisesRegex(policy.CoordinationRefusal, "run_or_attempt_changed"):
                policy.newer_run_blocks(self.repository, self.run, self.source, "c" * 40)
            self.assertEqual(len(reads), 2)

    def test_selector_reuses_only_when_preflight_proves_failed_pr_unrelated(self):
        env = {"GITHUB_REPOSITORY": self.repository, "GITHUB_SHA": "8" * 40, "SOURCE_RUN": "99"}
        for matching in [False, True]:
            self.proof["tree_sha"] = self.commit["tree"]["sha"] = "c" * 40 if matching else "f" * 40
            with self.subTest(matching=matching), self.server(), patch.object(policy, "workflow_history", return_value=[self.run, self.source]), patch.object(policy, "validate_source"):
                self.assertEqual(policy.reusable_run(env, "poc", with_attempt=True), ("", "") if matching else ("99", "2"))


class CanonicalCoordinationTests(unittest.TestCase):
    """A native API/artifact simulation; this does not simulate lock acquisition."""

    def setUp(self):
        getattr(policy, "_follower_publication_deadlines", {}).clear()
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
        self.jobs[200].append({"name": "Select CI scope", "status": "in_progress", "conclusion": None, "started_at": now.isoformat()})
        self.jobs[201].append({"name": "Select CI scope", "status": "queued", "conclusion": None, "started_at": now.isoformat()})
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
        job_id = "reuse-poc" if extra.get("SOURCE_RUN") else "plan"
        if job_id == "reuse-poc" and not any(job["name"] == "Reuse POC validation evidence" for job in self.jobs[number]):
            self.jobs[number].append({"name": "Reuse POC validation evidence", "status": "in_progress", "conclusion": None,
                "started_at": datetime.datetime.now(datetime.timezone.utc).isoformat()})
        return {"GITHUB_REPOSITORY": self.repository, "GITHUB_SHA": self.artifacts[number]["ci-coordination-identity"]["tested_sha"],
                "GITHUB_RUN_ID": str(number), "GITHUB_RUN_ATTEMPT": str(self.runs[number]["run_attempt"]), "COORDINATION_ACTIVE": "true", "GITHUB_JOB": job_id, **extra}

    def api(self, endpoint, *, timeout=45):
        if endpoint.endswith("/workflows/tests.yml"): return {"id": 7}
        if "/workflows/tests.yml/runs?" in endpoint: return {"total_count": len(self.runs), "workflow_runs": list(self.runs.values())}
        if "/git/commits/" in endpoint: return self.commits[endpoint.rsplit("/", 1)[-1]]
        if "/pulls/" in endpoint:
            return {"base": {"ref": "dev" if endpoint.endswith("/10") else "uat"}, "head": {"sha": "a" * 40 if endpoint.endswith("/10") else "d" * 40, "ref": "feature" if endpoint.endswith("/10") else "dev", "repo": {"full_name": self.repository}}}
        if endpoint.startswith(f"repos/{self.repository}/actions/artifacts?per_page=100"):
            artifacts = [{"id": number * 10 + index, "name": f"{prefix}-{number}-{self.runs[number]['run_attempt']}", "workflow_run": {"id": number}}
                         for number in self.artifacts for index, prefix in enumerate(self.artifacts[number])]
            page = int(endpoint.split("&page=", 1)[1]) if "&page=" in endpoint else 1
            return {"total_count": len(artifacts), "artifacts": artifacts[(page - 1) * 100:page * 100]}
        if "/artifacts?" in endpoint:
            number = int(endpoint.split("/runs/")[1].split("/")[0])
            artifacts = [{"id": number * 10 + index, "name": f"{prefix}-{number}-{self.runs[number]['run_attempt']}", "expired": False} for index, prefix in enumerate(self.artifacts[number])]
            return {"total_count": len(artifacts), "artifacts": artifacts}
        return self.runs[int(endpoint.rsplit("/", 1)[-1])]

    def pages(self, endpoint, *, timeout=45):
        if "/jobs?" in endpoint:
            number = int(endpoint.split("/runs/")[1].split("/")[0])
            return [{"jobs": self.jobs[number]}]
        return [self.api(endpoint)]

    def command(self, *args, timeout=45):
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

    def test_no_source_selection_keeps_two_used_history_reads_and_fresh_own_identity(self):
        self.artifacts[100].pop("validation-tree")
        with self.server(), patch.object(policy, "workflow_history", wraps=policy.workflow_history) as history, patch.object(policy, "current_coordination_identity", wraps=policy.current_coordination_identity) as own:
            decision = policy.canonical_selection(self.env())
            self.assertEqual((decision["mode"], decision["owner_run"]), ("execute", "200"))
            self.assertEqual(history.call_count, 2)
            self.assertEqual(own.call_count, 2)

    def test_pending_followers_verify_own_identity_without_reading_unused_history(self):
        with self.server(), patch.object(policy, "workflow_history", side_effect=AssertionError("unused history retrieval")), patch.object(policy, "coordination_identity", wraps=policy.coordination_identity) as identity:
            self.assertEqual(policy.pending_follower_ids(self.env(), [self.runs[201]], self.tree), {201})
            self.assertEqual(identity.call_args_list[0].args[1]["id"], 200)

    def test_each_used_history_api_failure_still_refuses_selection(self):
        for failing_read in [1, 2]:
            reads = 0
            def api(endpoint, **kwargs):
                nonlocal reads
                if "/workflows/tests.yml/runs?" in endpoint:
                    reads += 1
                    if reads == failing_read:
                        raise subprocess.CalledProcessError(1, "gh api")
                return copy.deepcopy(self.api(endpoint, **kwargs))
            with self.subTest(history_read=failing_read), self.server(), patch.object(policy, "api", side_effect=api), self.assertRaises(subprocess.CalledProcessError):
                policy.canonical_selection(self.env())
            self.assertEqual(reads, failing_read)

    def test_second_history_observes_new_matching_failures_and_published_owner(self):
        original_runs, original_jobs, original_artifacts = copy.deepcopy((self.runs, self.jobs, self.artifacts))
        for transition in ["failure", "cancelled", "skipped", "owner"]:
            self.runs, self.jobs, self.artifacts = copy.deepcopy((original_runs, original_jobs, original_artifacts))
            reads = 0
            def api(endpoint, **kwargs):
                nonlocal reads
                if "/workflows/tests.yml/runs?" in endpoint:
                    reads += 1
                    if reads == 2:
                        if transition == "owner":
                            self.publish(201, self.decision(201, "execute", 201, 201))
                        else:
                            self.runs[202] = dict(self.runs[200], id=202, status="completed", conclusion=transition)
                return copy.deepcopy(self.api(endpoint, **kwargs))
            with self.subTest(transition=transition), self.server(), patch.object(policy, "api", side_effect=api):
                with self.assertRaisesRegex(policy.CoordinationRefusal, "newer_matching_run_blocks_reuse"):
                    policy.canonical_selection(self.env())
                self.assertEqual(reads, 2)
                self.assertFalse(Path("ci-coordination-decision/coordination-decision.json").exists())

    def test_follower_own_identity_is_fresh_after_second_history(self):
        original_runs, original_artifacts = copy.deepcopy((self.runs, self.artifacts))
        for transition in ["attempt", "workflow", "tree"]:
            self.runs, self.artifacts = copy.deepcopy((original_runs, original_artifacts))
            env = self.env()
            reads = 0
            def api(endpoint, **kwargs):
                nonlocal reads
                if "/workflows/tests.yml/runs?" in endpoint:
                    reads += 1
                    if reads == 2:
                        if transition == "tree":
                            self.artifacts[200]["ci-coordination-identity"]["tree_sha"] = "f" * 40
                        else:
                            self.runs[200]["run_attempt" if transition == "attempt" else "workflow_id"] = 2 if transition == "attempt" else 999
                return copy.deepcopy(self.api(endpoint, **kwargs))
            with self.subTest(transition=transition), self.server(), patch.object(policy, "api", side_effect=api), self.assertRaises(policy.CoordinationRefusal):
                policy.canonical_selection(env)
            self.assertEqual(reads, 2)

    def test_pending_follower_own_attempt_change_during_native_verification_refuses(self):
        env = self.env()
        def api(endpoint, **kwargs):
            if endpoint.endswith("/git/commits/" + "d" * 40):
                self.runs[200]["run_attempt"] = 2
            return copy.deepcopy(self.api(endpoint, **kwargs))
        with self.server(), patch.object(policy, "api", side_effect=api), self.assertRaisesRegex(policy.CoordinationRefusal, "run_or_attempt_changed"):
            policy.pending_follower_ids(env, [self.runs[201]], self.tree)

    def test_current_attempt_change_before_decision_publication_refuses(self):
        original = policy.reusable_run
        def change_after_selection(*args, **kwargs):
            result = original(*args, **kwargs)
            self.runs[200]["run_attempt"] = 2
            return result
        with self.server(), patch.object(policy, "api", side_effect=lambda endpoint, **kwargs: copy.deepcopy(self.api(endpoint, **kwargs))), patch.object(policy, "reusable_run", side_effect=change_after_selection):
            with self.assertRaisesRegex(policy.CoordinationRefusal, "run_or_attempt_changed"):
                policy.canonical_selection(self.env())
            self.assertFalse(Path("ci-coordination-decision/coordination-decision.json").exists())

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

    @contextlib.contextmanager
    def publication_clock(self, advance):
        clock = [0]
        def sleep(seconds):
            self.assertGreater(seconds, 0)
            self.assertLessEqual(seconds, 3)
            clock[0] += seconds
            advance(clock[0])
        with self.server(), patch.object(policy, "api", side_effect=lambda endpoint, **kwargs: copy.deepcopy(self.api(endpoint, **kwargs))), patch.object(policy.time, "monotonic", side_effect=lambda: clock[0]), patch.object(policy.time, "sleep", side_effect=sleep):
            yield clock

    def test_consumer_accepts_verified_follower_publication_after_45_seconds_within_producer_cap(self):
        for delay in [48, 90, 297]:
            self.setUp()
            self.publish(200, self.decision(200, "reuse", 200, 100))
            self.jobs[201][-1]["status"] = "in_progress"
            def publish_after_delay(seconds):
                if seconds >= delay:
                    self.publish(201, self.decision(201, "await", 200, 100))
            with self.subTest(publication_seconds=delay), self.publication_clock(publish_after_delay) as clock, patch.object(policy, "validate_source", wraps=policy.validate_source) as verify:
                self.assertEqual(policy.reusable_run(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"), "poc"), "100")
                self.assertEqual(clock[0], delay)
                self.assertTrue(verify.called)
                self.assertEqual(self.artifacts[201]["ci-coordination-decision"]["source_run"], "100")
                self.assertNotIn("validation-tree", self.artifacts[200])
                self.assertNotIn("validation-tree", self.artifacts[201])

    def test_reuse_consumer_with_native_budget_still_refuses_at_300_second_wait_ceiling(self):
        self.publish(200, self.decision(200, "reuse", 200, 100))
        self.jobs[201][-1]["status"] = "in_progress"
        with self.publication_clock(lambda _: None) as clock, patch.object(policy, "validate_source", wraps=policy.validate_source) as verify:
            with self.assertRaisesRegex(policy.CoordinationRefusal, "follower_decision_publication_timeout"):
                policy.reusable_run(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"), "poc")
            self.assertEqual(clock[0], 300)
            verify.assert_not_called()
            self.assertNotIn("ci-coordination-decision", self.artifacts[201])
            self.assertFalse(Path("ci-evidence/tested-tree.json").exists())

    def test_selector_with_150_seconds_spent_refuses_60_seconds_before_native_cap(self):
        now = datetime.datetime.now(datetime.timezone.utc)
        self.jobs[200][-1]["started_at"] = (now - datetime.timedelta(seconds=150)).isoformat()
        self.jobs[201][-1]["status"] = "in_progress"
        with self.publication_clock(lambda _: None) as clock, patch.object(policy.datetime, "datetime", wraps=datetime.datetime) as dates, patch.object(policy, "validate_source", wraps=policy.validate_source) as verify:
            dates.now.return_value = now
            with self.assertRaisesRegex(policy.CoordinationRefusal, "follower_decision_publication_timeout"):
                policy.reusable_run(self.env(), "poc")
            self.assertEqual(clock[0], 90)
            self.assertEqual(150 + clock[0], 240)
            verify.assert_not_called()
            self.assertFalse(Path("ci-coordination-decision/coordination-decision.json").exists())

    def test_reuse_wait_uses_current_job_and_remaining_combined_budget(self):
        now = datetime.datetime.now(datetime.timezone.utc)
        def completed(name, seconds):
            return {"name": name, "status": "completed", "conclusion": "success", "started_at": (now - datetime.timedelta(seconds=1000)).isoformat(),
                "completed_at": (now - datetime.timedelta(seconds=1000 - seconds)).isoformat()}
        jobs = [completed(policy.IDENTITY_JOB, 10), completed("Select CI scope", 150),
            {"name": "Reuse POC validation evidence", "status": "in_progress", "started_at": (now - datetime.timedelta(seconds=120)).isoformat()}]
        with patch.object(policy, "run_jobs", return_value=jobs), patch.object(policy.time, "monotonic", return_value=0), patch.object(policy.datetime, "datetime", wraps=datetime.datetime) as dates:
            dates.now.return_value = now
            self.assertEqual(policy.follower_publication_deadline(self.env(SOURCE_RUN="100"), self.runs[200]), 260)

    def test_unverifiable_native_current_job_cannot_grant_a_fresh_wait(self):
        now = datetime.datetime.now(datetime.timezone.utc)
        job = {"name": "Select CI scope", "status": "in_progress", "started_at": (now + datetime.timedelta(seconds=30)).isoformat()}
        for jobs in ([], [job, job], [job], [{**job, "started_at": None}], [{**job, "status": "completed"}]):
            with self.subTest(jobs=jobs), patch.object(policy, "run_jobs", return_value=jobs), self.assertRaisesRegex(policy.CoordinationRefusal, "follower_publication_job_budget_unverifiable"):
                policy.follower_publication_deadline(self.env(), self.runs[200])

    def test_slow_successful_follower_read_after_deadline_is_not_accepted(self):
        self.publish(200, self.decision(200, "reuse", 200, 100))
        self.jobs[201][-1]["status"] = "in_progress"
        native = policy.pending_participants
        with self.publication_clock(lambda _: self.publish(201, self.decision(201, "await", 200, 100))) as clock:
            def slow_read(*args):
                result = native(*args)
                clock[0] = 301
                return result
            with patch.object(policy, "pending_participants", side_effect=slow_read), patch.object(policy, "validate_source", wraps=policy.validate_source) as verify:
                with self.assertRaisesRegex(policy.CoordinationRefusal, "follower_decision_publication_timeout"):
                    policy.reusable_run(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"), "poc")
                verify.assert_not_called()
        self.assertIsNone(policy._follower_read_deadline.get())

    def test_later_follower_recheck_does_not_restart_its_wait_allowance(self):
        self.publish(200, self.decision(200, "reuse", 200, 100))
        self.jobs[201][-1]["status"] = "in_progress"
        published = [False]
        def publish_once(seconds):
            if seconds >= 48 and not published[0]:
                self.publish(201, self.decision(201, "await", 200, 100))
        with self.publication_clock(publish_once) as clock:
            env = self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1")
            self.assertEqual(policy.reusable_run(env, "poc"), "100")
            published[0] = True
            self.artifacts[201].pop("ci-coordination-decision")
            self.jobs[201][-1].update(status="in_progress", conclusion=None)
            clock[0] = 150
            with self.assertRaisesRegex(policy.CoordinationRefusal, "follower_decision_publication_timeout"):
                policy.reusable_run(env, "poc")
            self.assertEqual(clock[0], 300)

    def test_follower_native_read_timeout_is_clipped_and_expires_closed(self):
        token = policy._follower_read_deadline.set(10)
        try:
            for late_success in (False, True):
                clock = [7]
                def read(*args, **kwargs):
                    self.assertEqual(kwargs["timeout"], 3)
                    clock[0] = 10
                    if late_success:
                        return "valid but late"
                    raise subprocess.TimeoutExpired("gh", kwargs["timeout"])
                with self.subTest(late_success=late_success), patch.object(policy.time, "monotonic", side_effect=lambda: clock[0]), patch.object(policy.subprocess, "check_output", side_effect=read), self.assertRaisesRegex(policy.CoordinationRefusal, "follower_decision_publication_timeout"):
                    policy.command("gh", "api", "native-evidence")
        finally:
            policy._follower_read_deadline.reset(token)

    def test_publication_wait_preserves_native_failure_attempt_and_decision_refusals(self):
        for transition in ["failure", "cancelled", "attempt", "malformed tree", "another owner", "another source"]:
            self.setUp()
            self.publish(200, self.decision(200, "reuse", 200, 100))
            self.jobs[201][-1]["status"] = "in_progress"
            def change_while_waiting(seconds):
                if seconds != 6:
                    return
                if transition in ("failure", "cancelled"):
                    self.runs[201].update(status="completed", conclusion=transition)
                    self.jobs[201][-1].update(status="completed", conclusion=transition)
                elif transition == "attempt":
                    self.runs[201]["run_attempt"] = 2
                else:
                    decision = self.decision(201, "execute", 201, 201) if transition == "another owner" else self.decision(201, "await", 200, 100)
                    if transition == "malformed tree": decision["tree_sha"] = "f" * 40
                    if transition == "another source": decision["source_run"] = "999"
                    self.publish(201, decision)
            with self.subTest(transition=transition), self.publication_clock(change_while_waiting) as clock:
                with self.assertRaises(policy.CoordinationRefusal):
                    policy.reusable_run(self.env(SOURCE_RUN="100", SOURCE_ATTEMPT="1"), "poc")
                self.assertEqual(clock[0], 6)
                self.assertFalse(Path("ci-evidence/tested-tree.json").exists())

    def test_consumer_timeout_cascades_into_peer_terminal_transition_without_takeover(self):
        self.publish(200, self.decision(200, "reuse", 200, 100))
        self.jobs[201][-1]["status"] = "in_progress"
        self.current = 201
        consumer = self.env(200, SOURCE_RUN="100", SOURCE_ATTEMPT="1")
        selector = self.env(201)
        native_decision = policy.coordination_decision
        refusals = []
        def consumer_finishes_before_peer_latest_read(repository, run, identity):
            decision = native_decision(repository, run, identity)
            if run["id"] == 200 and not refusals:
                refusals.append("consumer started")
                self.current = 200
                try:
                    policy.reusable_run(consumer, "poc")
                except policy.CoordinationRefusal as error:
                    refusals.append(str(error))
                finally:
                    self.current = 201
                self.runs[200].update(status="completed", conclusion="failure")
            return decision
        with self.publication_clock(lambda _: None) as clock, patch.object(policy, "coordination_decision", side_effect=consumer_finishes_before_peer_latest_read):
            with self.assertRaisesRegex(policy.CoordinationRefusal, "participant_became_terminal_during_selection"):
                policy.canonical_selection(selector)
            self.assertEqual(refusals, ["consumer started", "follower_decision_publication_timeout"])
            self.assertGreaterEqual(clock[0], 45)
            self.assertNotIn("ci-coordination-decision", self.artifacts[201])
            self.assertFalse(Path("ci-coordination-decision/coordination-decision.json").exists())

    def test_publication_deadlines_preserve_native_caps_and_combined_budget(self):
        lines = Path(__file__).resolve().parents[1].joinpath("workflows/tests.yml").read_text().splitlines()
        for job, minutes in [("plan", 5), ("reuse-poc", 10)]:
            start = lines.index(f"  {job}:")
            end = next((index for index in range(start + 1, len(lines)) if lines[index].startswith("  ") and not lines[index].startswith("   ") and lines[index].strip()), len(lines))
            self.assertIn(f"    timeout-minutes: {minutes}", lines[start:end])
        start_at = datetime.datetime(2026, 10, 16, tzinfo=datetime.timezone.utc)
        for seconds in [600, 601]:
            jobs = []
            for name in ["Select CI scope", *policy.POC_JOBS]:
                duration = seconds - 20 if name == "POC PHP safety and static checks" else (20 if name == "Select CI scope" else 1)
                jobs.append({"name": name, "status": "completed", "conclusion": "success", "started_at": start_at.isoformat(), "completed_at": (start_at + datetime.timedelta(seconds=duration)).isoformat()})
            with self.subTest(combined_seconds=seconds):
                if seconds == 600:
                    policy.validate_completed_budget(jobs)
                else:
                    with self.assertRaisesRegex(ValueError, "ten minutes"):
                        policy.validate_completed_budget(jobs)

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
        with self.server(), patch.object(policy.time, "monotonic", side_effect=[0, 0, 0, 0, 511]), self.assertRaisesRegex(policy.CoordinationRefusal, "wait_timeout"):
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

    def test_artifact_index_requires_complete_stable_unique_run_bound_inventory(self):
        artifacts = [{"id": n, "name": "ci-coordination-identity-200-1" if n == 1 else f"unrelated-{n}", "workflow_run": {"id": 200}} for n in range(1, 102)]
        for scenario in ["complete", "missing page", "changed count", "duplicate id", "duplicate identity", "invalid name"]:
            first = {"total_count": 101, "artifacts": copy.deepcopy(artifacts[:100])}
            second = {"total_count": 101, "artifacts": copy.deepcopy(artifacts[100:])}
            if scenario == "missing page": second["artifacts"] = []
            if scenario == "changed count": second["total_count"] = 102
            if scenario == "duplicate id": second["artifacts"][0]["id"] = 1
            if scenario == "duplicate identity": second["artifacts"][0]["name"] = artifacts[0]["name"]
            if scenario == "invalid name": second["artifacts"][0]["name"] = []
            def api(endpoint, **kwargs):
                return second if "&page=2" in endpoint else first
            with self.subTest(scenario=scenario), patch.object(policy, "api", side_effect=api), patch.object(policy.time, "sleep"):
                if scenario == "complete":
                    self.assertEqual(policy.coordination_artifact_index(self.repository), {artifacts[0]["name"]: 200})
                else:
                    with self.assertRaises(policy.CoordinationRefusal): policy.coordination_artifact_index(self.repository)

    def test_artifact_index_restarts_complete_read_after_concurrent_publication(self):
        old = [{"id": n, "name": f"ci-coordination-identity-{n}-1", "workflow_run": {"id": n}} for n in range(1, 3406)]
        new = [{"id": 3406, "name": "ci-coordination-identity-3406-1", "workflow_run": {"id": 3406}}, *old]
        for scenario in ["upload after first read", "upload between pages", "overlapping page IDs"]:
            base_reads = []
            def api(endpoint, **kwargs):
                if "&page=" not in endpoint:
                    base_reads.append(endpoint)
                    items = old if len(base_reads) == 1 else new
                    return {"total_count": len(items), "artifacts": items[:100]}
                page = int(endpoint.rsplit("=", 1)[-1])
                items = new if len(base_reads) > 1 or scenario == "upload after first read" else old
                response = {"total_count": len(items), "artifacts": copy.deepcopy(items[(page - 1) * 100:page * 100])}
                if len(base_reads) == 1 and scenario == "upload between pages" and page == 2:
                    response["total_count"] += 1
                if len(base_reads) == 1 and scenario == "overlapping page IDs" and page == 2:
                    response["artifacts"][0] = old[0]
                return response
            with self.subTest(scenario=scenario), patch.object(policy, "api", side_effect=api), patch.object(policy.time, "sleep"):
                self.assertEqual(policy.coordination_artifact_index(self.repository), {a["name"]: a["workflow_run"]["id"] for a in new})
                self.assertEqual(len(base_reads), 3)  # unstable snapshot, retry, fresh anchor

    def test_stable_single_page_inventory_needs_no_retry_or_pagination(self):
        artifact = {"id": 1, "name": "ci-coordination-identity-200-1", "workflow_run": {"id": 200}}
        with patch.object(policy, "api", return_value={"total_count": 1, "artifacts": [artifact]}) as api, patch.object(policy, "pages") as pages, patch.object(policy.time, "sleep") as sleep:
            self.assertEqual(policy.coordination_artifact_index(self.repository), {artifact["name"]: 200})
        self.assertEqual(api.call_count, 1)
        pages.assert_not_called()
        sleep.assert_not_called()

    def test_history_instability_cannot_publish_a_decision_or_claim_ownership(self):
        reads = []
        def api(endpoint, **kwargs):
            if "/workflows/tests.yml/runs?" in endpoint:
                reads.append(endpoint)
                return {"total_count": len(self.runs) + 1, "workflow_runs": list(self.runs.values())}
            return self.api(endpoint, **kwargs)
        with self.server(), patch.object(policy, "api", side_effect=api), patch.object(policy.time, "sleep"):
            with self.assertRaisesRegex(policy.CoordinationRefusal, "workflow_history_incomplete_or_changed"):
                policy.canonical_selection(self.env())
            self.assertFalse(Path("ci-coordination-decision/coordination-decision.json").exists())
        self.assertEqual(len(reads), 3)
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_artifact_index_persistent_instability_is_bounded_and_cannot_claim_ownership(self):
        first = {"total_count": 2, "artifacts": [{"id": 1, "name": "unrelated"}]}
        inventory_reads = []
        def api(endpoint, **kwargs):
            if endpoint.endswith("actions/artifacts?per_page=100"):
                inventory_reads.append(endpoint)
                return first
            return self.api(endpoint, **kwargs)
        with self.server(), patch.object(policy, "api", side_effect=api), patch.object(policy.time, "sleep"), self.assertRaisesRegex(policy.CoordinationRefusal, "inventory_incomplete_or_changed"):
            policy.canonical_selection(self.env())
        self.assertEqual(len(inventory_reads), 3)
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_artifact_index_uses_one_deadline_for_both_native_commands(self):
        artifacts = [{"id": n, "name": "unrelated"} for n in range(101)]
        first = {"total_count": 101, "artifacts": artifacts[:100]}
        last = {"total_count": 101, "artifacts": artifacts[100:]}
        clock = [0]
        def command(*args, **kwargs):
            if "&page=2" in args[0][-1]:
                clock[0] = 46
                return json.dumps(last)
            clock[0] = 20
            return json.dumps(first)
        with patch.object(policy.time, "monotonic", side_effect=lambda: clock[0]), patch.object(policy.subprocess, "check_output", side_effect=command) as calls, self.assertRaisesRegex(policy.CoordinationRefusal, "inventory_incomplete_or_changed"):
            policy.coordination_artifact_index(self.repository)
        self.assertEqual([call.kwargs["timeout"] for call in calls.call_args_list], [45, 25])

    def test_artifact_index_hard_failures_are_not_retried(self):
        artifact = {"id": 1, "name": "ci-coordination-identity-200-1", "workflow_run": {"id": 200}}
        for scenario in ["exceeds bound", "invalid name", "duplicate identity", "API failure", "API timeout"]:
            response = {"total_count": 1, "artifacts": [copy.deepcopy(artifact)]}
            error = None
            if scenario == "exceeds bound": response["total_count"] = 10001
            if scenario == "invalid name": response["artifacts"][0]["name"] = []
            if scenario == "duplicate identity": response = {"total_count": 2, "artifacts": [artifact, dict(artifact, id=2)]}
            if scenario == "API failure": error = subprocess.CalledProcessError(1, "gh api")
            if scenario == "API timeout": error = subprocess.TimeoutExpired("gh api", 45)
            with self.subTest(scenario=scenario), patch.object(policy, "api", return_value=response, side_effect=error) as api, patch.object(policy.time, "sleep") as sleep, self.assertRaises((policy.CoordinationRefusal, subprocess.CalledProcessError, subprocess.TimeoutExpired)):
                policy.coordination_artifact_index(self.repository)
            self.assertEqual(api.call_count, 1)
            sleep.assert_not_called()

    def test_artifact_retry_cannot_publish_for_a_changed_run_attempt(self):
        native_api = self.api
        reads = 0
        def api(endpoint, **kwargs):
            nonlocal reads
            result = copy.deepcopy(native_api(endpoint, **kwargs))
            if endpoint.endswith("actions/artifacts?per_page=100"):
                reads += 1
                if reads == 1:
                    result["total_count"] += 1
                else:
                    self.runs[200]["run_attempt"] = 2
            return result
        def pages(endpoint, **kwargs):
            if endpoint.endswith("actions/artifacts?per_page=100"):
                return [{"total_count": 0, "artifacts": []}]
            return self.pages(endpoint, **kwargs)
        with self.server(), patch.object(policy, "api", side_effect=api), patch.object(policy, "pages", side_effect=pages), patch.object(policy.time, "sleep"), self.assertRaisesRegex(policy.CoordinationRefusal, "current_run_is_not_trusted_attempt"):
            policy.canonical_selection(self.env())
        self.assertEqual(reads, 2)
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_artifact_index_timeout_never_grants_execute_ownership(self):
        with self.server(), patch.object(policy, "coordination_artifact_index", side_effect=subprocess.TimeoutExpired("gh api", 45)), self.assertRaises(subprocess.TimeoutExpired):
            policy.canonical_selection(self.env())
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_failed_peer_without_identity_avoids_serial_per_run_artifact_lookup(self):
        self.runs[100]["conclusion"] = "failure"
        self.artifacts[100].clear()
        calls = []
        native_api = self.api
        def record(endpoint, **kwargs):
            calls.append(endpoint)
            return native_api(endpoint, **kwargs)
        with self.server(), patch.object(policy, "api", side_effect=record):
            self.assertEqual(policy.canonical_selection(self.env())["mode"], "execute")
        self.assertFalse(any("/runs/100/artifacts?" in endpoint for endpoint in calls))

    def test_indexed_failed_peer_identity_disappearing_refuses_new_ownership(self):
        self.runs[100]["conclusion"] = "failure"
        original = policy.coordination_identity
        def disappearance(repository, run):
            if run["id"] == 100:
                raise policy.MissingEvidence("identity absent from now-complete per-run inventory")
            return original(repository, run)
        with self.server(), patch.object(policy, "coordination_identity", side_effect=disappearance), self.assertRaisesRegex(policy.CoordinationRefusal, "coordination_index_changed"):
            policy.canonical_selection(self.env())
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def failed_peer(self, number, *, matching=False):
        run = copy.deepcopy(self.runs[100])
        sha = f"{number:040x}"
        run.update(id=number, event="push", head_branch="dev", head_sha=sha, conclusion="failure")
        self.runs[number] = run
        self.artifacts[number] = {}
        self.jobs[number] = []
        tree = self.tree if matching else "7" * 40
        self.commits[sha] = {"sha": sha, "tree": {"sha": tree}, "parents": [{"sha": "8" * 40}]}
        self.identity(number)["tree_sha"] = tree

    def test_historical_peer_reads_overlap_with_a_fixed_bound_and_validate_every_identity(self):
        peers = list(range(300, 308))
        for number in peers:
            self.failed_peer(number)
        original = policy.coordination_identity
        lock = threading.Lock()
        ready = threading.Event()
        active = peak = 0
        checked = []

        def identity(repository, run):
            nonlocal active, peak
            if run["id"] not in peers:
                return original(repository, run)
            with lock:
                active += 1
                peak = max(peak, active)
                if active == 4:
                    ready.set()
            try:
                self.assertTrue(ready.wait(3), "historical reads were serialized")
                result = original(repository, run)
                with lock:
                    checked.append(run["id"])
                return result
            finally:
                with lock:
                    active -= 1

        with self.server(), patch.object(policy, "coordination_identity", side_effect=identity):
            self.assertEqual(policy.canonical_selection(self.env())["mode"], "reuse")
        self.assertEqual(peak, 4)
        self.assertCountEqual(checked, peers)

    def test_parallel_historical_reads_still_refuse_a_matching_failed_peer(self):
        for number in range(300, 308):
            self.failed_peer(number, matching=number == 307)
        with self.server(), self.assertRaisesRegex(policy.CoordinationRefusal, "canonical_peer_unsuccessful_no_takeover"):
            policy.canonical_selection(self.env())
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_parallel_historical_reads_cannot_ignore_failed_identity_publication(self):
        for number in range(300, 308):
            self.failed_peer(number)
        self.jobs[307][0]["steps"][-1]["conclusion"] = "failure"
        with self.server(), self.assertRaisesRegex(policy.CoordinationRefusal, "identity_or_decision_step_not_successful"):
            policy.canonical_selection(self.env())
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_historical_refusal_does_not_submit_later_batches(self):
        peers = list(range(300, 340))
        for number in peers:
            self.failed_peer(number, matching=number == 300)
        original = policy.coordination_identity
        checked = []

        def identity(repository, run):
            checked.append(run["id"])
            return original(repository, run)

        with self.server(), patch.object(policy, "coordination_identity", side_effect=identity), self.assertRaisesRegex(policy.CoordinationRefusal, "canonical_peer_unsuccessful_no_takeover"):
            policy.canonical_selection(self.env())
        self.assertTrue(set(checked).intersection(peers))
        self.assertFalse(set(checked).intersection(peers[4:]))
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_current_identity_contradiction_rechecks_before_selecting(self):
        self.jobs[200][0].update(status="in_progress", conclusion="success")
        with self.server(), patch.object(policy.time, "sleep", side_effect=lambda _: self.jobs[200][0].update(status="completed")) as sleep:
            self.assertEqual(policy.canonical_selection(self.env())["mode"], "reuse")
        self.assertEqual(sleep.call_count, 1)

    def test_persistent_current_identity_contradiction_is_bounded_and_refuses(self):
        self.jobs[200][0].update(status="in_progress", conclusion="success")
        with self.server(), patch.object(policy.time, "sleep") as sleep, self.assertRaisesRegex(policy.CoordinationRefusal, "identity_job_status_inconsistent") as error:
            policy.canonical_selection(self.env())
        self.assertNotIsInstance(error.exception, policy.PublicationPending)
        self.assertEqual(sleep.call_count, 2)
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_terminal_historical_identity_job_cannot_escape_as_pending(self):
        self.runs[100]["conclusion"] = "failure"
        self.jobs[100][0].update(status="in_progress", conclusion=None)
        with self.server(), patch.object(policy.time, "sleep") as sleep, self.assertRaisesRegex(policy.CoordinationRefusal, "identity_job_status_inconsistent") as error:
            policy.canonical_selection(self.env())
        self.assertNotIsInstance(error.exception, policy.PublicationPending)
        self.assertEqual(sleep.call_count, 2)
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_historical_identity_recovery_still_blocks_matching_failed_peer(self):
        self.runs[100]["conclusion"] = "failure"
        self.jobs[100][0].update(status="in_progress", conclusion="success")
        with self.server(), patch.object(policy.time, "sleep", side_effect=lambda _: self.jobs[100][0].update(status="completed")), self.assertRaisesRegex(policy.CoordinationRefusal, "canonical_peer_unsuccessful_no_takeover"):
            policy.canonical_selection(self.env())
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_identity_status_retry_preserves_attempt_and_step_provenance(self):
        for scenario in ["attempt changed", "failed upload"]:
            self.runs[200]["run_attempt"] = 1
            self.jobs[200][0] = self.job(policy.IDENTITY_JOB, steps=policy.IDENTITY_STEPS)
            self.jobs[200][0].update(status="in_progress", conclusion="success")
            def transition(_):
                self.jobs[200][0]["status"] = "completed"
                if scenario == "attempt changed":
                    self.runs[200]["run_attempt"] = 2
                else:
                    self.jobs[200][0]["steps"][-1]["conclusion"] = "failure"
            reason = "run_or_attempt_changed" if scenario == "attempt changed" else "identity_or_decision_step_not_successful"
            with self.subTest(scenario=scenario), self.server(), patch.object(policy, "api", side_effect=lambda endpoint, **kwargs: copy.deepcopy(self.api(endpoint, **kwargs))), patch.object(policy.time, "sleep", side_effect=transition), self.assertRaisesRegex(policy.CoordinationRefusal, reason):
                policy.canonical_selection(self.env())
            self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_contradictory_status_never_retries_a_failed_publication_step(self):
        self.jobs[200][0].update(status="in_progress", conclusion="success")
        self.jobs[200][0]["steps"][-1]["conclusion"] = "failure"
        with self.server(), patch.object(policy.time, "sleep") as sleep, self.assertRaisesRegex(policy.CoordinationRefusal, "identity_or_decision_step_not_successful"):
            policy.canonical_selection(self.env())
        sleep.assert_not_called()
        self.assertNotIn("ci-coordination-decision", self.artifacts[200])

    def test_nonterminal_identity_with_failed_conclusion_never_waits_or_succeeds(self):
        for conclusion in ["failure", "cancelled", "skipped", "timed_out"]:
            self.jobs[200][0].update(status="in_progress", conclusion=conclusion)
            with self.subTest(conclusion=conclusion), self.server(), patch.object(policy.time, "sleep") as sleep, self.assertRaisesRegex(policy.CoordinationRefusal, "identity_or_decision_job_not_successful"):
                policy.canonical_selection(self.env())
            sleep.assert_not_called()

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


    def test_matching_prefetched_hint_still_needs_fresh_source_validation(self):
        with self.server(), patch.object(policy, "validate_source", side_effect=ValueError("source changed after hint")) as fresh:
            self.assertEqual(policy.canonical_selection(self.env())["mode"], "execute")
        fresh.assert_called_once_with(self.repository, self.runs[100], self.tree, "poc")

    def test_pinned_and_full_source_reads_do_not_prefetch(self):
        for scope, extra in [("poc", {"SOURCE_RUN": "100", "SOURCE_ATTEMPT": "1"}), ("full", {})]:
            with self.subTest(scope=scope), self.server(), patch.object(policy, "source_proof_hints") as hints:
                self.assertEqual(policy.reusable_run(self.env(**extra), scope), "100" if scope == "poc" else "")
                hints.assert_not_called()

    def test_parallel_inventory_reanchors_all_metadata_even_when_count_and_ids_are_unchanged(self):
        artifacts = [{"id": n, "name": f"ci-coordination-identity-{n}-1", "workflow_run": {"id": n}} for n in range(101)]
        base_reads = []
        def api(endpoint, **kwargs):
            if "&page=" in endpoint:
                return {"total_count": 101, "artifacts": copy.deepcopy(artifacts[100:])}
            base_reads.append(endpoint)
            first = copy.deepcopy(artifacts[:100])
            if len(base_reads) % 2 == 0:
                first[0]["workflow_run"]["id"] = 999
            return {"total_count": 101, "artifacts": first}
        with patch.object(policy, "api", side_effect=api), patch.object(policy.time, "sleep"), self.assertRaisesRegex(policy.CoordinationRefusal, "inventory_incomplete_or_changed"):
            policy.coordination_artifact_index(self.repository)
        self.assertEqual(len(base_reads), 6)

    def test_parallel_inventory_covers_49_pages_with_at_most_four_readers(self):
        artifacts = [{"id": n, "name": f"ci-coordination-identity-{n}-1", "workflow_run": {"id": n}} for n in range(4828)]
        barrier, lock = threading.Barrier(4), threading.Lock()
        active, peak, pages = [0], [0], []
        def api(endpoint, **kwargs):
            if "&page=" not in endpoint:
                return {"total_count": len(artifacts), "artifacts": artifacts[:100]}
            page = int(endpoint.rsplit("=", 1)[-1])
            with lock:
                active[0] += 1
                peak[0] = max(peak[0], active[0])
                pages.append(page)
            try:
                if page <= 5: barrier.wait(timeout=3)
                return {"total_count": len(artifacts), "artifacts": artifacts[(page - 1) * 100:page * 100]}
            finally:
                with lock: active[0] -= 1
        with patch.object(policy, "api", side_effect=api) as calls:
            result = policy.coordination_artifact_index(self.repository)
        self.assertEqual(result, {a["name"]: a["workflow_run"]["id"] for a in artifacts})
        self.assertEqual(sorted(pages), list(range(2, 50)))
        self.assertEqual((peak[0], active[0], calls.call_count), (4, 0, 50))


    def test_matching_hint_never_bypasses_the_newer_failed_run_guard(self):
        self.runs[202] = dict(self.runs[100], id=202, conclusion="failure")
        with self.server(), patch.object(policy, "validate_source") as fresh, patch.object(policy, "newer_run_blocks", side_effect=lambda repository, run, source, tree: run["id"] == 202) as newer, self.assertRaisesRegex(policy.CoordinationRefusal, "^newer_matching_run_blocks_reuse$"):
            policy.reusable_run(self.env(), "poc")
        fresh.assert_called_once_with(self.repository, self.runs[100], self.tree, "poc")
        self.assertTrue(any(call.args[1]["id"] == 202 for call in newer.call_args_list))

    def test_inventory_keeps_an_expired_real_parent_deadline_a_hard_refusal(self):
        token = policy._follower_read_deadline.set(99)
        try:
            with patch.object(policy.time, "monotonic", return_value=100), patch.object(policy, "api") as api, self.assertRaisesRegex(policy.CoordinationRefusal, "^follower_decision_publication_timeout$"):
                policy.coordination_artifact_index(self.repository)
            api.assert_not_called()
        finally:
            policy._follower_read_deadline.reset(token)


class SourceProofHintTests(unittest.TestCase):
    def test_positive_source_cannot_return_after_cleanup_expires_real_parent(self):
        clock = [0]
        class SlowCleanupExecutor(policy.ThreadPoolExecutor):
            def shutdown(self, wait=True, *, cancel_futures=False):
                super().shutdown(wait=wait, cancel_futures=cancel_futures)
                clock[0] = 31
        runs = [{"id": n, "status": "completed", "conclusion": "success", "run_attempt": 1,
                 "created_at": "2026-10-17T00:00:00Z"} for n in range(4)]
        env = {"GITHUB_REPOSITORY": "rozine-rw/rozine", "GITHUB_SHA": "c" * 40, "GITHUB_RUN_ID": "999"}
        token = policy._follower_read_deadline.set(30)
        try:
            with patch.object(policy.time, "monotonic", side_effect=lambda: clock[0]), patch.object(policy, "ThreadPoolExecutor", SlowCleanupExecutor), patch.object(policy, "command", side_effect=lambda *args: "d" * 40 if args[-1] == "HEAD^{tree}" else "c" * 40), patch.object(policy, "api", return_value={"id": 7}), patch.object(policy, "workflow_history", return_value=runs), patch.object(policy, "trusted_run", return_value=True), patch.object(policy, "pending_follower_ids", return_value=set()), patch.object(policy, "download_evidence", return_value={"tree_sha": "d" * 40, "scope": "poc"}), patch.object(policy, "newer_run_blocks", return_value=False), patch.object(policy, "validate_source") as fresh, self.assertRaisesRegex(policy.CoordinationRefusal, "^follower_decision_publication_timeout$"):
                policy.reusable_run(env, "poc")
            fresh.assert_called_once()
            self.assertEqual(policy._follower_read_deadline.get(), 30)
        finally:
            policy._follower_read_deadline.reset(token)

    def test_original_hundred_run_window_is_not_widened_when_failures_are_filtered(self):
        repository = "rozine-rw/rozine"
        runs = [{"id": n, "status": "completed", "conclusion": "failure" if n == 10 else "success", "run_attempt": 1,
                 "workflow_id": 7, "path": ".github/workflows/tests.yml", "head_repository": {"full_name": repository},
                 "event": "push", "created_at": "2026-10-17T00:00:00Z"} for n in range(105)]
        seen = []
        def hints(repository, candidates, deadline):
            seen.extend(source["id"] for source in candidates)
            return ((source, {"tree_sha": "e" * 40, "scope": "poc"}) for source in candidates)
        env = {"GITHUB_REPOSITORY": repository, "GITHUB_SHA": "c" * 40, "GITHUB_RUN_ID": "999"}
        with patch.object(policy, "command", side_effect=lambda *args: "d" * 40 if args[-1] == "HEAD^{tree}" else "c" * 40), patch.object(policy, "api", return_value={"id": 7}), patch.object(policy, "workflow_history", return_value=runs), patch.object(policy, "source_proof_hints", side_effect=hints), patch.object(policy, "validate_source") as fresh:
            self.assertEqual(policy.reusable_run(env, "poc"), "")
        self.assertEqual(seen, [n for n in range(104, 4, -1) if n != 10])
        fresh.assert_not_called()

    def test_four_bounded_readers_preserve_candidate_order_despite_completion_order(self):
        barrier, lock = threading.Barrier(4), threading.Lock()
        active, peak = [0], [0]
        candidates = [{"id": n} for n in range(12)]
        def download(repository, run):
            with lock:
                active[0] += 1
                peak[0] = max(peak[0], active[0])
            try:
                if run["id"] < 4: barrier.wait(timeout=3)
                return {"run_id": str(run["id"])}
            finally:
                with lock: active[0] -= 1
        with patch.object(policy, "download_evidence", side_effect=download):
            result = list(policy.source_proof_hints("repo", candidates, policy.time.monotonic() + 5))
        self.assertEqual([source["id"] for source, _ in result], list(range(12)))
        self.assertEqual([proof["run_id"] for _, proof in result], list(map(str, range(12))))
        self.assertEqual((peak[0], active[0]), (4, 0))

    def test_hint_errors_are_only_unusable_positives_and_do_not_leak_context(self):
        errors = [OSError("offline"), subprocess.TimeoutExpired("gh", 1), ValueError("malformed"), KeyError("missing"), TypeError("bad"), AttributeError("bad")]
        def download(repository, run):
            if run["id"] < len(errors): raise errors[run["id"]]
            return {"run_id": "valid hint"}
        with patch.object(policy, "download_evidence", side_effect=download):
            result = list(policy.source_proof_hints("repo", [{"id": n} for n in range(7)], policy.time.monotonic() + 5))
        self.assertEqual([hint for _, hint in result], [None] * 6 + [{"run_id": "valid hint"}])
        self.assertIsNone(policy._follower_read_deadline.get())

    def test_shorter_parent_deadline_is_inherited_per_worker_and_reset_in_caller(self):
        parent = policy.time.monotonic() + 4
        caps, lock = [], threading.Lock()
        def download(repository, run):
            with lock: caps.append(policy._follower_read_deadline.get())
            return {"run_id": str(run["id"])}
        token = policy._follower_read_deadline.set(parent)
        try:
            with patch.object(policy, "download_evidence", side_effect=download):
                self.assertEqual(len(list(policy.source_proof_hints("repo", [{"id": n} for n in range(8)], parent + 10))), 8)
            self.assertEqual(caps, [parent] * 8)
            self.assertEqual(policy._follower_read_deadline.get(), parent)
        finally:
            policy._follower_read_deadline.reset(token)

    def test_expired_real_parent_deadline_cannot_become_execute_fallback(self):
        token = policy._follower_read_deadline.set(99)
        try:
            with patch.object(policy.time, "monotonic", return_value=100), patch.object(policy, "download_evidence", return_value={}), self.assertRaisesRegex(policy.CoordinationRefusal, "^follower_decision_publication_timeout$"):
                list(policy.source_proof_hints("repo", [{"id": 1}], 145))
            self.assertEqual(policy._follower_read_deadline.get(), 99)
        finally:
            policy._follower_read_deadline.reset(token)

    def test_shared_lookup_cap_bounds_native_command_and_rejects_late_success(self):
        clock, timeouts = [0], []
        def command(*args, **kwargs):
            timeouts.append(kwargs["timeout"])
            clock[0] = 46
            return "{}"
        def download(repository, run):
            return json.loads(policy.command("gh", "api", "positive-proof"))
        with patch.object(policy.time, "monotonic", side_effect=lambda: clock[0]), patch.object(policy.subprocess, "check_output", side_effect=command), patch.object(policy, "download_evidence", side_effect=download):
            self.assertEqual(list(policy.source_proof_hints("repo", [{"id": 1}], 45)), [])
        self.assertEqual(timeouts, [45])
        self.assertIsNone(policy._follower_read_deadline.get())

    def test_early_consumer_close_stops_prefetch_and_joins_all_started_readers(self):
        calls, lock = [], threading.Lock()
        def download(repository, run):
            with lock: calls.append(run["id"])
            return {"run_id": str(run["id"])}
        with patch.object(policy, "download_evidence", side_effect=download):
            hints = policy.source_proof_hints("repo", [{"id": n} for n in range(100)], policy.time.monotonic() + 5)
            self.assertEqual(next(hints)[0]["id"], 0)
            hints.close()
            stopped = len(calls)
        self.assertLessEqual(stopped, 4)
        self.assertEqual(len(calls), stopped)


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
