"""Shared mandatory POC job and step contract for proof and budget checks."""

STATIC_JOB = "POC PHP static checks and validation evidence"
CATALOG_JOB = "POC PHP safety and static checks"
WEB_JOB = "POC web safety and static checks"
BOARD_JOB = "Board sync offline tests"
IDENTITY_JOB = "Identify CI validation tree"
POC_JOBS = [BOARD_JOB, CATALOG_JOB, WEB_JOB, STATIC_JOB]
POC_STEPS = ["Record POC validation tree", "Publish POC validation tree", "Require combined POC execution within ten minutes"]
STATIC_STEPS = ["Checkout exact tested commit", "Run PHP formatting and full Pest-aware static analysis"]
CATALOG_STEPS = [
    "Run explicit POC PostgreSQL safety catalog without coverage replay",
    "Prove deployment admission refuses missing scoped evidence",
    "Prove scoped selection and evidence reuse fail closed",
]


def successful_steps(job, required):
    for name in required:
        steps = [step for step in job.get("steps", []) if step.get("name") == name]
        if len(steps) != 1 or steps[0].get("status") != "completed" or steps[0].get("conclusion") != "success":
            raise ValueError(f"missing, duplicate or unsuccessful POC step: {name}")


def static_evidence(jobs, run, *, running=False):
    matches = [job for job in jobs if job.get("name") == STATIC_JOB]
    if len(matches) != 1:
        raise ValueError("missing or duplicate mandatory POC static job")
    job = matches[0]
    complete = job.get("status") == "completed" and job.get("conclusion") == "success"
    active = running and job.get("status") == "in_progress" and job.get("conclusion") is None
    if not (complete or active):
        raise ValueError("mandatory POC static job has not succeeded")
    if any(job.get(key) != run.get(source) for key, source in
           (("run_id", "id"), ("run_attempt", "run_attempt"), ("head_sha", "head_sha"))):
        raise ValueError("POC static job SHA, run or attempt differs")
    if any(run.get(key) is None for key in ("id", "run_attempt", "head_sha")):
        raise ValueError("POC static job lacks authoritative run identity")
    successful_steps(job, STATIC_STEPS + (POC_STEPS[:2] if active else POC_STEPS))
    return job


def catalog_evidence(jobs):
    matches = [job for job in jobs if job.get("name") == CATALOG_JOB]
    if len(matches) != 1 or matches[0].get("status") != "completed" or matches[0].get("conclusion") != "success":
        raise ValueError("mandatory POC catalog job has not succeeded")
    successful_steps(matches[0], CATALOG_STEPS)
