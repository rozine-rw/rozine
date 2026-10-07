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
REUSE_JOBS = ["Select CI scope", "Board sync offline tests", "Reuse POC validation evidence"]
REUSE_STEPS = ["Revalidate original POC proof without repeating passed tests", "Record original POC source", "Publish POC reuse receipt", "Require combined POC execution within ten minutes"]
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
        validation = env.get("VALIDATION", "auto")
        if validation not in ("auto", "poc", "full"):
            raise ValueError("unknown manual validation scope")
        if validation == "full":
            return "full"
        if ref in ("refs/heads/dev", "refs/heads/uat"):
            return "poc"
        if validation == "poc":
            raise ValueError("POC dispatch is restricted to dev/uat")
        return "full"
    if event == "pull_request" and env.get("PR_BASE") in ("dev", "uat"):
        return "poc"
    if event == "push" and ref in ("refs/heads/dev", "refs/heads/uat"):
        return "poc"
    return "full"


def validate_completed_budget(jobs, reused=False):
    names = REUSE_JOBS if reused else ["Select CI scope", *POC_JOBS]
    durations = {}
    for name in names:
        matches = [job for job in jobs if job["name"] == name]
        if len(matches) != 1 or matches[0]["conclusion"] != "success" or matches[0]["status"] != "completed":
            raise ValueError("budget requires complete successful execution")
        job = matches[0]
        start = datetime.datetime.fromisoformat(job["started_at"].replace("Z", "+00:00"))
        end = datetime.datetime.fromisoformat(job["completed_at"].replace("Z", "+00:00"))
        durations[name] = (end - start).total_seconds()
        if durations[name] < 0:
            raise ValueError("invalid execution interval")
    critical = max(durations["Board sync offline tests"], durations["Select CI scope"] + max(durations[name] for name in names if name not in ("Select CI scope", "Board sync offline tests")))
    if critical > 600:
        raise ValueError("completed combined POC execution exceeds ten minutes")


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
        validate_completed_budget(jobs)
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
        if run["head_branch"] not in ("dev", "uat") or tested != run["head_sha"]:
            raise ValueError("source is not exact dev/uat validation")
    else:
        raise ValueError("unsupported source event")


def trusted_run(run, repository, workflow_id):
    return (run.get("workflow_id") == workflow_id
            and run.get("path") == ".github/workflows/tests.yml"
            and run.get("head_repository", {}).get("full_name") == repository
            and run.get("event") in ("push", "workflow_dispatch", "pull_request"))


def download_evidence(repository, run, prefix="validation-tree"):
    name = f"{prefix}-{run['id']}-{run['run_attempt']}"
    artifacts = api(f"repos/{repository}/actions/runs/{run['id']}/artifacts?per_page=100")["artifacts"]
    matching = [item for item in artifacts if item["name"] == name and not item["expired"]]
    if len(matching) != 1:
        raise ValueError("missing, duplicate or expired evidence artifact")
    with tempfile.TemporaryDirectory(prefix="rozine-validation-") as directory:
        command("gh", "run", "download", str(run["id"]), "--repo", repository,
                "--name", name, "--dir", directory)
        proof = json.loads((Path(directory) / "tested-tree.json").read_text())
    if not isinstance(proof, dict):
        raise ValueError("evidence must be an object")
    return proof


def run_jobs(repository, run):
    return [job for page in pages(f"repos/{repository}/actions/runs/{run['id']}/attempts/{run['run_attempt']}/jobs?per_page=100") for job in page["jobs"]]


def validate_source(repository, run, tree, scope):
    proof = download_evidence(repository, run)
    if proof.get("tree_sha") != tree or proof.get("scope") != scope:
        raise ValueError("source tree or scope differs")
    commit = api(f"repos/{repository}/git/commits/{proof['tested_sha']}")
    validate_proof(proof, run, run_jobs(repository, run), commit, tree, repository, scope)
    if run["event"] == "pull_request":
        pull = api(f"repos/{repository}/pulls/{proof['pull_number']}")
        allowed = pull["base"]["ref"] == "dev" or (scope == "poc" and pull["base"]["ref"] == "uat" and pull["head"]["ref"] == "dev")
        if not allowed or pull["head"]["sha"] != run["head_sha"] or pull["head"]["repo"]["full_name"] != repository:
            raise ValueError("source PR is not trusted dev/uat validation")
    return proof


def attempt_order(run):
    # Rerunning an old ID can be newer than a subsequent successful run.
    timestamps = [run[key] for key in ("created_at", "run_started_at", "updated_at") if run.get(key)]
    latest = max(datetime.datetime.fromisoformat(value.replace("Z", "+00:00")) for value in timestamps)
    return (latest, run["id"])


def newer_run_blocks(repository, run, source, tree):
    # A later rerun attempt keeps the same run ID. Its status is authoritative.
    if run["id"] == source["id"]:
        return run["run_attempt"] != source["run_attempt"] or run["conclusion"] != "success"
    if attempt_order(run) < attempt_order(source) or (run["status"] == "completed" and run["conclusion"] == "success"):
        return False
    if run["head_sha"] == source["head_sha"]:
        return True
    commit = api(f"repos/{repository}/git/commits/{run['head_sha']}")
    if commit["tree"]["sha"] == tree:
        return True
    if run["event"] == "pull_request":
        # Failed PRs may have tested a different merge tree than their head.
        # If the merge artifact is absent/unverifiable, fail closed on reuse.
        proof = download_evidence(repository, run)
        expected = {"schema": 2, "repository": repository, "run_id": str(run["id"]), "run_attempt": str(run["run_attempt"])}
        if any(proof.get(key) != value for key, value in expected.items()) or not re.fullmatch(SHA, str(proof.get("tested_sha", ""))):
            raise ValueError("failed-run merge evidence has unverifiable provenance")
        tested = api(f"repos/{repository}/git/commits/{proof['tested_sha']}")
        if (tested["sha"] != proof["tested_sha"] or tested["tree"]["sha"] != proof.get("tree_sha")
                or len(tested["parents"]) != 2 or tested["parents"][1]["sha"] != run["head_sha"]):
            raise ValueError("failed-run merge tree does not bind the actual source head")
        return tested["tree"]["sha"] == tree
    return False


def reusable_run(env, scope):
    repository = env["GITHUB_REPOSITORY"]
    candidate = env["GITHUB_SHA"]
    if not re.fullmatch(SHA, candidate) or command("git", "rev-parse", "HEAD") != candidate:
        raise ValueError("checkout does not match candidate")
    tree = command("git", "rev-parse", "HEAD^{tree}")
    workflow_id = api(f"repos/{repository}/actions/workflows/tests.yml")["id"]
    runs = api(f"repos/{repository}/actions/workflows/tests.yml/runs?per_page=100")["workflow_runs"]
    runs = [run for run in runs if trusted_run(run, repository, workflow_id) and str(run["id"]) != env.get("GITHUB_RUN_ID")]
    for source in sorted(runs, key=attempt_order, reverse=True):
        if source["status"] != "completed" or source["conclusion"] != "success":
            continue
        if env.get("SOURCE_RUN") and str(source["id"]) != env["SOURCE_RUN"]:
            continue
        try:
            validate_source(repository, source, tree, scope)
        except (OSError, subprocess.SubprocessError, ValueError, KeyError, TypeError, AttributeError):
            continue
        # Do not silently fall back to an older success on a newer genuine
        # failure, cancellation, skipped run or unresolved matching attempt.
        if any(newer_run_blocks(repository, run, source, tree) for run in runs):
            return ""
        return str(source["id"])
    return ""


def staging_evidence(env):
    repository = env["GITHUB_REPOSITORY"]
    candidate = env["CANDIDATE_SHA"]
    if command("git", "rev-parse", "HEAD") != candidate:
        raise ValueError("admission checkout differs from candidate")
    tree = command("git", "rev-parse", "HEAD^{tree}")
    run = api(f"repos/{repository}/actions/runs/{env['EVIDENCE_RUN']}")
    workflow_id = api(f"repos/{repository}/actions/workflows/tests.yml")["id"]
    if (not trusted_run(run, repository, workflow_id) or run["head_sha"] != candidate
            or run["head_branch"] != "uat" or run["event"] not in ("push", "workflow_dispatch")
            or run["status"] != "completed" or run["conclusion"] != "success"):
        raise ValueError("staging requires successful exact-candidate UAT validation")
    try:
        proof = download_evidence(repository, run)
    except ValueError:
        proof = download_evidence(repository, run, "poc-reuse")
    scope = proof.get("scope")
    if scope not in ("poc", "full"):
        raise ValueError("unknown staging evidence scope")
    source = run
    if proof.get("kind") == "reuse":
        if scope != "poc":
            raise ValueError("staging cannot reuse full candidate coverage")
        expected = {"schema": 2, "repository": repository, "tested_sha": candidate, "tree_sha": tree,
                    "run_id": str(run["id"]), "run_attempt": str(run["run_attempt"])}
        if any(proof.get(key) != value for key, value in expected.items()):
            raise ValueError("reuse receipt identity mismatch")
        jobs = run_jobs(repository, run)
        if proof.get("required_jobs") != REUSE_JOBS or proof.get("required_steps") != REUSE_STEPS:
            raise ValueError("reuse receipt check set differs")
        validate_completed_budget(jobs, reused=True)
        for name in REUSE_JOBS:
            matches = [job for job in jobs if job["name"] == name]
            if len(matches) != 1 or matches[0]["conclusion"] != "success":
                raise ValueError("missing/unsuccessful reuse admission job")
        reuse = next(job for job in jobs if job["name"] == "Reuse POC validation evidence")
        for name in REUSE_STEPS:
            steps = [step for step in reuse.get("steps", []) if step["name"] == name]
            if len(steps) != 1 or steps[0]["conclusion"] != "success":
                raise ValueError("missing/unsuccessful reuse provenance/budget step")
        if not re.fullmatch(r"[0-9]+", str(proof.get("source_run", ""))) or int(proof["source_run"]) >= run["id"]:
            raise ValueError("reuse must name an earlier original run")
        source = api(f"repos/{repository}/actions/runs/{proof['source_run']}")
        if str(source["run_attempt"]) != proof.get("source_attempt"):
            raise ValueError("original source attempt changed")
    elif proof.get("tested_sha") != candidate:
        raise ValueError("executed UAT proof is not exact candidate")
    if not trusted_run(source, repository, workflow_id):
        raise ValueError("untrusted original source")
    validate_source(repository, source, tree, scope)
    lookup = dict(env, GITHUB_SHA=candidate, GITHUB_RUN_ID="", SOURCE_RUN=str(source["id"]))
    if reusable_run(lookup, scope) != str(source["id"]):
        raise ValueError("original evidence is unavailable or superseded by newer failure")
    return {"scope": scope, "source_run": str(source["id"]), "source_attempt": str(source["run_attempt"]), "tree_sha": tree, "required_jobs": POC_JOBS if scope == "poc" else FULL_JOBS}


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
    if sys.argv[1:] == ["admit-staging"]:
        print(json.dumps(staging_evidence(env)))
        return
    if sys.argv[1:] == ["record-reuse"]:
        source = api(f"repos/{env['GITHUB_REPOSITORY']}/actions/runs/{env['SOURCE_RUN']}")
        record(env, "poc")
        path = Path("ci-evidence/tested-tree.json")
        receipt = json.loads(path.read_text())
        receipt.update(kind="reuse", source_run=str(source["id"]), source_attempt=str(source["run_attempt"]), required_jobs=REUSE_JOBS, required_steps=REUSE_STEPS)
        path.write_text(json.dumps(receipt, indent=2) + "\n")
        return
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
    if scope == "poc" and (promotion or (env.get("GITHUB_EVENT_NAME") in ("push", "workflow_dispatch") and env.get("GITHUB_REF") in ("refs/heads/dev", "refs/heads/uat"))):
        try:
            source = reusable_run(env, scope)
        except (OSError, subprocess.SubprocessError, ValueError, KeyError, TypeError, AttributeError):
            pass
    with open(env["GITHUB_OUTPUT"], "a") as output:
        output.write(f"full={str(scope == 'full' and not source).lower()}\npoc={str(scope == 'poc' and not source).lower()}\nscope={scope}\nreused={str(bool(source)).lower()}\nsource_run={source}\n")
    print(f"Scope: {scope}; " + (f"reuse identical complete tree from run {source}" if source else "execute checks"))


if __name__ == "__main__":
    main()
