#!/usr/bin/env python3
"""Select POC/full execution, or reuse fresh GitHub evidence for the exact tree."""

import datetime
import json
import os
import re
import subprocess
import sys
import tempfile
import time
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
MERGE_IDENTITY_STEPS = ["Record immutable PR merge identity", "Publish immutable PR merge identity"]
IDENTITY_JOB = "Identify CI validation tree"
IDENTITY_STEPS = ["Record immutable POC coordination identity", "Publish immutable POC coordination identity"]
DECISION_STEPS = ["Reuse only original successful evidence for an identical dev/UAT tree", "Publish canonical validation decision"]
VALIDATION_JOBS = [*POC_JOBS[1:], "Reuse POC validation evidence"]
SHA = r"[0-9a-f]{40}"


class MissingEvidence(ValueError):
    """The complete artifact inventory contains no artifact with this name."""


class CoordinationRefusal(ValueError):
    """A bounded, public reason code; never contains API output or credentials."""


class PublicationPending(CoordinationRefusal):
    """Native job publication is still in progress; bounded polling is safe."""


class PreflightPublicationPending(PublicationPending):
    """The independent identity job can finish while selection owns the lock."""


def diagnostic(reason, run=None):
    suffix = f" run={run['id']} attempt={run['run_attempt']}" if run else ""
    print(f"CI coordination: {reason}{suffix}", file=sys.stderr)


def command(*args, timeout=45):
    return subprocess.check_output(args, text=True, stderr=subprocess.PIPE, timeout=timeout).strip()


def api(endpoint, *, timeout=45):
    return json.loads(command("gh", "api", endpoint, timeout=timeout))


def pages(endpoint, *, timeout=45):
    return json.loads(command("gh", "api", "--paginate", "--slurp", endpoint, timeout=timeout))


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
    names = [*names, *([IDENTITY_JOB] if any(job["name"] == IDENTITY_JOB for job in jobs) else [])]
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
    critical = max(durations["Board sync offline tests"], durations.get(IDENTITY_JOB, 0) + durations["Select CI scope"] + max(durations[name] for name in names if name not in (IDENTITY_JOB, "Select CI scope", "Board sync offline tests")))
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


def artifact_inventory(repository, run):
    endpoint = f"repos/{repository}/actions/runs/{run['id']}/artifacts?per_page=100"
    first = api(endpoint)
    total = first["total_count"]
    if not isinstance(total, int) or not 0 <= total <= 1000:
        raise ValueError("artifact inventory exceeds the verified lookup bound")
    responses = [first] if len(first["artifacts"]) == total else pages(endpoint)
    artifacts = [item for response in responses for item in response["artifacts"]]
    if (any(response["total_count"] != total for response in responses)
            or len(artifacts) != total or len({item["id"] for item in artifacts}) != total):
        raise ValueError("artifact inventory is incomplete or changed during pagination")
    return artifacts


def download_evidence(repository, run, prefix="validation-tree"):
    name = f"{prefix}-{run['id']}-{run['run_attempt']}"
    artifacts = artifact_inventory(repository, run)
    matching = [item for item in artifacts if item["name"] == name]
    if not matching:
        raise MissingEvidence("evidence artifact is absent from complete inventory")
    if len(matching) != 1 or matching[0]["expired"]:
        raise ValueError("missing, duplicate or expired evidence artifact")
    with tempfile.TemporaryDirectory(prefix="rozine-validation-") as directory:
        command("gh", "run", "download", str(run["id"]), "--repo", repository,
                "--name", name, "--dir", directory)
        filename = {"pr-merge-identity": "merge-identity.json", "ci-coordination-identity": "coordination-identity.json",
                    "ci-coordination-decision": "coordination-decision.json"}.get(prefix, "tested-tree.json")
        proof = json.loads((Path(directory) / filename).read_text())
    if not isinstance(proof, dict):
        raise ValueError("evidence must be an object")
    return proof


def run_jobs(repository, run):
    return [job for page in pages(f"repos/{repository}/actions/runs/{run['id']}/attempts/{run['run_attempt']}/jobs?per_page=100") for job in page["jobs"]]


def same_attempt(repository, run):
    latest = api(f"repos/{repository}/actions/runs/{run['id']}")
    for key in ("id", "run_attempt", "head_sha", "event", "workflow_id", "path", "head_repository"):
        if latest.get(key) != run.get(key):
            diagnostic("run_or_attempt_changed", run)
            raise CoordinationRefusal("run_or_attempt_changed")
    return latest


def successful_steps(jobs, name, steps):
    matches = [job for job in jobs if job["name"] == name]
    if len(matches) == 1 and matches[0].get("conclusion") not in (None, "success"):
        raise CoordinationRefusal("identity_or_decision_job_not_successful")
    if len(matches) == 1:
        for step in steps:
            if any(item["name"] == step and item.get("conclusion") not in (None, "success") for item in matches[0].get("steps", [])):
                raise CoordinationRefusal("identity_or_decision_step_not_successful")
    if len(matches) == 1 and matches[0].get("status") in ("queued", "pending", "waiting", "in_progress"):
        raise PublicationPending("coordination_publication_in_progress")
    if len(matches) != 1 or matches[0].get("status") != "completed" or matches[0].get("conclusion") != "success":
        raise CoordinationRefusal("identity_or_decision_job_not_successful")
    for step in steps:
        found = [item for item in matches[0].get("steps", []) if item["name"] == step]
        if len(found) != 1 or found[0].get("conclusion") != "success":
            raise CoordinationRefusal("identity_or_decision_step_not_successful")


def coordination_identity(repository, run):
    proof = download_evidence(repository, run, "ci-coordination-identity")
    expected = {"schema": 1, "protocol": 1, "kind": "poc-coordination-identity", "scope": "poc",
                "repository": repository, "run_id": str(run["id"]), "run_attempt": str(run["run_attempt"]),
                "event": run["event"], "head_sha": run["head_sha"]}
    if any(proof.get(key) != value for key, value in expected.items()):
        raise CoordinationRefusal("coordination_identity_binding_mismatch")
    if any(not re.fullmatch(SHA, str(proof.get(key, ""))) for key in ("tested_sha", "tree_sha", "head_sha")):
        raise CoordinationRefusal("coordination_identity_invalid_sha")
    commit = api(f"repos/{repository}/git/commits/{proof['tested_sha']}")
    if commit.get("sha") != proof["tested_sha"] or commit["tree"]["sha"] != proof["tree_sha"]:
        raise CoordinationRefusal("coordination_identity_native_tree_mismatch")
    if run["event"] == "pull_request":
        allowed = proof.get("base_ref") == "dev" or (proof.get("base_ref") == "uat" and run["head_branch"] == "dev")
        if (not allowed or not re.fullmatch(r"[1-9][0-9]*", str(proof.get("pull_number", "")))
                or [parent["sha"] for parent in commit["parents"]] != [proof.get("base_sha"), run["head_sha"]]):
            raise CoordinationRefusal("coordination_identity_pr_contract_mismatch")
    elif run["event"] not in ("push", "workflow_dispatch") or run.get("head_branch") not in ("dev", "uat") or proof["tested_sha"] != run["head_sha"]:
        raise CoordinationRefusal("coordination_identity_target_mismatch")
    for read in range(3):
        jobs = run_jobs(repository, run)
        try:
            successful_steps(jobs, IDENTITY_JOB, IDENTITY_STEPS)
        except PublicationPending:
            latest = same_attempt(repository, run)
            identity_jobs = [job for job in jobs if job["name"] == IDENTITY_JOB]
            contradictory = latest["status"] == "completed" or identity_jobs[0].get("conclusion") is not None
            if not contradictory:
                raise PreflightPublicationPending("peer_preflight_identity_publication_in_progress") from None
            if read == 2:
                raise CoordinationRefusal("identity_job_status_inconsistent") from None
            diagnostic("retrying_inconsistent_identity_job_status", run)
            time.sleep(1)
            continue
        same_attempt(repository, run)
        return proof


def coordination_decision(repository, run, identity):
    proof = download_evidence(repository, run, "ci-coordination-decision")
    expected = {key: identity[key] for key in ("schema", "protocol", "scope", "repository", "run_id", "run_attempt", "tested_sha", "tree_sha")}
    expected["kind"] = "poc-coordination-decision"
    if any(proof.get(key) != value for key, value in expected.items()):
        raise CoordinationRefusal("coordination_decision_binding_mismatch")
    if proof.get("mode") not in ("execute", "reuse", "await"):
        raise CoordinationRefusal("coordination_decision_invalid_mode")
    for key in ("owner_run", "owner_attempt", "source_run", "source_attempt"):
        if not re.fullmatch(r"[1-9][0-9]*", str(proof.get(key, ""))):
            raise CoordinationRefusal("coordination_decision_invalid_pin")
    own = (str(run["id"]), str(run["run_attempt"]))
    owner = (proof["owner_run"], proof["owner_attempt"])
    source = (proof["source_run"], proof["source_attempt"])
    if (proof["mode"] in ("execute", "reuse") and owner != own
            or proof["mode"] == "execute" and source != own
            or proof["mode"] == "await" and owner == own
            or proof["mode"] == "reuse" and source == own):
        raise CoordinationRefusal("coordination_decision_cycle_or_owner_mismatch")
    successful_steps(run_jobs(repository, run), "Select CI scope", DECISION_STEPS)
    same_attempt(repository, run)
    return proof


def pending_participants(repository, runs, tree):
    participants = []
    for run in runs:
        if run["status"] == "completed":
            continue
        try:
            identity = coordination_identity(repository, run)
        except MissingEvidence:
            jobs = run_jobs(repository, run)
            preflight = [job for job in jobs if job["name"] == IDENTITY_JOB]
            if len(preflight) == 1 and preflight[0]["status"] in ("queued", "pending", "waiting", "in_progress"):
                raise PreflightPublicationPending("peer_preflight_identity_publication_in_progress")
            # A legacy peer needs actual native/merge evidence proving a
            # different tree. Unknown or matching active work cannot be owned
            # again merely because there is no reusable successful source.
            reference = {"id": 0, "run_attempt": 1, "head_sha": "0" * 40, "created_at": "2000-01-01T00:00:00Z"}
            try:
                blocks = newer_run_blocks(repository, run, reference, tree)
            except (OSError, subprocess.SubprocessError, ValueError, KeyError, TypeError, AttributeError):
                diagnostic("unknown_pending_peer_tree", run)
                raise CoordinationRefusal("unknown_pending_peer_tree") from None
            if blocks:
                diagnostic("unknown_matching_pending_peer", run)
                raise CoordinationRefusal("unknown_matching_pending_peer")
            continue
        if identity["tree_sha"] != tree:
            continue
        try:
            decision = coordination_decision(repository, run, identity)
        except MissingEvidence:
            jobs = run_jobs(repository, run)
            plans = [job for job in jobs if job["name"] == "Select CI scope"]
            if len(plans) == 1 and plans[0]["status"] == "in_progress":
                raise PublicationPending("canonical_decision_publication_in_progress")
            if (len(plans) > 1 or plans and plans[0]["status"] not in ("queued", "pending", "waiting")
                    or any(job["name"] in VALIDATION_JOBS and job["status"] != "queued" for job in jobs)):
                raise CoordinationRefusal("started_plan_without_verified_decision")
            decision = None
        latest = same_attempt(repository, run)
        if latest["status"] == "completed":
            raise CoordinationRefusal("participant_became_terminal_during_selection")
        participants.append((run, decision))
    return participants


def canonical_context(env, tree):
    repository = env["GITHUB_REPOSITORY"]
    workflow_id = api(f"repos/{repository}/actions/workflows/tests.yml")["id"]
    current = api(f"repos/{repository}/actions/runs/{env['GITHUB_RUN_ID']}")
    if not trusted_run(current, repository, workflow_id) or str(current["run_attempt"]) != env["GITHUB_RUN_ATTEMPT"]:
        raise CoordinationRefusal("current_run_is_not_trusted_attempt")
    identity = coordination_identity(repository, current)
    if identity["tree_sha"] != tree or identity["tested_sha"] != env["GITHUB_SHA"]:
        raise CoordinationRefusal("current_checkout_identity_mismatch")
    runs = [run for run in workflow_history(repository) if trusted_run(run, repository, workflow_id) and run["id"] != current["id"]]
    return current, identity, runs


def pending_follower_ids(env, runs, tree):
    if env.get("COORDINATION_ACTIVE") != "true":
        return set()
    current, identity, _ = canonical_context(env, tree)
    own = (str(current["id"]), str(current["run_attempt"]))
    try:
        decision = coordination_decision(env["GITHUB_REPOSITORY"], current, identity)
    except MissingEvidence:
        decision = None  # Selection is running under the native lock.
    if decision and decision["mode"] == "await":
        own = (decision["owner_run"], decision["owner_attempt"])
    followers = set()
    deadline = time.monotonic() + 45
    while True:
        try:
            participants = pending_participants(env["GITHUB_REPOSITORY"], runs, tree)
            break
        except PublicationPending:
            if time.monotonic() >= deadline:
                raise CoordinationRefusal("follower_decision_publication_timeout")
            diagnostic("waiting_for_follower_decision_publication")
            time.sleep(3)
    for run, other in participants:
        if other is None or (other["mode"] == "await" and (other["owner_run"], other["owner_attempt"]) == own
                             and (not decision or all(other[key] == decision[key] for key in ("source_run", "source_attempt")))):
            followers.add(run["id"])
    return followers


def canonical_selection(env):
    repository = env["GITHUB_REPOSITORY"]
    if command("git", "rev-parse", "HEAD") != env["GITHUB_SHA"]:
        raise CoordinationRefusal("selector_checkout_mismatch")
    tree = command("git", "rev-parse", "HEAD^{tree}")
    current, identity, runs = canonical_context(env, tree)
    # A sibling may finish unsuccessfully before this queued selector starts.
    # Its immutable claim remains authoritative; a waiter cannot take over.
    retry = int(current["run_attempt"]) > 1 or current["event"] == "workflow_dispatch"
    retry_started = datetime.datetime.fromisoformat(current.get("run_started_at", current["created_at"]).replace("Z", "+00:00"))
    inventory = coordination_artifact_index(repository)
    for run in runs:
        if (run["status"] != "completed" or run["conclusion"] == "success"
                or retry and attempt_order(run)[0] < retry_started):
            continue
        name = f"ci-coordination-identity-{run['id']}-{run['run_attempt']}"
        if name not in inventory:
            continue
        if inventory[name] != run["id"]:
            raise CoordinationRefusal("coordination_artifact_inventory_run_binding_mismatch")
        try:
            failed_identity = coordination_identity(repository, run)
        except MissingEvidence:
            raise CoordinationRefusal("coordination_index_changed_during_failed_peer_validation") from None
        if failed_identity["tree_sha"] == tree:
            diagnostic("canonical_peer_unsuccessful", run)
            raise CoordinationRefusal("canonical_peer_unsuccessful_no_takeover")
    deadline = time.monotonic() + 45
    while True:
        try:
            participants = pending_participants(repository, runs, tree)
            break
        except PreflightPublicationPending:
            if time.monotonic() >= deadline:
                raise CoordinationRefusal("peer_preflight_identity_publication_timeout")
            diagnostic("waiting_for_peer_preflight_identity")
            time.sleep(3)
            current, identity, runs = canonical_context(env, tree)
    owners = [(run, decision) for run, decision in participants if decision and decision["mode"] in ("execute", "reuse")]
    if len(owners) > 1:
        raise CoordinationRefusal("multiple_pending_validation_owners")
    if owners:
        owner, decision = owners[0]
        for _, other in participants:
            if other and other["mode"] == "await" and (other["owner_run"], other["owner_attempt"]) != (str(owner["id"]), str(owner["run_attempt"])):
                raise CoordinationRefusal("waiter_names_another_owner")
        result = dict(decision, mode="await", owner_run=str(owner["id"]), owner_attempt=str(owner["run_attempt"]))
        diagnostic("await_pinned_validation_owner", owner)
    else:
        if any(decision for _, decision in participants):
            raise CoordinationRefusal("pending_waiter_has_no_pending_owner")
        source, attempt = reusable_run(env, "poc", with_attempt=True)
        own = {"owner_run": str(current["id"]), "owner_attempt": str(current["run_attempt"])}
        result = dict(own, mode="reuse" if source else "execute", source_run=source or own["owner_run"], source_attempt=attempt or own["owner_attempt"])
        diagnostic("reuse_original_proof" if source else "execute_canonical_validation", current)
    result.update({key: identity[key] for key in ("schema", "protocol", "scope", "repository", "run_id", "run_attempt", "tested_sha", "tree_sha")}, kind="poc-coordination-decision")
    same_attempt(repository, current)
    Path("ci-coordination-decision").mkdir(exist_ok=True)
    Path("ci-coordination-decision/coordination-decision.json").write_text(json.dumps(result, indent=2) + "\n")
    return result


def canonical_owner(env):
    repository = env["GITHUB_REPOSITORY"]
    tree = command("git", "rev-parse", "HEAD^{tree}")
    current, identity, _ = canonical_context(env, tree)
    decision = coordination_decision(repository, current, identity)
    if decision["mode"] not in ("reuse", "await"):
        raise CoordinationRefusal("reuse_job_is_not_a_consumer")
    if (decision["source_run"], decision["source_attempt"]) != (env["SOURCE_RUN"], env["SOURCE_ATTEMPT"]):
        raise CoordinationRefusal("consumer_original_source_pin_changed")
    owner = api(f"repos/{repository}/actions/runs/{decision['owner_run']}")
    if not trusted_run(owner, repository, current["workflow_id"]) or str(owner["run_attempt"]) != decision["owner_attempt"]:
        raise CoordinationRefusal("canonical_owner_attempt_or_workflow_changed")
    owner_identity = coordination_identity(repository, owner)
    owner_decision = coordination_decision(repository, owner, owner_identity)
    if (owner_identity["tree_sha"] != tree or owner_decision["mode"] not in ("execute", "reuse")
            or any(owner_decision[key] != decision[key] for key in ("owner_run", "owner_attempt", "source_run", "source_attempt"))):
        raise CoordinationRefusal("canonical_owner_provenance_or_cycle_mismatch")
    return owner, decision


def await_canonical_owner(env, timeout=510):
    if env.get("COORDINATION_ACTIVE") != "true":
        return
    owner, decision = canonical_owner(env)
    if decision["mode"] == "reuse":
        return  # The current run owns consumption of already-successful proof.
    started = time.monotonic()
    while True:
        latest = same_attempt(env["GITHUB_REPOSITORY"], owner)
        if latest["status"] == "completed":
            if latest["conclusion"] != "success":
                diagnostic("canonical_owner_unsuccessful", latest)
                raise CoordinationRefusal("canonical_owner_unsuccessful")
            break
        if time.monotonic() - started >= timeout:
            diagnostic("canonical_owner_wait_timeout", owner)
            raise CoordinationRefusal("canonical_owner_wait_timeout")
        diagnostic("waiting_for_canonical_owner", owner)
        time.sleep(10)
    diagnostic(f"owner_succeeded_wait_seconds={time.monotonic() - started:.0f}", latest)


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


def immutable_merge_identity(repository, run):
    if (run.get("event") != "pull_request" or run.get("path") != ".github/workflows/tests.yml"
            or run.get("head_repository", {}).get("full_name") != repository):
        raise ValueError("merge identity requires the trusted same-repository PR workflow")
    proof = download_evidence(repository, run, "pr-merge-identity")
    expected = {"schema": 1, "kind": "pr-merge-identity", "scope": "identity-only",
                "repository": repository, "run_id": str(run["id"]),
                "run_attempt": str(run["run_attempt"]), "head_sha": run["head_sha"]}
    if any(proof.get(key) != value for key, value in expected.items()):
        raise ValueError("PR merge identity does not bind the run/attempt/head")
    for key in ("tested_sha", "tree_sha", "base_sha", "head_sha"):
        if not re.fullmatch(SHA, str(proof.get(key, ""))):
            raise ValueError("invalid immutable merge identity SHA")
    if not re.fullmatch(r"[1-9][0-9]*", str(proof.get("pull_number", ""))):
        raise ValueError("invalid immutable merge identity pull number")
    age = datetime.datetime.now(datetime.timezone.utc) - datetime.datetime.fromisoformat(run["created_at"].replace("Z", "+00:00"))
    if age.total_seconds() < 0 or age > datetime.timedelta(days=30):
        raise ValueError("PR merge identity is stale")
    tested = api(f"repos/{repository}/git/commits/{proof['tested_sha']}")
    if (tested.get("sha") != proof["tested_sha"] or tested["tree"]["sha"] != proof["tree_sha"]
            or [parent["sha"] for parent in tested["parents"]] != [proof["base_sha"], run["head_sha"]]):
        raise ValueError("PR merge identity does not match native ordered parents/tree")
    jobs = [job for job in run_jobs(repository, run) if job["name"] == "Select CI scope"]
    if len(jobs) != 1:
        raise ValueError("PR merge identity requires a unique scope job")
    for name in MERGE_IDENTITY_STEPS:
        steps = [step for step in jobs[0].get("steps", []) if step["name"] == name]
        if len(steps) != 1 or steps[0]["conclusion"] != "success":
            raise ValueError("PR merge identity record/publication did not succeed")
    legacy_name = f"validation-tree-{run['id']}-{run['run_attempt']}"
    if any(item["name"] == legacy_name for item in artifact_inventory(repository, run)):
        raise ValueError("validation evidence appeared during merge classification")
    latest = api(f"repos/{repository}/actions/runs/{run['id']}")
    for key in ("id", "run_attempt", "head_sha", "event", "workflow_id", "path", "head_repository"):
        if latest.get(key) != run.get(key):
            raise ValueError("run identity/attempt changed during merge classification")
    return proof


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
        # Identity-only provenance can rule out an unrelated merge, never pass
        # failed tests. Only genuine absence permits the early-identity path.
        try:
            proof = download_evidence(repository, run)
        except MissingEvidence:
            return immutable_merge_identity(repository, run)["tree_sha"] == tree
        expected = {"schema": 2, "repository": repository, "run_id": str(run["id"]), "run_attempt": str(run["run_attempt"])}
        if any(proof.get(key) != value for key, value in expected.items()) or not re.fullmatch(SHA, str(proof.get("tested_sha", ""))):
            raise ValueError("failed-run merge evidence has unverifiable provenance")
        tested = api(f"repos/{repository}/git/commits/{proof['tested_sha']}")
        if (tested["sha"] != proof["tested_sha"] or tested["tree"]["sha"] != proof.get("tree_sha")
                or len(tested["parents"]) != 2 or tested["parents"][1]["sha"] != run["head_sha"]):
            raise ValueError("failed-run merge tree does not bind the actual source head")
        return tested["tree"]["sha"] == tree
    return False


def workflow_history(repository):
    endpoint = f"repos/{repository}/actions/workflows/tests.yml/runs?per_page=100"
    first = api(endpoint)
    total = first["total_count"]
    if not isinstance(total, int) or not 0 <= total <= 5000:
        raise ValueError("workflow history exceeds the verified lookup bound")
    responses = [first] if len(first["workflow_runs"]) == total else pages(endpoint)
    runs = [run for response in responses for run in response["workflow_runs"]]
    if (any(response["total_count"] != total for response in responses)
            or len(runs) != total or len({run["id"] for run in runs}) != total):
        raise ValueError("workflow history is incomplete or changed during pagination")
    return runs


def coordination_artifact_index(repository):
    # One bounded paginated request replaces a serial lookup for every old
    # unsuccessful run. Absence is usable only from a complete stable inventory.
    endpoint = f"repos/{repository}/actions/artifacts?per_page=100"
    deadline = time.monotonic() + 45

    def remaining():
        seconds = deadline - time.monotonic()
        if seconds <= 0:
            raise CoordinationRefusal("coordination_artifact_inventory_incomplete_or_changed")
        return seconds

    for attempt in range(3):
        # Concurrent uploads can move page boundaries. Discard every page of
        # an unstable read; never combine it with a later inventory.
        first = api(endpoint, timeout=remaining())
        total = first["total_count"]
        if not isinstance(total, int) or not 0 <= total <= 10000:
            raise CoordinationRefusal("coordination_artifact_inventory_exceeds_bound")
        responses = [first] if len(first["artifacts"]) == total else pages(endpoint, timeout=remaining())
        artifacts = [artifact for response in responses for artifact in response["artifacts"]]
        for artifact in artifacts:
            if not isinstance(artifact["name"], str):
                raise CoordinationRefusal("coordination_artifact_inventory_invalid_name")
        if (any(response["total_count"] != total for response in responses)
                or len(artifacts) != total or len({artifact["id"] for artifact in artifacts}) != total):
            if attempt == 2:
                raise CoordinationRefusal("coordination_artifact_inventory_incomplete_or_changed")
            diagnostic("retrying_unstable_coordination_artifact_inventory")
            time.sleep(min(1, remaining()))
            continue
        remaining()  # Do not accept a read that finished outside its budget.
        index = {}
        for artifact in artifacts:
            if artifact["name"].startswith("ci-coordination-identity-"):
                name = artifact["name"]
                if name in index:
                    raise CoordinationRefusal("coordination_artifact_inventory_duplicate_identity")
                index[name] = artifact["workflow_run"]["id"]
        return index


def reusable_run(env, scope, with_attempt=False):
    repository = env["GITHUB_REPOSITORY"]
    candidate = env["GITHUB_SHA"]
    if not re.fullmatch(SHA, candidate) or command("git", "rev-parse", "HEAD") != candidate:
        raise ValueError("checkout does not match candidate")
    tree = command("git", "rev-parse", "HEAD^{tree}")
    workflow_id = api(f"repos/{repository}/actions/workflows/tests.yml")["id"]
    runs = workflow_history(repository)
    runs = [run for run in runs if trusted_run(run, repository, workflow_id) and str(run["id"]) != env.get("GITHUB_RUN_ID")]
    followers = pending_follower_ids(env, runs, tree) if scope == "poc" else set()
    candidates = sorted(runs, key=attempt_order, reverse=True)
    if env.get("SOURCE_RUN"):
        candidates = [run for run in candidates if str(run["id"]) == env["SOURCE_RUN"]]
    else:
        candidates = candidates[:100]
    deadline = time.monotonic() + 45
    for source in candidates:
        if time.monotonic() > deadline:
            break
        if source["status"] != "completed" or source["conclusion"] != "success":
            continue
        if env.get("SOURCE_ATTEMPT") and str(source["run_attempt"]) != env["SOURCE_ATTEMPT"]:
            continue
        try:
            validate_source(repository, source, tree, scope)
        except (OSError, subprocess.SubprocessError, ValueError, KeyError, TypeError, AttributeError):
            diagnostic("source_proof_not_usable", source)
            continue
        # Do not silently fall back to an older success on a newer genuine
        # failure, cancellation, skipped run or unresolved matching attempt.
        for run in runs:
            if run["id"] in followers:
                run = same_attempt(repository, run)
                if run["status"] != "completed" and run["id"] in pending_follower_ids(env, [run], tree):
                    diagnostic("verified_pending_follower", run)
                    continue
            if newer_run_blocks(repository, run, source, tree):
                diagnostic("newer_matching_run_blocks_reuse", run)
                if env.get("COORDINATION_ACTIVE") == "true":
                    raise CoordinationRefusal("newer_matching_run_blocks_reuse")
                return ("", "") if with_attempt else ""
        return (str(source["id"]), str(source["run_attempt"])) if with_attempt else str(source["id"])
    return ("", "") if with_attempt else ""


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
        if not re.fullmatch(r"[1-9][0-9]*", str(proof.get("source_run", ""))) or (proof.get("coordination_protocol") != 1 and int(proof["source_run"]) >= run["id"]):
            raise ValueError("reuse must name an earlier original run")
        if proof.get("coordination_protocol") == 1:
            owner_env = dict(env, GITHUB_SHA=candidate, GITHUB_RUN_ID=str(run["id"]), GITHUB_RUN_ATTEMPT=str(run["run_attempt"]),
                             SOURCE_RUN=proof["source_run"], SOURCE_ATTEMPT=proof["source_attempt"], COORDINATION_ACTIVE="true")
            owner, decision = canonical_owner(owner_env)
            if (str(owner["id"]), str(owner["run_attempt"])) != (proof.get("canonical_owner_run"), proof.get("canonical_owner_attempt")):
                raise CoordinationRefusal("receipt_canonical_owner_pin_mismatch")
            if decision["mode"] == "await" and (owner["status"] != "completed" or owner["conclusion"] != "success"):
                raise CoordinationRefusal("receipt_canonical_owner_not_successful")
        source = api(f"repos/{repository}/actions/runs/{proof['source_run']}")
        if str(source["run_attempt"]) != proof.get("source_attempt"):
            raise ValueError("original source attempt changed")
    elif proof.get("tested_sha") != candidate:
        raise ValueError("executed UAT proof is not exact candidate")
    if not trusted_run(source, repository, workflow_id):
        raise ValueError("untrusted original source")
    validate_source(repository, source, tree, scope)
    lookup = dict(env, GITHUB_SHA=candidate, GITHUB_RUN_ID="", SOURCE_RUN=str(source["id"]), SOURCE_ATTEMPT=str(source["run_attempt"]))
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


def record_coordination_identity(env):
    scope = requested_scope(env)
    tested = command("git", "rev-parse", "HEAD")
    tree = command("git", "rev-parse", "HEAD^{tree}")
    if tested != env["GITHUB_SHA"] or not re.fullmatch(SHA, tested) or not re.fullmatch(SHA, tree):
        raise CoordinationRefusal("preflight_checkout_mismatch")
    coordinated = scope == "poc" and (env.get("GITHUB_EVENT_NAME") != "pull_request" or env.get("PR_HEAD_REPOSITORY") == env["GITHUB_REPOSITORY"])
    with open(env["GITHUB_OUTPUT"], "a") as output:
        output.write(f"scope={scope}\ntree_sha={tree}\ncoordinate={str(coordinated).lower()}\n")
    if not coordinated:
        return
    proof = {"schema": 1, "protocol": 1, "kind": "poc-coordination-identity", "scope": scope,
             "repository": env["GITHUB_REPOSITORY"], "run_id": env["GITHUB_RUN_ID"], "run_attempt": env["GITHUB_RUN_ATTEMPT"],
             "tested_sha": tested, "tree_sha": tree, "event": env["GITHUB_EVENT_NAME"], "head_sha": tested}
    if env["GITHUB_EVENT_NAME"] == "pull_request":
        pull = json.loads(Path(env["GITHUB_EVENT_PATH"]).read_text())["pull_request"]
        headers = command("git", "cat-file", "-p", "HEAD").split("\n\n", 1)[0].splitlines()
        parents = [line.removeprefix("parent ") for line in headers if line.startswith("parent ")]
        if (pull["head"]["repo"]["full_name"] != env["GITHUB_REPOSITORY"] or pull["base"]["repo"]["full_name"] != env["GITHUB_REPOSITORY"]
                or parents != [pull["base"]["sha"], pull["head"]["sha"]]
                or not (pull["base"]["ref"] == "dev" or (pull["base"]["ref"] == "uat" and pull["head"]["ref"] == "dev"))):
            raise CoordinationRefusal("preflight_original_pr_merge_mismatch")
        proof.update(head_sha=parents[1], base_sha=parents[0], base_ref=pull["base"]["ref"], pull_number=str(pull["number"]))
    Path("ci-coordination-identity").mkdir(exist_ok=True)
    Path("ci-coordination-identity/coordination-identity.json").write_text(json.dumps(proof, indent=2) + "\n")


def record_merge_identity(env):
    if env.get("GITHUB_EVENT_NAME") != "pull_request":
        raise ValueError("merge identity is restricted to pull_request")
    event = json.loads(Path(env["GITHUB_EVENT_PATH"]).read_text())
    pull = event["pull_request"]
    repository = env["GITHUB_REPOSITORY"]
    if pull["head"]["repo"]["full_name"] != repository or pull["base"]["repo"]["full_name"] != repository:
        raise ValueError("merge identity is restricted to same-repository PRs")
    tested = command("git", "rev-parse", "HEAD")
    tree = command("git", "rev-parse", "HEAD^{tree}")
    # Read raw commit headers: checkout is shallow, but parent identities are
    # immutable commit content and must not disappear at a shallow boundary.
    headers = command("git", "cat-file", "-p", "HEAD").split("\n\n", 1)[0].splitlines()
    parents = [line.removeprefix("parent ") for line in headers if line.startswith("parent ")]
    expected_parents = [pull["base"]["sha"], pull["head"]["sha"]]
    if tested != env["GITHUB_SHA"] or parents != expected_parents:
        raise ValueError("checkout does not bind the original PR event merge")
    for value in [tested, tree, *parents]:
        if not re.fullmatch(SHA, value):
            raise ValueError("invalid checked-out merge identity SHA")
    proof = {"schema": 1, "kind": "pr-merge-identity", "scope": "identity-only",
             "repository": repository, "run_id": env["GITHUB_RUN_ID"],
             "run_attempt": env["GITHUB_RUN_ATTEMPT"], "pull_number": str(pull["number"]),
             "tested_sha": tested, "tree_sha": tree, "base_sha": parents[0], "head_sha": parents[1]}
    Path("ci-merge-identity").mkdir(exist_ok=True)
    Path("ci-merge-identity/merge-identity.json").write_text(json.dumps(proof, indent=2) + "\n")


def record_reuse(env):
    if env.get("COORDINATION_ACTIVE") == "true":
        owner, decision = canonical_owner(env)
        if decision["mode"] == "await" and (owner["status"] != "completed" or owner["conclusion"] != "success"):
            raise CoordinationRefusal("canonical_owner_not_successful_at_receipt")
    if (not re.fullmatch(r"[0-9]+", env.get("SOURCE_RUN", ""))
            or not re.fullmatch(r"[1-9][0-9]*", env.get("SOURCE_ATTEMPT", ""))
            or reusable_run(env, "poc") != env["SOURCE_RUN"]):
        raise ValueError("original pinned source attempt is no longer validated")
    record(env, "poc")
    path = Path("ci-evidence/tested-tree.json")
    receipt = json.loads(path.read_text())
    receipt.update(kind="reuse", source_run=env["SOURCE_RUN"], source_attempt=env["SOURCE_ATTEMPT"], required_jobs=REUSE_JOBS, required_steps=REUSE_STEPS)
    if env.get("COORDINATION_ACTIVE") == "true":
        receipt.update(coordination_protocol=1, canonical_owner_run=decision["owner_run"], canonical_owner_attempt=decision["owner_attempt"])
    path.write_text(json.dumps(receipt, indent=2) + "\n")


def main():
    env = os.environ
    if sys.argv[1:] == ["record-coordination-identity"]:
        record_coordination_identity(env)
        return
    if sys.argv[1:] == ["record-merge-identity"]:
        record_merge_identity(env)
        return
    if sys.argv[1:] == ["admit-staging"]:
        print(json.dumps(staging_evidence(env)))
        return
    if sys.argv[1:] == ["record-reuse"]:
        record_reuse(env)
        return
    if sys.argv[1:] in (["verify-full"], ["verify-poc"]):
        scope = sys.argv[1].removeprefix("verify-")
        if scope == "poc":
            await_canonical_owner(env)
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
    source, source_attempt = "", ""
    canonical = None
    if scope == "poc" and env.get("COORDINATION_ACTIVE") == "true":
        canonical = canonical_selection(env)
        if canonical["mode"] in ("reuse", "await"):
            source, source_attempt = canonical["source_run"], canonical["source_attempt"]
    # Dev PRs execute. A dev->uat PR may reuse only its actual proposed merge tree.
    promotion = env.get("GITHUB_EVENT_NAME") == "pull_request" and env.get("PR_BASE") == "uat" and env.get("PR_HEAD_BRANCH") == "dev"
    if not canonical and scope == "poc" and (promotion or (env.get("GITHUB_EVENT_NAME") in ("push", "workflow_dispatch") and env.get("GITHUB_REF") in ("refs/heads/dev", "refs/heads/uat"))):
        try:
            source, source_attempt = reusable_run(env, scope, with_attempt=True)
        except (OSError, subprocess.SubprocessError, ValueError, KeyError, TypeError, AttributeError):
            diagnostic("unverifiable_lookup_execute_fresh_checks")
    with open(env["GITHUB_OUTPUT"], "a") as output:
        output.write(f"full={str(scope == 'full' and not source).lower()}\npoc={str(scope == 'poc' and not source).lower()}\nscope={scope}\nreused={str(bool(source)).lower()}\nsource_run={source}\nsource_attempt={source_attempt}\n")
    print(f"Scope: {scope}; " + (f"reuse identical complete tree from run {source}" if source else "execute checks"))


if __name__ == "__main__":
    main()
