"""Offline preflight tests; no credentials or live GitHub requests."""

import copy
import io
import json
import os
import re
import subprocess
import sys
import tempfile
import textwrap
import unittest
from pathlib import Path
from unittest.mock import Mock

import project_board_preflight as preflight
import project_board_sync as board
from test_project_board_sync import FakeAPI, fixture


def push_fixture():
    env, _, _, _, repository, census = fixture()
    env.update(BOARD_SYNC_ENABLED="", BOARD_SYNC_WRITES_ENABLED="false",
               BOARD_SYNC_PREFLIGHT_ENABLED="true", BOARD_SYNC_PREFLIGHT_TRIGGER_SHA="a" * 40,
               GITHUB_EVENT_NAME="push", GITHUB_RUN_ATTEMPT="1", GITHUB_SHA="c" * 40)
    event = {"repository": repository, "ref": "refs/heads/dev", "created": False,
             "deleted": False, "forced": False, "before": "e" * 40, "after": env["GITHUB_SHA"],
             "head_commit": {"id": env["GITHUB_SHA"]},
             "commits": [{"id": env["BOARD_SYNC_PREFLIGHT_TRIGGER_SHA"]}, {"id": env["GITHUB_SHA"]}]}
    return env, event, repository, census


class PreflightTest(unittest.TestCase):
    def setUp(self):
        self.env, self.event, self.repository, self.census = push_fixture()

    def test_normal_dev_merge_contains_approved_commit_and_unrelated_push_skips(self):
        self.assertTrue(preflight.eligible_push(self.event, self.env))
        self.event["commits"] = [{"id": self.env["GITHUB_SHA"]}]
        self.assertFalse(preflight.eligible_push(self.event, self.env))

    def test_missing_disabled_or_malicious_configuration_fails_closed(self):
        for key, value in [("BOARD_SYNC_PREFLIGHT_ENABLED", ""), ("BOARD_SYNC_ENABLED", "true"),
                           ("BOARD_SYNC_WRITES_ENABLED", "true"), ("BOARD_SYNC_WRITES_ENABLED", ""),
                           ("BOARD_SYNC_TRUSTED_SHA", "$(id)"),
                           ("BOARD_SYNC_PREFLIGHT_TRIGGER_SHA", "a' ; $(id)"),
                           ("BOARD_SYNC_CLIENT_ID", ""), ("BOARD_SYNC_APP_SLUG", ""),
                           ("BOARD_SYNC_INSTALLATION_ID", "0")]:
            with self.subTest(key=key), self.assertRaises(board.Refused):
                preflight.eligible_push(self.event, {**self.env, key: value})

    def test_only_first_push_attempt_to_exact_repo_dev_is_eligible(self):
        for key, value in [("GITHUB_EVENT_NAME", "pull_request_target"),
                           ("GITHUB_EVENT_NAME", "workflow_dispatch"), ("GITHUB_REF", "refs/heads/main"),
                           ("GITHUB_REF", "refs/tags/dev"), ("GITHUB_REPOSITORY", "attacker/rozine"),
                           ("GITHUB_RUN_ATTEMPT", "2"), ("GITHUB_RUN_ATTEMPT", ""),
                           ("GITHUB_SHA", "d" * 40)]:
            with self.subTest(key=key), self.assertRaises(board.Refused):
                preflight.eligible_push(self.event, {**self.env, key: value})
        for key, value in [("full_name", "attacker/rozine"), ("id", 1),
                           ("owner", {"node_id": "attacker"})]:
            event = copy.deepcopy(self.event)
            event["repository"][key] = value
            with self.subTest(key=key), self.assertRaises(board.Refused):
                preflight.eligible_push(event, self.env)

    def test_created_deleted_forced_or_wrong_head_push_refused(self):
        for key in ["created", "deleted", "forced"]:
            for value in [True, 0, None]:
                with self.subTest(key=key, value=value), self.assertRaises(board.Refused):
                    preflight.eligible_push({**self.event, key: value}, self.env)
        for change in [{"ref": "refs/heads/main"}, {"after": "$(id)"},
                       {"head_commit": {"id": "d" * 40}}]:
            with self.assertRaises(board.Refused):
                preflight.eligible_push({**self.event, **change}, self.env)

    def test_missing_duplicate_rewritten_or_potentially_truncated_commits_refused(self):
        for commits in [None, [], [{"id": "$(id)"}], [self.event["commits"][0]] * 2,
                        [{"id": f"{i:040x}"} for i in range(2048)]]:
            with self.subTest(size=len(commits) if isinstance(commits, list) else None), self.assertRaises(board.Refused):
                preflight.eligible_push({**self.event, "commits": commits}, self.env)
        # Squash/rebase identities no longer include the reviewed feature head.
        self.assertFalse(preflight.eligible_push({**self.event, "commits": [{"id": "c" * 40}]}, self.env))

    def test_preflight_census_preserves_all_cards_and_does_not_select_pull(self):
        api = FakeAPI(self.repository, self.census, {})
        before = copy.deepcopy(api.board)
        self.assertIn("no cards selected or changed", preflight.inspect(api, self.env))
        self.assertEqual(api.board, before)
        self.assertEqual(api.mutations, [])
        self.assertEqual(api.reads, 1)
        api.board["organization"]["projectV2"]["id"] = "WRONG_PROJECT"
        with self.assertRaises(board.Refused):
            preflight.inspect(api, self.env)
        with self.assertRaises(board.Refused):
            preflight.inspect(FakeAPI(self.repository, self.census, {}),
                              {**self.env, "BOARD_SYNC_ACTUAL_INSTALLATION_ID": "wrong"})

    def test_read_only_api_allows_only_exact_census_and_audience_reads(self):
        opener = Mock()
        opener.open.side_effect = [io.BytesIO(json.dumps({"total_count": 1}).encode()),
                                   io.BytesIO(json.dumps({"data": self.census}).encode())]
        api = preflight.ReadOnlyAPI("OFFLINE_TOKEN", opener=opener)
        self.assertEqual(api.request("/installation/repositories?per_page=100"), {"total_count": 1})
        self.assertEqual(api.graphql(board.BOARD_QUERY, {"cursor": None}), self.census)
        self.assertEqual(opener.open.call_count, 2)

    def test_mutations_arbitrary_queries_posts_variables_and_urls_refused_before_network(self):
        opener = Mock()
        api = preflight.ReadOnlyAPI("OFFLINE_TOKEN", opener=opener)
        for path, payload, mutation in [
            ("/graphql", {"query": board.UPDATE_MUTATION, "variables": {}}, False),
            ("/graphql", {"query": board.BOARD_QUERY, "variables": {"cursor": None}}, True),
            ("/graphql", {"query": board.BOARD_QUERY, "variables": {"injected": "SECRET"}}, False),
            ("/installation/repositories?per_page=100", {}, False),
            ("/repos/rozine-rw/rozine/pulls/1", None, False),
            ("https://attacker.invalid/SECRET", None, False),
        ]:
            with self.subTest(path=path), self.assertRaises(board.Refused) as error:
                api.request(path, payload, mutation)
            self.assertNotIn("SECRET", str(error.exception))
        opener.open.assert_not_called()

    def test_check_cli_needs_no_token_and_redacts_event_text(self):
        with tempfile.TemporaryDirectory() as directory:
            event_path, output_path = Path(directory) / "event.json", Path(directory) / "output"
            event = {**self.event, "irrelevant": "SECRET $(touch /tmp/SHOULD_NOT_EXIST)"}
            event_path.write_text(json.dumps(event))
            env = {**os.environ, **self.env, "GITHUB_EVENT_PATH": str(event_path),
                   "GITHUB_OUTPUT": str(output_path), "BOARD_SYNC_TOKEN": ""}
            result = subprocess.run([sys.executable, "-B", preflight.__file__, "--check"],
                                    env=env, capture_output=True, text=True, timeout=10)
            self.assertEqual(result.returncode, 0)
            self.assertEqual(output_path.read_text(), "eligible=true\n")
            self.assertNotIn("SECRET", result.stdout + result.stderr)
            event_path.write_text('{"bad": "SECRET"}')
            result = subprocess.run([sys.executable, "-B", preflight.__file__, "--check"],
                                    env=env, capture_output=True, text=True, timeout=10)
            self.assertEqual(result.returncode, 1)
            self.assertNotIn("SECRET", result.stdout + result.stderr)


class PreflightWorkflowTest(unittest.TestCase):
    def setUp(self):
        self.workflow = (Path(__file__).resolve().parents[1] / "workflows/board-sync-preflight.yml").read_text()

    def test_fixed_read_scope_and_trust_order_with_no_default_branch_trigger(self):
        self.assertIn("push:\n    branches: [dev]", self.workflow)
        for trigger in ["workflow_dispatch:", "pull_request:", "pull_request_target:", "schedule:"]:
            self.assertNotIn(trigger, self.workflow)
        self.assertIn("github.run_attempt == 1", self.workflow)
        self.assertIn("    concurrency:\n      group: rozine-delivery-project-1-sync", self.workflow)
        self.assertNotIn("\nconcurrency:", self.workflow)
        self.assertIn("contains(github.event.commits.*.id, vars.BOARD_SYNC_PREFLIGHT_TRIGGER_SHA)", self.workflow)
        self.assertIn("permission-organization-projects: read", self.workflow)
        self.assertNotIn("permission-organization-projects: ${{", self.workflow)
        self.assertIn("persist-credentials: false", self.workflow)
        self.assertIn("fetch-depth: 0", self.workflow)
        self.assertLess(self.workflow.index("git merge-base --is-ancestor"),
                        self.workflow.index("run: python3 .github/scripts/project_board_preflight.py --check"))
        self.assertLess(self.workflow.index("--check"), self.workflow.index("- name: Mint"))
        self.assertNotIn("run: python3 .github/scripts/project_board_sync.py", self.workflow)
        for run in re.findall(r'run: (.*?)(?=\n      - |\Z)', self.workflow, re.S):
            self.assertNotIn("${{", run)
        self.assertEqual(len(re.findall(r'uses: actions/[^\s]+@[0-9a-f]{40}', self.workflow)), 2)

    def test_actual_bootstrap_refuses_missing_key_rerun_write_mode_and_bad_pin(self):
        program = textwrap.dedent(self.workflow.split("python3 - <<'PY'\n", 1)[1].split("\n          PY", 1)[0])
        env, _, _, _ = push_fixture()
        env = {**os.environ, **env, "HAS_PRIVATE_KEY": "true"}
        for key, value in [("HAS_PRIVATE_KEY", "false"), ("GITHUB_RUN_ATTEMPT", "2"),
                           ("BOARD_SYNC_ENABLED", "true"), ("BOARD_SYNC_WRITES_ENABLED", "true"),
                           ("BOARD_SYNC_WRITES_ENABLED", ""),
                           ("BOARD_SYNC_TRUSTED_SHA", "$(id)"), ("BOARD_SYNC_PREFLIGHT_TRIGGER_SHA", "")]:
            result = subprocess.run([sys.executable, "-c", program], env={**env, key: value},
                                    capture_output=True, text=True, timeout=10)
            self.assertEqual(result.returncode, 1)
            self.assertIn("no token minted", result.stderr)
        result = subprocess.run([sys.executable, "-c", program], env=env,
                                capture_output=True, text=True, timeout=10)
        self.assertEqual(result.returncode, 0)

    def test_actual_ancestry_gate_refuses_unmerged_mismatched_or_missing_history(self):
        step = "      - name: Require the pinned implementation to be merged into dev\n"
        program = textwrap.dedent(self.workflow.split(step, 1)[1].split("\n      - name:", 1)[0]
                                 .split("run: |\n", 1)[1])
        with tempfile.TemporaryDirectory() as directory:
            def git(*args):
                return subprocess.run(["git", *args], cwd=directory, check=True,
                                      capture_output=True, text=True).stdout.strip()

            git("init", "--initial-branch=dev")
            git("config", "user.name", "Offline test")
            git("config", "user.email", "offline@example.invalid")
            git("commit", "--allow-empty", "-m", "Reviewed base")
            base = git("rev-parse", "HEAD")
            git("update-ref", "refs/remotes/origin/dev", base)
            git("commit", "--allow-empty", "-m", "Unmerged feature")
            feature = git("rev-parse", "HEAD")

            def gate(pin):
                return subprocess.run(["bash", "-c", program], cwd=directory,
                                      env={**os.environ, "BOARD_SYNC_TRUSTED_SHA": pin},
                                      capture_output=True, text=True, timeout=10)

            self.assertEqual(gate(feature).returncode, 1)
            self.assertEqual(gate(base).returncode, 1)
            git("checkout", "--detach", base)
            self.assertEqual(gate(base).returncode, 0)
            git("update-ref", "-d", "refs/remotes/origin/dev")
            result = gate(base)
            self.assertEqual(result.returncode, 1)
            self.assertIn("no token minted", result.stderr)


if __name__ == "__main__":
    unittest.main()
