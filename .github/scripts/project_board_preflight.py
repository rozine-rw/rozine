"""Read-only App preflight for one explicitly approved push to dev."""

import argparse
import json
import os
import sys
from pathlib import Path

import project_board_sync as board


def eligible_push(event, env):
    board.require(env.get("BOARD_SYNC_PREFLIGHT_ENABLED") == "true", "preflight disabled")
    board.require(env.get("BOARD_SYNC_ENABLED", "") in ("", "false")
                  and env.get("BOARD_SYNC_WRITES_ENABLED") == "false", "sync enabled or writes not explicitly disabled")
    for key, pattern in [("BOARD_SYNC_TRUSTED_SHA", r"[0-9a-f]{40}"),
                         ("BOARD_SYNC_PREFLIGHT_TRIGGER_SHA", r"[0-9a-f]{40}"),
                         ("BOARD_SYNC_CLIENT_ID", r"Iv[0-9A-Za-z.]+"),
                         ("BOARD_SYNC_INSTALLATION_ID", r"[1-9][0-9]*"),
                         ("BOARD_SYNC_APP_SLUG", r"[a-z0-9-]+")]:
        board.require(board.match(pattern, env.get(key)), "invalid preflight configuration")
    board.require(env.get("GITHUB_EVENT_NAME") == "push" and env.get("GITHUB_REF") == "refs/heads/dev"
                  and env.get("GITHUB_REPOSITORY") == board.REPOSITORY
                  and env.get("GITHUB_RUN_ATTEMPT") == "1", "wrong preflight context or rerun")
    repository = event["repository"]
    board.require(repository["full_name"] == board.REPOSITORY and repository["id"] == board.REPOSITORY_ID
                  and repository["owner"]["node_id"] == board.ORGANIZATION_ID, "wrong push repository")
    board.require(event["ref"] == "refs/heads/dev" and event.get("created") is False
                  and event.get("deleted") is False and event.get("forced") is False, "unsafe push")
    board.require(board.match(r"[0-9a-f]{40}", event["after"])
                  and event["after"] == env.get("GITHUB_SHA")
                  and event["head_commit"]["id"] == event["after"], "wrong push head")
    commits = event["commits"]
    board.require(isinstance(commits, list) and 0 < len(commits) < 2048, "missing or potentially truncated commits")
    ids = [commit["id"] for commit in commits]
    board.require(all(board.match(r"[0-9a-f]{40}", value) for value in ids)
                  and len(set(ids)) == len(ids) and event["after"] in ids, "invalid push commits")
    return env["BOARD_SYNC_PREFLIGHT_TRIGGER_SHA"] in ids


class ReadOnlyAPI(board.API):
    def request(self, path, payload=None, mutation=False):
        board.require(mutation is False, "preflight mutation refused")
        if path == "/installation/repositories?per_page=100":
            board.require(payload is None, "preflight POST refused")
        else:
            board.require(path == "/graphql" and isinstance(payload, dict)
                          and set(payload) == {"query", "variables"} and payload["query"] == board.BOARD_QUERY,
                          "preflight query refused")
            variables = payload["variables"]
            board.require(isinstance(variables, dict) and set(variables) == {"cursor"}
                          and (variables["cursor"] is None or isinstance(variables["cursor"], str)),
                          "preflight variables refused")
        return super().request(path, payload, mutation=False)


def inspect(api, env):
    board.validate_audience(api, env)
    board.board_snapshot(api)
    return "preflight passed; project identity/access checked; no cards selected or changed"


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--check", action="store_true")
    args = parser.parse_args()
    try:
        env = os.environ
        eligible = eligible_push(json.loads(Path(env["GITHUB_EVENT_PATH"]).read_text()), env)
        if args.check:
            with open(env["GITHUB_OUTPUT"], "a") as output:
                output.write(f'eligible={str(eligible).lower()}\n')
            print("preflight event checked; " + ("eligible" if eligible else "no approved push"))
        elif eligible:
            print(inspect(ReadOnlyAPI(env.get("BOARD_SYNC_TOKEN")), env))
        else:
            print("no approved preflight push")
    except board.Refused as error:
        print(f"Board preflight refused: {error}", file=sys.stderr)
        return 1
    except (KeyError, TypeError, ValueError, OSError, AttributeError):
        print("Board preflight refused: malformed or incomplete input/response", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
