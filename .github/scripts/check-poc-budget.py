#!/usr/bin/env python3
"""Check execution critical path separately from GitHub runner queue latency."""

import datetime
import json
import os
import subprocess


def stamp(value):
    return datetime.datetime.fromisoformat(value.replace("Z", "+00:00"))


def measure(jobs):
    by_name = {job["name"]: job for job in jobs}
    names = ["Select CI scope", "Board sync offline tests", "POC PHP safety and static checks", "POC web safety and static checks", "Record validation tree"]
    seconds = {}
    for name in names:
        matches = [job for job in jobs if job["name"] == name]
        if len(matches) != 1 or matches[0]["conclusion"] != "success":
            raise ValueError(f"budget requires exactly one successful {name}")
        seconds[name] = max(0, (stamp(by_name[name]["completed_at"]) - stamp(by_name[name]["started_at"])).total_seconds())
    critical = max(seconds[names[1]], seconds[names[0]] + max(seconds[names[2]], seconds[names[3]])) + seconds[names[4]]
    elapsed = (max(stamp(by_name[name]["completed_at"]) for name in names) - min(stamp(by_name[name]["started_at"]) for name in names)).total_seconds()
    return critical, elapsed, sum(seconds.values())


def main():
    env = os.environ
    endpoint = f"repos/{env['GITHUB_REPOSITORY']}/actions/runs/{env['GITHUB_RUN_ID']}/attempts/{env['GITHUB_RUN_ATTEMPT']}/jobs?per_page=100"
    pages = json.loads(subprocess.check_output(["gh", "api", "--paginate", "--slurp", endpoint], text=True, timeout=45))
    jobs = [job for page in pages for job in page["jobs"]]
    critical, elapsed, runner_seconds = measure(jobs)
    active = [job for job in jobs if job["name"] == "POC execution budget"]
    if len(active) != 1 or not active[0].get("started_at"):
        raise ValueError("budget job identity is unavailable")
    budget_seconds = (datetime.datetime.now(datetime.timezone.utc) - stamp(active[0]["started_at"])).total_seconds()
    critical += max(0, budget_seconds) + 5  # Reserve final summary/post-job overhead.
    summary = f"POC execution critical path including proof and budget publication: {critical:.0f}s; elapsed through proof publication: {elapsed:.0f}s; preceding summed job wall time: {runner_seconds:.0f}s (not rounded billing). Total execution budget 600s. Queue gaps beyond job duration are reported separately, not guaranteed. PostgreSQL provisioning is included in job duration. POC is partial evidence and cannot satisfy release coverage."
    print(summary)
    with open(env["GITHUB_STEP_SUMMARY"], "a") as output:
        output.write(summary + "\n")
    if critical > 600:
        raise ValueError("combined POC execution including evidence publication exceeds ten minutes")


if __name__ == "__main__":
    main()
