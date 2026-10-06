#!/usr/bin/env python3
"""Check execution critical path separately from GitHub runner queue latency."""

import datetime
import json
import os
import subprocess
import time


def stamp(value):
    return datetime.datetime.fromisoformat(value.replace("Z", "+00:00"))


def measure(jobs):
    by_name = {job["name"]: job for job in jobs}
    names = ["Select CI scope", "Board sync offline tests", "POC PHP safety and static checks", "POC web safety and static checks"]
    seconds = {}
    ends = {}
    for name in names:
        matches = [job for job in jobs if job["name"] == name]
        if len(matches) != 1:
            raise ValueError(f"budget requires exactly one successful {name}")
        job = matches[0]
        if name == "POC PHP safety and static checks" and job["status"] == "in_progress":
            required = ["Run explicit POC PostgreSQL safety catalog without coverage replay", "Prove deployment admission still refuses missing full evidence", "Prove scoped selection and evidence reuse fail closed", "Record POC validation tree", "Publish POC validation tree"]
            for step_name in required:
                steps = [step for step in job.get("steps", []) if step["name"] == step_name]
                if len(steps) != 1 or steps[0]["conclusion"] != "success":
                    raise ValueError(f"budget refuses an unpassed step: {step_name}")
            end = datetime.datetime.now(datetime.timezone.utc)
        elif job["conclusion"] == "success":
            end = stamp(job["completed_at"])
        else:
            raise ValueError(f"budget refuses an unpassed job: {name}")
        seconds[name] = max(0, (end - stamp(job["started_at"])).total_seconds())
        ends[name] = end
    critical = max(seconds[names[1]], seconds[names[0]] + max(seconds[names[2]], seconds[names[3]]))
    elapsed = (max(ends.values()) - min(stamp(by_name[name]["started_at"]) for name in names)).total_seconds()
    return critical, elapsed, sum(seconds.values())


def main():
    env = os.environ
    endpoint = f"repos/{env['GITHUB_REPOSITORY']}/actions/runs/{env['GITHUB_RUN_ID']}/attempts/{env['GITHUB_RUN_ATTEMPT']}/jobs?per_page=100"
    deadline = time.monotonic() + 90
    while True:
        pages = json.loads(subprocess.check_output(["gh", "api", "--paginate", "--slurp", endpoint], text=True, timeout=30))
        jobs = [job for page in pages for job in page["jobs"]]
        peers = [job for job in jobs if job["name"] in ("POC web safety and static checks", "Board sync offline tests")]
        own = [job for job in jobs if job["name"] == "POC PHP safety and static checks"]
        own_ready = len(own) == 1 and (own[0]["status"] == "completed" or all(
            any(step["name"] == name and step["conclusion"] == "success" for step in own[0].get("steps", []))
            for name in ["Record POC validation tree", "Publish POC validation tree"]))
        if own_ready and len(peers) == 2 and all(job["status"] == "completed" for job in peers):
            break
        if time.monotonic() >= deadline:
            raise ValueError("peer POC checks remain queued/running; no successful proof")
        time.sleep(5)
    critical, elapsed, runner_seconds = measure(jobs)
    critical += 10  # Reserve post-job cleanup; the containing job also has a nine-minute hard limit.
    summary = f"POC execution critical path including proof and budget publication: {critical:.0f}s; elapsed through proof publication: {elapsed:.0f}s; preceding summed job wall time: {runner_seconds:.0f}s (not rounded billing). Total execution budget 600s. Queue gaps beyond job duration are reported separately, not guaranteed. PostgreSQL provisioning is included in job duration. POC is partial evidence and cannot satisfy release coverage."
    print(summary)
    with open(env["GITHUB_STEP_SUMMARY"], "a") as output:
        output.write(summary + "\n")
    if critical > 600:
        raise ValueError("combined POC execution including evidence publication exceeds ten minutes")


if __name__ == "__main__":
    main()
