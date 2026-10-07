#!/usr/bin/env python3
"""Retain actionlint checks while validating its documented queue-key gap.

GitHub supports queue:max with cancel-in-progress:false. actionlint 1.7.12
does not yet parse that key (upstream issue657 / PR654). Only the exact
syntax diagnostic at our two independently checked queue nodes is accepted.
All other errors, malformed output and tool failures remain failures.
"""

import json
import re
import subprocess
import sys
from pathlib import Path


QUEUE_ERROR = 'unexpected key "queue" for "concurrency" section. expected one of "cancel-in-progress", "group"'


def valid_queue_node(lines, number):
    path = []
    for line in lines[:number]:
        match = re.match(r"^( *)([A-Za-z0-9_-]+):(?:\s|$)", line)
        if not match:
            continue
        depth, key = len(match[1]), match[2]
        while path and path[-1][0] >= depth:
            path.pop()
        path.append((depth, key))
    allowed = [("concurrency", "queue"), ("jobs", "plan", "concurrency", "queue")]
    return (tuple(key for _, key in path) in allowed and number >= 3
            and lines[number - 1].strip() == "queue: max"
            and lines[number - 2].strip() == "cancel-in-progress: false"
            and lines[number - 3].strip().startswith("group: "))


def remaining_errors(output, returncode, workflow):
    if returncode not in (0, 1):
        raise ValueError("actionlint tool failed")
    errors = json.loads(output or "[]")
    if not isinstance(errors, list) or (returncode == 1 and not errors):
        raise ValueError("actionlint returned no verifiable diagnostics")
    lines = workflow.read_text().splitlines()
    remaining = []
    for error in errors:
        if (not isinstance(error, dict) or not isinstance(error.get("line"), int)
                or not isinstance(error.get("filepath"), str) or not isinstance(error.get("message"), str)):
            raise ValueError("actionlint diagnostic shape is invalid")
        if (error.get("kind") == "syntax-check" and error["message"] == QUEUE_ERROR
                and Path(error["filepath"]) == workflow and 1 <= error["line"] <= len(lines)
                and valid_queue_node(lines, error["line"])):
            print(f"Verified documented concurrency queue at {workflow}:{error['line']}")
        else:
            remaining.append(error)
    return remaining


def main():
    result = subprocess.run([sys.argv[1], "-shellcheck=", "-format", "{{json .}}"], capture_output=True, text=True, timeout=45)
    errors = remaining_errors(result.stdout, result.returncode, Path(".github/workflows/tests.yml"))
    if errors:
        print(json.dumps(errors, indent=2), file=sys.stderr)
        raise SystemExit(1)
    print("Workflow syntax and expressions passed; documented queue nodes validated separately.")


if __name__ == "__main__":
    main()
