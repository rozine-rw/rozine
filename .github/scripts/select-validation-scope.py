#!/usr/bin/env python3
"""Select POC/full execution, or reuse fresh GitHub evidence for the exact tree."""

import datetime
import json
import os
import re
import subprocess
import sys
import tempfile
from pathlib import Path

FULL_JOBS = [
    "Board sync offline tests", "PHP 8.5 quality gate", "TypeScript/React quality gate",
    "PostgreSQL concurrency lane", "PHP gate negative controls", "Deployment admission negative controls",
    *[f"PHP 8.5 tests (shard {shard}/4)" for shard in range(1, 5)],
    *[f"PHP negative controls ({group})" for group in ["architecture", "business", "auditor", "coverage"]],
]
POC_JOBS = ["Board sync offline tests", "POC PHP safety and static checks", "POC web safety and static checks"]
POC_STEPS = ["Record POC validation tree", "Publish POC validation tree", "Require combined POC execution within ten minutes"]
SHA = r"[0-9a-f]{40}"


def command(*args):
    return subprocess.check_output(args, text=True, stderr=subprocess.PIPE, timeout=45).strip()


def api(endpoint):
    return json.loads(command("gh", "api", endpoint))


def pages(endpoint):
    return json.loads(command("gh", "api", "--paginate", "--slurp", endpoint))


def requested_scope(env):
    event, ref = env.get("GITHUB_EVENT_NAME"), env.get("GITHUB_REF")
    if event == "workflow_dispatch":
        if env.get("VALIDATION") != "full":
            raise ValueError("manual validation must explicitly request full")
        return "full"
    if event == "pull_request" and env.get("PR_BASE") in ("dev", "uat"):
        return "poc"
    if event == "push" and ref in ("refs/heads/dev", "refs/heads/uat"):
        return "poc"
    return "full"


def validate_proof(proof, run, jobs, commit, tree, repository, scope):
    required = POC_JOBS if scope == "poc" else FULL_JOBS
    if proof.get("scope") != scope or proof.get("required_jobs") != required:
        raise ValueError("validation scope/check set differs; POC is not full proof")
    expected = {"schema": 2, "repository": repository, "run_id": str(run["id"]),
                "run_attempt": str(run["run_attempt"]), "tree_sha": tree}
    if any(proof.get(key) != value for key, value in expected.items()):
        raise ValueError("evidence identity mismatch")
    tested = proof.get("tested_sha", "")
    if not isinstance(tested, str) or not re.fullmatch(SHA, tested):
        raise ValueError("invalid tested SHA")
    if commit.get("sha") != tested or commit["tree"]["sha"] != tree:
        raise ValueError("tested repository tree differs, including code/config/locks")
    if run["status"] != "completed" or run["conclusion"] != "success":
        raise ValueError("source run did not succeed")
    age = datetime.datetime.now(datetime.timezone.utc) - datetime.datetime.fromisoformat(run["created_at"].replace("Z", "+00:00"))
    if age.total_seconds() < 0 or age > datetime.timedelta(days=30):
        raise ValueError("evidence is stale")
    for name in required + (["Record validation tree"] if scope == "full" else []):
        matches = [job for job in jobs if job["name"] == name]
        if len(matches) != 1 or matches[0]["conclusion"] != "success":
            raise ValueError(f"missing, duplicate or unsuccessful job: {name}")
    if scope == "poc":
        job = next(job for job in jobs if job["name"] == "POC PHP safety and static checks")
        if proof.get("required_steps") != POC_STEPS:
            raise ValueError("POC proof omits publication/budget controls")
        for name in POC_STEPS:
            steps = [step for step in job.get("steps", []) if step["name"] == name]
            if len(steps) != 1 or steps[0]["conclusion"] != "success":
                raise ValueError(f"missing or unsuccessful POC evidence step: {name}")
    if run["event"] == "pull_request":
        parents = commit["parents"]
        if len(parents) != 2 or parents[1]["sha"] != run["head_sha"]:
            raise ValueError("tested merge does not contain the source PR head")
    elif run["event"] in ("push", "workflow_dispatch"):
        if run["head_branch"] != "dev" or tested != run["head_sha"]:
            raise ValueError("source is not exact dev validation")
    else:
        raise ValueError("unsupported source event")


def reusable_run(env, scope):
    repository = env["GITHUB_REPOSITORY"]
    candidate = env["GITHUB_SHA"]
    if not re.fullmatch(SHA, candidate) or command("git", "rev-parse", "HEAD") != candidate:
        raise ValueError("checkout does not match candidate")
    tree = command("git", "rev-parse", "HEAD^{tree}")
    # Bounded lookup: absent or expired evidence causes execution, never a green bypass.
    runs = api(f"repos/{repository}/actions/workflows/tests.yml/runs?per_page=100")["workflow_runs"]
    latest_heads = set()
    for run in sorted(runs, key=lambda item: item["id"], reverse=True):
        identity = (run["event"], run["head_sha"])
        if identity in latest_heads or str(run["id"]) == env.get("GITHUB_RUN_ID"):
            continue
        latest_heads.add(identity)
        if run["status"] != "completed" or run["conclusion"] != "success":
            continue
        try:
            artifacts = api(f"repos/{repository}/actions/runs/{run['id']}/artifacts?per_page=100")["artifacts"]
            name = f"validation-tree-{run['id']}-{run['run_attempt']}"
            matching = [item for item in artifacts if item["name"] == name and not item["expired"]]
            if len(matching) != 1:
                continue
            with tempfile.TemporaryDirectory(prefix="rozine-validation-") as directory:
                command("gh", "run", "download", str(run["id"]), "--repo", repository,
                        "--name", name, "--dir", directory)
                proof = json.loads((Path(directory) / "tested-tree.json").read_text())
            if proof.get("tree_sha") != tree or proof.get("scope") != scope:
                continue
            commit = api(f"repos/{repository}/git/commits/{proof['tested_sha']}")
            jobs = [job for page in pages(f"repos/{repository}/actions/runs/{run['id']}/attempts/{run['run_attempt']}/jobs?per_page=100") for job in page["jobs"]]
            validate_proof(proof, run, jobs, commit, tree, repository, scope)
            if run["event"] == "pull_request":
                pull = api(f"repos/{repository}/pulls/{proof['pull_number']}")
                if pull["base"]["ref"] != "dev" or pull["head"]["sha"] != run["head_sha"] or pull["head"]["repo"]["full_name"] != repository:
                    raise ValueError("source PR is not same-repository dev validation")
            return str(run["id"])
        except (OSError, subprocess.SubprocessError, ValueError, KeyError, TypeError, AttributeError):
            continue
    return ""


def record(env, scope):
    required = POC_JOBS if scope == "poc" else FULL_JOBS
    proof = {"schema": 2, "scope": scope, "repository": env["GITHUB_REPOSITORY"],
             "tested_sha": command("git", "rev-parse", "HEAD"), "tree_sha": command("git", "rev-parse", "HEAD^{tree}"),
             "run_id": env["GITHUB_RUN_ID"], "run_attempt": env["GITHUB_RUN_ATTEMPT"],
             "pull_number": env.get("PR_NUMBER", ""), "required_jobs": required}
    if scope == "poc":
        proof["required_steps"] = POC_STEPS
    Path("ci-evidence").mkdir(exist_ok=True)
    Path("ci-evidence/tested-tree.json").write_text(json.dumps(proof, indent=2) + "\n")


def main():
    env = os.environ
    if sys.argv[1:] in (["verify-full"], ["verify-poc"]):
        scope = sys.argv[1].removeprefix("verify-")
        source = reusable_run(env, scope)
        if not source:
            raise ValueError(f"no fresh identical-tree {scope} proof")
        print(source)
        return
    if len(sys.argv) == 3 and sys.argv[1] == "record":
        if sys.argv[2] not in ("poc", "full"):
            raise ValueError("unknown proof scope")
        record(env, sys.argv[2])
        return
    scope = requested_scope(env)
    source = ""
    # Dev PRs execute. A dev->uat PR may reuse only its actual proposed merge tree.
    promotion = env.get("GITHUB_EVENT_NAME") == "pull_request" and env.get("PR_BASE") == "uat" and env.get("PR_HEAD_BRANCH") == "dev"
    if promotion or (env.get("GITHUB_EVENT_NAME") == "push" and env.get("GITHUB_REF") in ("refs/heads/dev", "refs/heads/uat")):
        try:
            source = reusable_run(env, scope)
        except (OSError, subprocess.SubprocessError, ValueError, KeyError, TypeError):
            pass
    with open(env["GITHUB_OUTPUT"], "a") as output:
        output.write(f"full={str(scope == 'full' and not source).lower()}\npoc={str(scope == 'poc' and not source).lower()}\nscope={scope}\nreused={str(bool(source)).lower()}\nsource_run={source}\n")
    print(f"Scope: {scope}; " + (f"reuse identical complete tree from run {source}" if source else "execute checks"))


if __name__ == "__main__":
    main()
