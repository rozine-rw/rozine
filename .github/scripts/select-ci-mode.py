#!/usr/bin/env python3
"""Reuse complete PR evidence only for the identical tree merged into dev."""

import json
import os
import re
import subprocess
import tempfile
from pathlib import Path


REQUIRED_JOBS = [
    "Board sync offline tests",
    "PHP 8.5 quality gate",
    "TypeScript/React quality gate",
    "PostgreSQL concurrency lane",
    "PHP gate negative controls",
    "Deployment admission negative controls",
    "Record tested PR tree",
    *[f"PHP negative controls ({group})" for group in ["architecture", "business", "auditor", "coverage"]],
]


def command(*arguments):
    return subprocess.check_output(arguments, text=True, stderr=subprocess.PIPE, timeout=30).strip()


def pages(endpoint):
    return json.loads(command("gh", "api", "--paginate", "--slurp", endpoint))


def reusable_run():
    repository = os.environ["GITHUB_REPOSITORY"]
    candidate = os.environ["GITHUB_SHA"]
    if not re.fullmatch(r"[0-9a-f]{40}", candidate) or command("git", "rev-parse", "HEAD") != candidate:
        raise ValueError("checkout does not match the dev push")

    pulls = [pull for page in pages(f"repos/{repository}/commits/{candidate}/pulls?per_page=100") for pull in page]
    pulls = [pull for pull in pulls if pull["base"]["ref"] == "dev" and pull["merged_at"]
             and pull["merge_commit_sha"] == candidate]
    if len(pulls) != 1:
        raise ValueError("push is not one identified merged dev PR")
    pull = pulls[0]
    head = pull["head"]["sha"]
    runs = [run for page in pages(f"repos/{repository}/actions/workflows/tests.yml/runs?event=pull_request&head_sha={head}&per_page=100")
            for run in page["workflow_runs"] if run["event"] == "pull_request" and run["head_sha"] == head]
    if not runs:
        raise ValueError("no PR test run")
    run = max(runs, key=lambda item: item["id"])
    if run["status"] != "completed" or run["conclusion"] != "success":
        raise ValueError("latest PR run has not succeeded")
    run_id, attempt = run["id"], run["run_attempt"]
    jobs = [job for page in pages(f"repos/{repository}/actions/runs/{run_id}/attempts/{attempt}/jobs?per_page=100") for job in page["jobs"]]
    for required in REQUIRED_JOBS:
        matches = [job for job in jobs if job["name"] == required]
        if len(matches) != 1 or matches[0]["conclusion"] != "success":
            raise ValueError(f"missing or unsuccessful PR job: {required}")

    with tempfile.TemporaryDirectory(prefix="rozine-ci-evidence-") as directory:
        command("gh", "run", "download", str(run_id), "--repo", repository,
                "--name", f"tested-pr-tree-{run_id}-{attempt}", "--dir", directory)
        evidence = json.loads((Path(directory) / "tested-tree.json").read_text())
    if not isinstance(evidence, dict):
        raise ValueError("PR evidence must be a JSON object")
    tested = evidence.get("tested_sha", "")
    if not isinstance(tested, str) or not re.fullmatch(r"[0-9a-f]{40}", tested):
        raise ValueError("PR evidence has no immutable tested commit")
    commit = json.loads(command("gh", "api", f"repos/{repository}/git/commits/{tested}"))
    if not isinstance(commit, dict) or commit.get("sha") != tested:
        raise ValueError("tested commit is unavailable from the repository")
    parents = commit["parents"]
    if len(parents) != 2 or parents[1]["sha"] != head:
        raise ValueError("tested commit is not a proposed merge of this PR head")
    candidate_tree = command("git", "rev-parse", "HEAD^{tree}")
    if commit["tree"]["sha"] != candidate_tree:
        raise ValueError("GitHub's tested commit tree differs from the dev tree")
    expected = {
        "schema": 1,
        "repository": repository,
        "pull_number": str(pull["number"]),
        "head_sha": head,
        "run_id": str(run_id),
        "run_attempt": str(attempt),
        "tree_sha": candidate_tree,
    }
    if any(evidence.get(key) != value for key, value in expected.items()):
        raise ValueError("PR evidence does not identify this exact merged tree")
    return run_id


def main():
    source_run = None
    if os.environ.get("GITHUB_EVENT_NAME") == "push" and os.environ.get("GITHUB_REF") == "refs/heads/dev":
        try:
            source_run = reusable_run()
        except (OSError, subprocess.SubprocessError, ValueError, KeyError, TypeError, IndexError) as error:
            print(f"Full checks required: {error}")
    full = "true" if source_run is None else "false"
    with open(os.environ["GITHUB_OUTPUT"], "a") as output:
        output.write(f"full={full}\nsource_run={source_run or ''}\n")
    if source_run is not None:
        print(f"Identical dev tree passed PR run {source_run}; run the post-merge smoke check.")
    else:
        print("Running all authoritative quality gates.")


if __name__ == "__main__":
    main()
