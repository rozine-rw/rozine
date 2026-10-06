"""Offline event/API simulations. Never read credentials or contact GitHub."""

import copy
import io
import json
import os
import subprocess
import sys
import tempfile
import unittest
import urllib.error
from pathlib import Path
from unittest.mock import Mock

import project_board_sync as sync


def fixture():
    repository = {"id": sync.REPOSITORY_ID, "full_name": sync.REPOSITORY,
                  "owner": {"node_id": sync.ORGANIZATION_ID}}
    row = {"number": 230, "node_id": "PR_test", "item_id": "PVTI_test", "head_sha": "a" * 40}
    pull = {"number": row["number"], "node_id": row["node_id"], "state": "open", "draft": False,
            "merged": False, "base": {"ref": "dev", "repo": repository},
            "head": {"sha": row["head_sha"], "repo": repository}}
    event = {"repository": repository, "action": "ready_for_review", "pull_request": pull}
    env = {"BOARD_SYNC_ENABLED": "true", "BOARD_SYNC_WRITES_ENABLED": "true",
           "BOARD_SYNC_TRUSTED_SHA": "b" * 40, "BOARD_SYNC_CLIENT_ID": "Iv1.test",
           "BOARD_SYNC_INSTALLATION_ID": "123", "BOARD_SYNC_APP_SLUG": "rozine-board-sync",
           "BOARD_SYNC_ACTUAL_INSTALLATION_ID": "123", "BOARD_SYNC_ACTUAL_APP_SLUG": "rozine-board-sync",
           "GITHUB_REPOSITORY": sync.REPOSITORY, "GITHUB_EVENT_NAME": "pull_request_target",
           "GITHUB_REF": "refs/heads/dev"}
    item = {"id": row["item_id"], "isArchived": False,
            "content": {"__typename": "PullRequest", "id": row["node_id"], "number": row["number"],
                        "repository": {"id": sync.REPOSITORY_NODE_ID, "nameWithOwner": sync.REPOSITORY}},
            "fieldValues": {"pageInfo": {"hasNextPage": False}, "nodes": [
                {"name": "In progress", "optionId": sync.OPTIONS["In progress"], "field": {"id": sync.STATUS_ID}},
                {"name": "P0", "optionId": "unchanged", "field": {"id": "priority"}}]}}
    project = {"id": sync.PROJECT_ID, "number": 1, "url": sync.PROJECT_URL, "title": "Rozine Delivery",
               "public": False, "closed": False, "fields": {"pageInfo": {"hasNextPage": False}, "nodes": [
                   {"id": sync.STATUS_ID, "name": "Status", "options": [
                       {"name": name, "id": value} for name, value in sync.OPTIONS.items()]}]},
               "items": {"pageInfo": {"hasNextPage": False, "endCursor": None}, "nodes": [item]}}
    board = {"organization": {"id": sync.ORGANIZATION_ID, "login": "rozine-rw", "projectV2": project}}
    return env, event, {"schema": 1, "pull_requests": [row]}, row, repository, board


class FakeAPI:
    def __init__(self, repository, board, pull):
        self.audience = {"total_count": 1, "repositories": [copy.deepcopy(repository)]}
        self.board = copy.deepcopy(board)
        self.pull = copy.deepcopy(pull)
        self.mutations = []
        self.reads = 0
        self.before_second_read = None
        self.fail_after_write = False

    def request(self, path):
        if path.startswith("/installation"):
            return copy.deepcopy(self.audience)
        return copy.deepcopy(self.pull)

    def graphql(self, query, variables=None, mutation=False):
        if mutation:
            self.mutations.append(copy.deepcopy(variables))
            item = self.board["organization"]["projectV2"]["items"]["nodes"][0]
            item["fieldValues"]["nodes"][0].update(name="Review", optionId=sync.OPTIONS["Review"])
            if self.fail_after_write:
                raise sync.Refused("mutation outcome unknown; inspect before rerun")
            return {"updateProjectV2ItemFieldValue": {"projectV2Item": {"id": variables["item"]}}}
        self.reads += 1
        if self.reads == 2 and self.before_second_read:
            self.before_second_read(self)
        return copy.deepcopy(self.board)


class BoardSyncTest(unittest.TestCase):
    def setUp(self):
        self.env, self.event, self.document, self.row, repository, self.board = fixture()
        self.api = FakeAPI(repository, self.board, self.event["pull_request"])

    def status(self, name):
        item = self.api.board["organization"]["projectV2"]["items"]["nodes"][0]
        item["fieldValues"]["nodes"][0].update(name=name, optionId=sync.OPTIONS[name])

    def test_single_status_write_and_replay_are_idempotent(self):
        row, eligible = sync.select_approval(self.event, self.env, self.document)
        self.assertTrue(eligible)
        before = copy.deepcopy(self.api.board)
        self.assertIn("updated", sync.sync(self.api, self.env, row))
        self.assertEqual(self.api.mutations, [{"project": sync.PROJECT_ID, "item": self.row["item_id"],
                         "field": sync.STATUS_ID, "option": sync.OPTIONS["Review"]}])
        self.assertIn("preserved", sync.sync(self.api, self.env, row))
        self.assertEqual(len(self.api.mutations), 1)
        before["organization"]["projectV2"]["items"]["nodes"][0]["fieldValues"]["nodes"][0].update(
            name="Review", optionId=sync.OPTIONS["Review"])
        self.assertEqual(self.api.board, before)

    def test_all_other_statuses_remain_human_controlled(self):
        for name in ["Backlog", "Ready", "Review", "Blocked", "Done"]:
            with self.subTest(name=name):
                self.status(name)
                self.assertIn("preserved", sync.sync(self.api, self.env, self.row))
                self.assertEqual(self.api.mutations, [])

    def test_original_cards_not_automatically_authorized(self):
        self.document["pull_requests"] = []
        for number in [177, 219, 220, 999]:
            self.event["pull_request"]["number"] = number
            self.assertEqual(sync.select_approval(self.event, self.env, self.document), (None, False))

    def test_issue_closure_pr_close_merge_and_other_events_never_mean_done(self):
        for name, action in [("issues", "closed"), ("issues", "reopened"), ("pull_request_target", "closed"),
                             ("pull_request", "ready_for_review"), ("pull_request_target", "opened"),
                             ("pull_request_target", "synchronize"), ("issue_comment", "created")]:
            with self.subTest(name=name, action=action):
                self.env["GITHUB_EVENT_NAME"] = name
                self.event["action"] = action
                self.event["pull_request"]["merged"] = True
                self.assertEqual(sync.select_approval(self.event, self.env, self.document), (None, False))

    def test_fork_event_rejected_before_api_use(self):
        self.event["pull_request"]["head"]["repo"] = {"id": 42, "full_name": "attacker/rozine"}
        with self.assertRaises(sync.Refused):
            sync.select_approval(self.event, self.env, self.document)

    def test_event_number_node_and_head_are_validated(self):
        for key, value in [("node_id", "PR_other"), ("head", {"sha": "c" * 40, "repo": self.event["repository"]})]:
            with self.subTest(key=key):
                event = copy.deepcopy(self.event)
                event["pull_request"][key] = value
                with self.assertRaises(sync.Refused):
                    sync.select_approval(event, self.env, self.document)

    def test_malicious_body_title_branch_and_urls_are_never_used(self):
        payload = '$(touch /tmp/board-pwned)\n::error::secret\n${{ secrets.TOKEN }}'
        self.event["pull_request"].update(title=payload, body=payload, url="https://attacker.invalid/")
        self.event["pull_request"]["head"]["ref"] = payload
        row, _ = sync.select_approval(self.event, self.env, self.document)
        sync.sync(self.api, self.env, row)
        self.assertNotIn(payload, json.dumps(self.api.mutations))

    def test_untrusted_numbers_and_preflight_input_are_not_interpreted(self):
        for value in ['1; echo secret', '$(id)', '${{ secrets.X }}', '\n::error::x', '-1', '01', '1\n']:
            with self.subTest(value=value):
                self.env.update(GITHUB_EVENT_NAME="workflow_dispatch", BOARD_SYNC_PULL_NUMBER=value)
                with self.assertRaises(sync.Refused):
                    sync.select_approval(self.event, self.env, self.document)

    def test_missing_app_config_disabled_mode_and_wrong_repository_fail_closed(self):
        for key in ["BOARD_SYNC_ENABLED", "BOARD_SYNC_TRUSTED_SHA", "BOARD_SYNC_CLIENT_ID",
                    "BOARD_SYNC_INSTALLATION_ID", "BOARD_SYNC_APP_SLUG", "GITHUB_REPOSITORY"]:
            with self.subTest(key=key):
                env = self.env.copy()
                env.pop(key)
                with self.assertRaises(sync.Refused):
                    sync.select_approval(self.event, env, self.document)
        for invalid in ["TRUE", "yes", "$(id)"]:
            self.env["BOARD_SYNC_WRITES_ENABLED"] = invalid
            with self.assertRaises(sync.Refused):
                sync.validate_config(self.env)
        with self.assertRaises(sync.Refused):
            sync.API("")

    def test_wrong_project_identity_options_and_visibility_fail_closed(self):
        project = self.api.board["organization"]["projectV2"]
        for key, value in [("id", "PVT_other"), ("number", 2), ("url", "https://attacker.invalid"),
                           ("title", "Other"), ("closed", True), ("public", True)]:
            with self.subTest(key=key):
                original = project[key]
                project[key] = value
                with self.assertRaises(sync.Refused):
                    sync.sync(self.api, self.env, self.row)
                project[key] = original
        project["fields"]["nodes"][0]["options"][0]["id"] = "wrong"
        with self.assertRaises(sync.Refused):
            sync.sync(self.api, self.env, self.row)
        self.assertEqual(self.api.mutations, [])

    def test_wrong_installation_slug_and_broad_token_audience_refused(self):
        for key in ["BOARD_SYNC_ACTUAL_INSTALLATION_ID", "BOARD_SYNC_ACTUAL_APP_SLUG"]:
            env = self.env.copy()
            env[key] = "wrong"
            with self.assertRaises(sync.Refused):
                sync.sync(self.api, env, self.row)
        self.api.audience["total_count"] = 2
        with self.assertRaises(sync.Refused):
            sync.sync(self.api, self.env, self.row)
        self.assertEqual(self.api.reads, 0)

    def test_duplicate_allowlist_and_duplicate_missing_or_relinked_cards_refused(self):
        self.document["pull_requests"].append(copy.deepcopy(self.row))
        with self.assertRaises(sync.Refused):
            sync.approved_rows(self.document)
        items = self.api.board["organization"]["projectV2"]["items"]["nodes"]
        items.append(copy.deepcopy(items[0]))
        items[-1]["id"] = "PVTI_duplicate"
        with self.assertRaises(sync.Refused):
            sync.sync(self.api, self.env, self.row)
        items.pop()
        items[0]["id"] = "PVTI_relinked"
        with self.assertRaises(sync.Refused):
            sync.sync(self.api, self.env, self.row)
        items.clear()
        with self.assertRaises(sync.Refused):
            sync.sync(self.api, self.env, self.row)
        self.assertEqual(self.api.mutations, [])

    def test_drafts_issues_archived_cards_and_unset_status_not_changed(self):
        items = self.api.board["organization"]["projectV2"]["items"]["nodes"]
        original = copy.deepcopy(items[0])
        for content in [None, {"__typename": "DraftIssue"}, {**original["content"], "__typename": "Issue"}]:
            items[0]["content"] = content
            with self.assertRaises(sync.Refused):
                sync.sync(self.api, self.env, self.row)
        items[0] = original
        items[0]["isArchived"] = True
        self.assertIn("preserved", sync.sync(self.api, self.env, self.row))
        items[0]["fieldValues"]["nodes"] = []
        with self.assertRaises(sync.Refused):
            sync.sync(self.api, self.env, self.row)

    def test_stale_live_head_closed_draft_and_merged_pr_preserved(self):
        for key, value in [("state", "closed"), ("merged", True), ("draft", True),
                           ("head", {"sha": "c" * 40, "repo": self.event["repository"]})]:
            with self.subTest(key=key):
                original = self.api.pull[key]
                self.api.pull[key] = value
                self.assertIn("preserved", sync.sync(self.api, self.env, self.row))
                self.api.pull[key] = original
                self.assertEqual(self.api.mutations, [])

    def test_dry_run_mode_and_dispatch_never_mutate(self):
        self.env["BOARD_SYNC_WRITES_ENABLED"] = "false"
        self.assertIn("dry-run", sync.sync(self.api, self.env, self.row))
        self.env.update(BOARD_SYNC_WRITES_ENABLED="true", GITHUB_EVENT_NAME="workflow_dispatch",
                        BOARD_SYNC_PULL_NUMBER=str(self.row["number"]))
        row, eligible = sync.select_approval(self.event, self.env, self.document)
        self.assertTrue(eligible)
        self.assertIn("dry-run", sync.sync(self.api, self.env, row))
        self.env["BOARD_SYNC_PULL_NUMBER"] = ""
        row, eligible = sync.select_approval(self.event, self.env, self.document)
        self.assertIsNone(row)
        self.assertIn("preflight passed", sync.sync(self.api, self.env, row))
        self.assertEqual(self.api.mutations, [])
        self.env["GITHUB_REF"] = "refs/heads/feat/attacker"
        with self.assertRaises(sync.Refused):
            sync.select_approval(self.event, self.env, self.document)

    def test_human_block_or_pr_head_change_during_run_preserved(self):
        def block(api):
            self.status("Blocked")
        self.api.before_second_read = block
        self.assertIn("state changed", sync.sync(self.api, self.env, self.row))
        self.assertEqual(self.api.mutations, [])
        self.status("In progress")
        self.api.reads = 0
        def change_head(api):
            api.pull["head"]["sha"] = "c" * 40
        self.api.before_second_read = change_head
        self.assertIn("state changed", sync.sync(self.api, self.env, self.row))
        self.assertEqual(self.api.mutations, [])

    def test_ambiguous_write_is_not_retried_and_replay_inspects_current_state(self):
        self.api.fail_after_write = True
        with self.assertRaises(sync.Refused):
            sync.sync(self.api, self.env, self.row)
        self.assertIn("preserved", sync.sync(self.api, self.env, self.row))
        self.assertEqual(len(self.api.mutations), 1)

    def test_pagination_finds_duplicates_on_later_pages(self):
        first = copy.deepcopy(self.board)
        first["organization"]["projectV2"]["items"]["pageInfo"] = {"hasNextPage": True, "endCursor": "page2"}
        second = copy.deepcopy(self.board)
        second["organization"]["projectV2"]["items"]["nodes"][0]["id"] = "PVTI_duplicate"
        api = Mock()
        api.graphql.side_effect = [first, second]
        items = sync.board_snapshot(api)
        with self.assertRaises(sync.Refused):
            sync.eligible_item(items, self.row)
        self.assertEqual(api.graphql.call_args_list[1].args[1], {"cursor": "page2"})

    def test_truncated_fields_and_repeated_pagination_fail_closed(self):
        project = self.api.board["organization"]["projectV2"]
        project["fields"]["pageInfo"]["hasNextPage"] = True
        with self.assertRaises(sync.Refused):
            sync.board_snapshot(self.api)
        project["fields"]["pageInfo"]["hasNextPage"] = False
        project["items"]["nodes"][0]["fieldValues"]["pageInfo"]["hasNextPage"] = True
        with self.assertRaises(sync.Refused):
            sync.board_snapshot(self.api)
        project["items"]["nodes"] = []
        project["items"]["pageInfo"] = {"hasNextPage": True, "endCursor": "repeat"}
        with self.assertRaises(sync.Refused):
            sync.board_snapshot(self.api)

    def test_incomplete_api_cli_response_logs_no_payload_or_token(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "event.json"
            path.write_text(json.dumps({"repository": "SECRET-MALICIOUS-PAYLOAD"}))
            env = {**os.environ, **self.env, "GITHUB_EVENT_PATH": str(path), "GITHUB_OUTPUT": str(path.parent / "output")}
            result = subprocess.run([sys.executable, str(Path(sync.__file__)), "--check"],
                                    env=env, capture_output=True, text=True, timeout=10)
        self.assertEqual(result.returncode, 1)
        self.assertNotIn("SECRET-MALICIOUS-PAYLOAD", result.stderr)
        self.assertNotIn("Traceback", result.stderr)


class TransportTest(unittest.TestCase):
    def api(self, responses):
        opener = Mock()
        opener.open.side_effect = responses
        self.sleep = Mock()
        return sync.API("SECRET-TOKEN", opener=opener, sleep=self.sleep), opener

    def test_read_retries_with_bounded_backoff_and_fixed_endpoint(self):
        response = io.BytesIO(b'{"data": {}}')
        api, opener = self.api([urllib.error.HTTPError("https://attacker.invalid", 503, "SECRET", {}, None),
                                OSError("SECRET"), response])
        self.assertEqual(api.graphql(sync.BOARD_QUERY), {})
        self.assertEqual(opener.open.call_count, 3)
        self.assertEqual([call.args[0] for call in self.sleep.call_args_list], [1, 2])
        self.assertEqual(opener.open.call_args.args[0].full_url, "https://api.github.com/graphql")

    def test_forbidden_redirect_auth_and_graphql_errors_not_retried_or_logged(self):
        for code in [301, 302, 401, 403, 404]:
            api, opener = self.api([urllib.error.HTTPError("https://attacker.invalid/SECRET", code, "SECRET", {}, None)])
            with self.assertRaises(sync.Refused) as error:
                api.graphql(sync.BOARD_QUERY)
            self.assertNotIn("SECRET", str(error.exception))
            self.assertEqual(opener.open.call_count, 1)
        api, opener = self.api([io.BytesIO(b'{"errors":[{"message":"SECRET"}]}')])
        with self.assertRaises(sync.Refused) as error:
            api.graphql(sync.BOARD_QUERY)
        self.assertNotIn("SECRET", str(error.exception))
        self.assertEqual(opener.open.call_count, 1)
        self.assertIsNone(sync.NoRedirect().redirect_request(None, None, None, None, None, "https://attacker.invalid"))

    def test_rate_limit_and_network_exhaustion_stop_without_writes(self):
        for responses in [[urllib.error.HTTPError("url", 429, "SECRET", {}, None)] * 3, [OSError("SECRET")] * 3]:
            api, opener = self.api(responses)
            with self.assertRaises(sync.Refused):
                api.graphql(sync.BOARD_QUERY)
            self.assertEqual(opener.open.call_count, 3)

    def test_mutations_never_retry_uncertain_outcomes(self):
        for error in [OSError("SECRET"), urllib.error.HTTPError("url", 502, "SECRET", {}, None), ValueError("SECRET")]:
            api, opener = self.api([error])
            with self.assertRaises(sync.Refused) as refusal:
                api.graphql(sync.UPDATE_MUTATION, {}, mutation=True)
            self.assertIn("outcome unknown", str(refusal.exception))
            self.assertNotIn("SECRET", str(refusal.exception))
            self.assertEqual(opener.open.call_count, 1)
            self.sleep.assert_not_called()

    def test_external_or_injected_api_paths_refused(self):
        api, opener = self.api([])
        for path in ["https://attacker.invalid", "/repos/attacker/rozine/pulls/1", "/repos/rozine-rw/rozine/pulls/1;id"]:
            with self.assertRaises(sync.Refused):
                api.request(path)
        opener.open.assert_not_called()


class WorkflowBoundaryTest(unittest.TestCase):
    def test_workflow_gate_accepts_only_checked_out_dev_ancestors(self):
        import textwrap
        workflow = (Path(__file__).resolve().parents[1] / "workflows/board-sync.yml").read_text()
        step = "      - name: Require the pinned implementation to be merged into dev\n"
        program = textwrap.dedent(workflow.split(step, 1)[1].split("\n      - name:", 1)[0]
                                 .split("run: |\n", 1)[1])
        self.assertIn("fetch-depth: 0", workflow)
        self.assertLess(workflow.index(step), workflow.index("run: python3 .github/scripts/project_board_sync.py --check"))
        self.assertLess(workflow.index(step), workflow.index("- name: Mint dedicated installation token"))
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
            git("commit", "--allow-empty", "-m", "Unmerged malicious feature")
            feature = git("rev-parse", "HEAD")

            def gate(pin):
                return subprocess.run(["bash", "-c", program], cwd=directory,
                                      env={**os.environ, "BOARD_SYNC_TRUSTED_SHA": pin},
                                      capture_output=True, text=True, timeout=10)

            self.assertEqual(gate(feature).returncode, 1, "unmerged feature must fail")
            self.assertEqual(gate(base).returncode, 1, "checkout mismatch must fail")
            git("checkout", "--detach", base)
            self.assertEqual(gate(base).returncode, 0)
            git("update-ref", "-d", "refs/remotes/origin/dev")
            result = gate(base)
            self.assertEqual(result.returncode, 1, "missing dev history must fail")
            self.assertIn("no token minted", result.stderr)

    def test_configuration_gate_refuses_missing_key_before_minting(self):
        root = Path(__file__).resolve().parents[1]
        workflow = (root / "workflows/board-sync.yml").read_text()
        program = workflow.split("python3 - <<'PY'\n", 1)[1].split("\n          PY", 1)[0]
        import textwrap
        program = textwrap.dedent(program)
        env, _, _, _, _, _ = fixture()
        for key in ["HAS_PRIVATE_KEY", "BOARD_SYNC_TRUSTED_SHA", "BOARD_SYNC_CLIENT_ID",
                    "BOARD_SYNC_INSTALLATION_ID", "BOARD_SYNC_APP_SLUG"]:
            with self.subTest(key=key):
                values = {**os.environ, **env, "HAS_PRIVATE_KEY": "true", key: ""}
                result = subprocess.run([sys.executable, "-c", program], env=values,
                                        capture_output=True, text=True, timeout=10)
                self.assertEqual(result.returncode, 1)
                self.assertIn("no token minted", result.stderr)
        result = subprocess.run([sys.executable, "-c", program],
                                env={**os.environ, **env, "HAS_PRIVATE_KEY": "true"},
                                capture_output=True, text=True, timeout=10)
        self.assertEqual(result.returncode, 0)

    def test_checked_in_policy_is_inert_and_only_pinned_official_actions_get_credentials(self):
        root = Path(__file__).resolve().parents[1]
        sync.approved_rows(json.loads((root / "board-sync-allowlist.json").read_text()))
        workflow = (root / "workflows/board-sync.yml").read_text()
        self.assertIn("vars.BOARD_SYNC_ENABLED == 'true'", workflow)
        self.assertIn("cancel-in-progress: false", workflow)
        self.assertIn("permission-organization-projects:", workflow)
        self.assertIn("github.event_name == 'pull_request_target' && vars.BOARD_SYNC_WRITES_ENABLED == 'true'", workflow)
        self.assertIn("github.event.pull_request.head.repo.full_name == 'rozine-rw/rozine'", workflow)
        self.assertIn("ref: ${{ vars.BOARD_SYNC_TRUSTED_SHA }}", workflow)
        self.assertIn("persist-credentials: false", workflow)
        self.assertNotIn("schedule:", workflow)
        self.assertNotIn("GH_PROJECTS_TOKEN", workflow)
        self.assertNotIn("head.sha", workflow)
        self.assertNotIn("npm", workflow)
        import re
        actions = re.findall(r'uses: ([^\s]+)', workflow)
        self.assertEqual(len(actions), 2)
        for action in actions:
            self.assertRegex(action, r'^actions/(checkout|create-github-app-token)@[0-9a-f]{40}$')
        for run in re.findall(r'run: (.*?)(?=\n      - |\Z)', workflow, re.S):
            self.assertNotIn("${{", run, "event expressions must not enter shell programs")


if __name__ == "__main__":
    unittest.main()
