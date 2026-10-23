#!/usr/bin/env python3
"""Require complete PHP static checks, optionally overlapping the POC catalog."""

import argparse
import os
import signal
import subprocess
import time


CHECKS = (
    ("PHP formatting", ("composer", "lint:check")),
    ("PHP static analysis", ("composer", "types:check")),
)


def stop_checks(processes):
    # Each child owns a separate session, including its Composer/tool children.
    for process in processes:
        try:
            os.killpg(process.pid, signal.SIGTERM)
        except ProcessLookupError:
            pass
    deadline = time.monotonic() + 3
    for process in processes:
        try:
            process.wait(timeout=max(0, deadline - time.monotonic()))
        except subprocess.TimeoutExpired:
            pass
    for process in processes:
        try:
            os.killpg(process.pid, signal.SIGKILL)
        except ProcessLookupError:
            pass
    for process in processes:
        process.wait()


def run_checks(checks=CHECKS, cancelled=lambda: 0):
    if not checks:
        raise ValueError("PHP static checks must not be empty")
    processes, reported = [], set()
    complete = False
    try:
        for label, command in checks:
            if received := cancelled():
                return 128 + received
            print(f"{label}: starting complete check", flush=True)
            processes.append(subprocess.Popen(command, start_new_session=True))
        while True:
            if received := cancelled():
                return 128 + received
            for index, process in enumerate(processes):
                status = process.poll()
                if status is not None and index not in reported:
                    print(f"{checks[index][0]}: exited {status}", flush=True)
                    reported.add(index)
            if len(reported) == len(processes):
                if received := cancelled():
                    return 128 + received
                complete = True
                return int(any(process.returncode != 0 for process in processes))
            time.sleep(0.05)
    except OSError:
        print("Could not start or monitor every required PHP static check", flush=True)
        return 1
    finally:
        if not complete or any(process.returncode != 0 for process in processes):
            stop_checks(processes)


def main(arguments=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--poc", action="store_true", help="Also execute the unchanged complete POC PHP catalog concurrently.")
    options = parser.parse_args(arguments)
    checks = CHECKS + (("POC PHP safety catalog", ("python3", ".github/scripts/run-poc-tests.py", "php")),) if options.poc else CHECKS
    received = 0

    def cancel(signum, frame):
        nonlocal received
        received = signum

    for signum in (signal.SIGINT, signal.SIGTERM):
        signal.signal(signum, cancel)
    return run_checks(checks, cancelled=lambda: received)


if __name__ == "__main__":
    raise SystemExit(main())
