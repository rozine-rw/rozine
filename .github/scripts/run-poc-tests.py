#!/usr/bin/env python3
"""Run the checked-in POC smoke catalog, refusing missing files or zero tests."""

import json
import subprocess
import sys
import xml.etree.ElementTree as ET
from pathlib import Path


def selected_paths(manifest, suite):
    paths = manifest[suite]
    if manifest.get("schema") != 1 or manifest.get("scope") != "poc-only" or not paths or len(set(paths)) != len(paths):
        raise ValueError("invalid or empty POC catalog")
    for path in paths:
        if not isinstance(path, str) or not path.startswith("tests/") or ".." in Path(path).parts or not Path(path).is_file():
            raise ValueError(f"POC test is missing or unsafe: {path}")
    return paths


def main():
    suite = sys.argv[1]
    paths = selected_paths(json.loads(Path("config/poc-test-manifest.json").read_text()), suite)
    Path("coverage/poc").mkdir(parents=True, exist_ok=True)
    if suite == "php":
        args = ["php", "vendor/bin/pest", "--ci", "--no-tia", "--fail-on-skipped", "--fail-on-incomplete", "--fail-on-empty-test-suite", "--log-junit=coverage/poc/php.xml", "--compact"]
    elif suite == "web":
        args = ["npm", "run", "test:web", "--", "--reporter=default", "--reporter=junit", "--outputFile=coverage/poc/web.xml"]
    else:
        raise ValueError("unknown POC suite")
    subprocess.run([*args, *paths], check=True)
    verify_results(Path(f"coverage/poc/{suite}.xml"), paths, suite)


def verify_results(report, paths, suite):
    root = ET.parse(report).getroot()
    if any(next(root.iter(tag), None) is not None for tag in ["skipped", "failure", "error"]):
        raise ValueError("partial POC catalog contains skipped or failed tests")
    executed = {}
    for result in root.iter("testsuite"):
        identity = result.get("file") if suite == "php" else result.get("name")
        if identity in paths:
            executed[identity] = int(result.get("tests", "0"))
    if set(executed) != set(paths) or any(count <= 0 for count in executed.values()):
        raise ValueError("every selected POC file must execute at least one test")


if __name__ == "__main__":
    main()
