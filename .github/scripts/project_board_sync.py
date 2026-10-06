#!/usr/bin/env python3
"""Opt-in status handoff for one existing Rozine Delivery PR card. No imports."""

import argparse
import json
import os
import re
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

REPOSITORY = "rozine-rw/rozine"
REPOSITORY_ID = 1313583414
REPOSITORY_NODE_ID = "R_kgDOTkuxNg"
ORGANIZATION_ID = "O_kgDOEnR3Nw"
PROJECT_ID = "PVT_kwDOEnR3N84Blwsz"
PROJECT_URL = "https://github.com/orgs/rozine-rw/projects/1"
STATUS_ID = "PVTSSF_lADOEnR3N84BlwszzhkcPcw"
OPTIONS = {"Backlog": "f75ad846", "Ready": "30c08bd3", "In progress": "47fc9ee4",
           "Review": "10c0a544", "Blocked": "ae437e8e", "Done": "98236657"}
ALLOWLIST = Path(__file__).resolve().parents[1] / "board-sync-allowlist.json"

BOARD_QUERY = """
query Board($cursor: String) {
  organization(login: "rozine-rw") {
    id login
    projectV2(number: 1) {
      id number title url closed public
      fields(first: 100) {
        pageInfo { hasNextPage }
        nodes { ... on ProjectV2SingleSelectField { id name options { id name } } }
      }
      items(first: 100, after: $cursor) {
        pageInfo { hasNextPage endCursor }
        nodes {
          id isArchived
          content {
            __typename
            ... on PullRequest { id number repository { id nameWithOwner } }
            ... on Issue { id number repository { id nameWithOwner } }
          }
          fieldValues(first: 100) {
            pageInfo { hasNextPage }
            nodes { ... on ProjectV2ItemFieldSingleSelectValue {
              name optionId field { ... on ProjectV2SingleSelectField { id } }
            } }
          }
        }
      }
    }
  }
}
"""
UPDATE_MUTATION = """
mutation Handoff($project: ID!, $item: ID!, $field: ID!, $option: String!) {
  updateProjectV2ItemFieldValue(input: {
    projectId: $project, itemId: $item, fieldId: $field,
    value: { singleSelectOptionId: $option }
  }) { projectV2Item { id } }
}
"""


class Refused(Exception):
    """Only constant, non-sensitive refusal messages reach workflow logs."""


def require(condition, message):
    if not condition:
        raise Refused(message)


def match(pattern, value):
    return isinstance(value, str) and re.fullmatch(pattern, value) is not None


def validate_config(env):
    require(env.get("BOARD_SYNC_ENABLED") == "true", "sync disabled")
    require(env.get("BOARD_SYNC_WRITES_ENABLED", "") in ("", "false", "true"), "invalid write mode")
    require(match(r"[0-9a-f]{40}", env.get("BOARD_SYNC_TRUSTED_SHA")), "missing trusted commit")
    require(match(r"Iv[0-9A-Za-z.]+", env.get("BOARD_SYNC_CLIENT_ID")), "missing App client ID")
    require(match(r"[1-9][0-9]*", env.get("BOARD_SYNC_INSTALLATION_ID")), "missing installation ID")
    require(match(r"[a-z0-9-]+", env.get("BOARD_SYNC_APP_SLUG")), "missing App slug")
    require(env.get("GITHUB_REPOSITORY") == REPOSITORY, "wrong workflow repository")


def approved_rows(document):
    require(isinstance(document, dict) and set(document) == {"schema", "pull_requests"}
            and document["schema"] == 1 and isinstance(document["pull_requests"], list), "invalid allowlist")
    rows = document["pull_requests"]
    numbers, nodes, items = set(), set(), set()
    for row in rows:
        require(isinstance(row, dict) and set(row) == {"number", "node_id", "item_id", "head_sha"},
                "invalid approval row")
        require(type(row["number"]) is int and row["number"] > 0
                and match(r"PR_[A-Za-z0-9_-]+", row["node_id"])
                and match(r"PVTI_[A-Za-z0-9_-]+", row["item_id"])
                and match(r"[0-9a-f]{40}", row["head_sha"]), "invalid approval identity")
        require(row["number"] not in numbers and row["node_id"] not in nodes and row["item_id"] not in items,
                "duplicate approval")
        numbers.add(row["number"])
        nodes.add(row["node_id"])
        items.add(row["item_id"])
    return rows


def select_approval(event, env, document):
    validate_config(env)
    rows = approved_rows(document)
    repository = event["repository"]
    require(repository["full_name"] == REPOSITORY and repository["id"] == REPOSITORY_ID
            and repository["owner"]["node_id"] == ORGANIZATION_ID, "wrong event repository")
    if env.get("GITHUB_EVENT_NAME") == "workflow_dispatch":
        require(env.get("GITHUB_REF") == "refs/heads/dev", "preflight requires dev")
        number = env.get("BOARD_SYNC_PULL_NUMBER", "")
        if number == "":
            return None, True
        require(match(r"[1-9][0-9]{0,9}", number), "invalid preflight PR number")
        matches = [row for row in rows if row["number"] == int(number)]
        require(len(matches) == 1, "preflight PR is not approved")
        return matches[0], True
    if env.get("GITHUB_EVENT_NAME") != "pull_request_target" or event.get("action") != "ready_for_review":
        return None, False
    pull = event["pull_request"]
    require(pull["base"]["ref"] == "dev" and pull["base"]["repo"]["id"] == REPOSITORY_ID,
            "wrong PR base")
    require(pull["head"]["repo"]["id"] == REPOSITORY_ID
            and pull["head"]["repo"]["full_name"] == REPOSITORY, "fork PR refused")
    matches = [row for row in rows if row["number"] == pull["number"]]
    if not matches:
        return None, False
    row = matches[0]
    require(pull["node_id"] == row["node_id"] and pull["head"]["sha"] == row["head_sha"],
            "event differs from approved PR head")
    return row, True


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class API:
    def __init__(self, token, opener=None, sleep=time.sleep):
        require(bool(token), "missing installation token")
        self.token = token
        self.opener = opener or urllib.request.build_opener(NoRedirect())
        self.sleep = sleep

    def request(self, path, payload=None, mutation=False):
        # Paths and GraphQL documents are constants; payload data goes only into JSON variables.
        require(path in ("/graphql", "/installation/repositories?per_page=100")
                or match(r"/repos/rozine-rw/rozine/pulls/[1-9][0-9]*", path), "API path refused")
        request = urllib.request.Request("https://api.github.com" + path,
            data=None if payload is None else json.dumps(payload).encode(), headers={
                "Authorization": "Bearer " + self.token, "Accept": "application/vnd.github+json",
                "Content-Type": "application/json", "X-GitHub-Api-Version": "2022-11-28",
            })
        for attempt in range(3 if not mutation else 1):
            try:
                with self.opener.open(request, timeout=20) as response:
                    data = json.load(response)
                require(isinstance(data, dict) and not data.get("errors"),
                        "mutation response ambiguous; inspect before rerun" if mutation else "API rejected request")
                return data
            except urllib.error.HTTPError as error:
                if not mutation and (error.code == 429 or error.code >= 500) and attempt < 2:
                    self.sleep(2 ** attempt)
                    continue
                raise Refused("mutation outcome unknown; inspect before rerun" if mutation
                              else "API read refused") from None
            except (OSError, ValueError):
                if not mutation and attempt < 2:
                    self.sleep(2 ** attempt)
                    continue
                raise Refused("mutation outcome unknown; inspect before rerun" if mutation
                              else "API read unavailable") from None

    def graphql(self, query, variables=None, mutation=False):
        return self.request("/graphql", {"query": query, "variables": variables or {}}, mutation)["data"]


def validate_audience(api, env):
    require(env.get("BOARD_SYNC_ACTUAL_INSTALLATION_ID") == env["BOARD_SYNC_INSTALLATION_ID"]
            and env.get("BOARD_SYNC_ACTUAL_APP_SLUG") == env["BOARD_SYNC_APP_SLUG"], "wrong App installation")
    audience = api.request("/installation/repositories?per_page=100")
    require(audience["total_count"] == 1 and len(audience["repositories"]) == 1, "token audience too broad")
    repository = audience["repositories"][0]
    require(repository["id"] == REPOSITORY_ID and repository["full_name"] == REPOSITORY
            and repository["owner"]["node_id"] == ORGANIZATION_ID, "wrong token repository")


def board_snapshot(api):
    items, cursor, seen_cursors, seen_items = [], None, set(), set()
    for _ in range(20):
        organization = api.graphql(BOARD_QUERY, {"cursor": cursor})["organization"]
        require(organization["id"] == ORGANIZATION_ID and organization["login"] == "rozine-rw",
                "wrong organization")
        project = organization["projectV2"]
        require(project["id"] == PROJECT_ID and project["number"] == 1 and project["url"] == PROJECT_URL
                and project["title"] == "Rozine Delivery" and project["closed"] is False
                and project["public"] is False, "wrong or unavailable project")
        fields = project["fields"]
        require(fields["pageInfo"]["hasNextPage"] is False, "project fields truncated")
        statuses = [field for field in fields["nodes"] if field.get("name") == "Status"]
        require(len(statuses) == 1 and statuses[0]["id"] == STATUS_ID, "wrong Status field")
        options = statuses[0]["options"]
        require(len(options) == len(OPTIONS) and {o["name"]: o["id"] for o in options} == OPTIONS,
                "wrong Status options")
        for item in project["items"]["nodes"]:
            require(item["id"] not in seen_items, "duplicate item page")
            seen_items.add(item["id"])
            require(item["fieldValues"]["pageInfo"]["hasNextPage"] is False, "item fields truncated")
            items.append(item)
        info = project["items"]["pageInfo"]
        if info["hasNextPage"] is False:
            return items
        cursor = info["endCursor"]
        require(isinstance(cursor, str) and cursor and cursor not in seen_cursors, "invalid item pagination")
        seen_cursors.add(cursor)
    raise Refused("board page limit exceeded")


def live_pull(api, row):
    pull = api.request(f'/repos/{REPOSITORY}/pulls/{row["number"]}')
    require(pull["number"] == row["number"] and pull["node_id"] == row["node_id"]
            and pull["base"]["repo"]["id"] == REPOSITORY_ID and pull["base"]["ref"] == "dev"
            and pull["head"]["repo"]["id"] == REPOSITORY_ID
            and pull["head"]["repo"]["full_name"] == REPOSITORY, "live PR identity mismatch")
    return (pull["head"]["sha"] == row["head_sha"] and pull["state"] == "open"
            and pull["draft"] is False and pull["merged"] is False)


def eligible_item(items, row):
    matches = [item for item in items if item.get("content")
               and item["content"].get("id") == row["node_id"]]
    require(len(matches) == 1, "linked card missing or duplicated")
    item = matches[0]
    content = item["content"]
    require(item["id"] == row["item_id"] and content["__typename"] == "PullRequest"
            and content["number"] == row["number"]
            and content["repository"]["id"] == REPOSITORY_NODE_ID
            and content["repository"]["nameWithOwner"] == REPOSITORY, "wrong linked card")
    statuses = [value for value in item["fieldValues"]["nodes"] if value.get("field", {}).get("id") == STATUS_ID]
    require(len(statuses) == 1, "Status missing or ambiguous")
    status = statuses[0]
    require(OPTIONS.get(status["name"]) == status["optionId"], "invalid card Status")
    return item["isArchived"] is False and status["name"] == "In progress"


def sync(api, env, row):
    validate_audience(api, env)
    items = board_snapshot(api)
    if row is None:
        return "preflight passed; no card selected"
    if not live_pull(api, row):
        return "preserved; PR is not at the approved open review head"
    if not eligible_item(items, row):
        return "preserved; current card state is human controlled or already Review"
    write = env.get("GITHUB_EVENT_NAME") == "pull_request_target" and env.get("BOARD_SYNC_WRITES_ENABLED") == "true"
    if not write:
        return "dry-run; approved card would move In progress to Review"
    # Re-read immediately before the sole mutation. Projects has no compare-and-swap;
    # serialization protects workflow writers, but cannot lock human edits.
    if not eligible_item(board_snapshot(api), row) or not live_pull(api, row):
        return "preserved; state changed during inspection"
    result = api.graphql(UPDATE_MUTATION, {"project": PROJECT_ID, "item": row["item_id"],
        "field": STATUS_ID, "option": OPTIONS["Review"]}, mutation=True)
    require(result["updateProjectV2ItemFieldValue"]["projectV2Item"]["id"] == row["item_id"],
            "mutation response ambiguous; inspect before rerun")
    return "updated approved card to Review; acceptance remains human controlled"


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--check", action="store_true")
    args = parser.parse_args()
    try:
        env = os.environ
        row, eligible = select_approval(json.loads(Path(env["GITHUB_EVENT_PATH"]).read_text()),
                                       env, json.loads(ALLOWLIST.read_text()))
        if args.check:
            with open(env["GITHUB_OUTPUT"], "a") as output:
                output.write(f'eligible={str(eligible).lower()}\n')
            print("event/configuration checked; " + ("eligible" if eligible else "no approved handoff"))
        elif eligible:
            print(sync(API(env.get("BOARD_SYNC_TOKEN")), env, row))
        else:
            print("no approved handoff")
    except Refused as error:
        print(f"Board sync refused: {error}", file=sys.stderr)
        return 1
    except (KeyError, TypeError, ValueError, OSError, AttributeError):
        print("Board sync refused: malformed or incomplete input/response", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
