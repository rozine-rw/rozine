"""Bounded deployment fixtures; real distinct identities are mandatory in CI."""

import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest


SCRIPT = Path(__file__).with_name("deploy-remote.sh").resolve()


class StorageAccessTest(unittest.TestCase):
    def setUp(self):
        self.directory = Path(tempfile.mkdtemp(prefix="rozine-storage-"))
        self.directory.chmod(0o755)
        self.app = self.directory / "app"
        self.app.mkdir(mode=0o755)
        self.app.chmod(0o755)
        self.storage = self.app / "storage"
        self.storage.mkdir(mode=0o2775)
        self.storage.chmod(0o2775)
        self.bin = self.directory / "bin"
        self.bin.mkdir(mode=0o755)
        self.bin.chmod(0o755)
        self.calls = self.directory / "calls"
        self.calls.touch()
        self.runtime_uid = 65534 if os.geteuid() != 65534 else 65533
        self.env = dict(os.environ, PATH=f"{self.bin}:/usr/bin:/bin",
                        CALLS=str(self.calls), STORAGE=str(self.storage),
                        RUNTIME_UID=str(self.runtime_uid), TEST_ROOT=str(self.directory))
        self.write("php8.5", """#!/bin/bash
set -eu
if [ "${1:-}" = -r ]; then echo 8.5; exit; fi
printf '%s\\n' "$*" >> "$CALLS"
if [ "${2:-}" = isolation:check ] && [ "${REFUSE_ISOLATION:-}" = true ]; then exit 42; fi
if [ "${2:-}" = view:cache ] && [ -d storage/isolated/uat/views ]; then
  printf compiled > storage/isolated/uat/views/compiled-fixture
fi
""")
        for name in ["composer", "npm"]:
            self.write(name, '#!/bin/bash\nprintf "%s\\n" "$*" >> "$CALLS"\n')
        self.write("id", """#!/bin/bash
set -eu
if [ "${2:-}" = runtime ]; then
  case "$1" in
    -u) echo "$RUNTIME_UID";;
    -G) stat -c %g "$STORAGE";;
    *) exit 1;;
  esac
else exec /usr/bin/id "$@"; fi
""")
        # Legacy call-order fixtures cannot switch users. This stub evaluates
        # permissions as the distinct runtime UID; the CI class below uses sudo
        # and the kernel with two actual process identities instead.
        self.write("sudo", f"#!{sys.executable}\n" + '''
import os, pathlib, stat, sys
if os.getenv("REFUSE_SUDO") == "true": sys.exit(1)
assert sys.argv[1:6] == ["-n", "-u", "runtime", "--", "/usr/bin/test"]
flag, raw = sys.argv[6:]
path = pathlib.Path(raw)
uid = int(os.environ["RUNTIME_UID"])
groups = {os.stat(os.environ["STORAGE"]).st_gid}
def allowed(p, bit):
    s = p.stat()
    shift = 6 if s.st_uid == uid else 3 if s.st_gid in groups else 0
    return bool((stat.S_IMODE(s.st_mode) >> shift) & bit)
try:
    root = pathlib.Path(os.environ["TEST_ROOT"])
    traverse = all(allowed(p, 1) for p in path.parents if p.is_relative_to(root))
    ok = traverse and path.is_dir() and allowed(path, 1 if flag == "-x" else 2)
except OSError:
    ok = False
sys.exit(0 if ok else 1)
''')

    def tearDown(self):
        shutil.rmtree(self.directory)

    def write(self, name, body):
        target = self.bin / name
        target.write_text(body)
        target.chmod(0o755)

    def deploy(self, profile="uat", runtime="runtime", umask=0o022, **env):
        return subprocess.run(["bash", str(SCRIPT), str(self.app), profile, runtime],
                              env=dict(self.env, **env), capture_output=True, text=True,
                              umask=umask, timeout=20)

    def assert_refused(self, result):
        self.assertNotEqual(result.returncode, 0, result.stdout + result.stderr)
        calls = self.calls.read_text()
        for call in ["optimize:clear", "migrate --force", "queue:restart", "ci --no-audit"]:
            self.assertNotIn(call, calls)
        self.assertNotIn("Deployment complete", result.stdout)

    def test_fresh_directories_survive_restrictive_deploy_umask(self):
        result = self.deploy(umask=0o077)
        self.assertEqual(result.returncode, 0, result.stderr)
        for name in ["cache", "sessions", "private", "public", "logs", "views"]:
            folder = self.storage / "isolated/uat" / name
            self.assertEqual(folder.stat().st_gid, self.storage.stat().st_gid)
            self.assertEqual(folder.stat().st_mode & 0o7777, 0o2775)

    def test_existing_restrictive_parent_refuses_without_relabeling(self):
        parent = self.storage / "isolated"
        parent.mkdir(mode=0o700)
        before = parent.stat()
        self.assert_refused(self.deploy())
        after = parent.stat()
        self.assertEqual((before.st_uid, before.st_gid, before.st_mode),
                         (after.st_uid, after.st_gid, after.st_mode))
        self.assertFalse((parent / "uat").exists())

    def test_existing_storage_metadata_and_data_are_preserved(self):
        root = self.storage / "isolated/uat"
        root.mkdir(parents=True)
        root.chmod(0o2775)
        root.parent.chmod(0o2775)
        before = {}
        for name in ["cache", "sessions", "private", "public", "logs", "views"]:
            folder = root / name
            folder.mkdir()
            folder.chmod(0o2775)
            before[name] = (folder.stat().st_uid, folder.stat().st_gid, folder.stat().st_mode)
            (folder / "existing").write_text("preserved")
        result = self.deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        for name, metadata in before.items():
            folder = root / name
            self.assertEqual(metadata, (folder.stat().st_uid, folder.stat().st_gid, folder.stat().st_mode))
            self.assertEqual((folder / "existing").read_text(), "preserved")

    def test_missing_runtime_root_runtime_same_runtime_and_denied_probe_refuse(self):
        for runtime, env in [("", {}), ("-root", {}), ("runtime", {"RUNTIME_UID": "0"}),
                             ("runtime", {"RUNTIME_UID": str(os.geteuid())}),
                             ("runtime", {"REFUSE_SUDO": "true"})]:
            with self.subTest(runtime=runtime, env=env):
                self.assert_refused(self.deploy(runtime=runtime, **env))
                self.assertFalse((self.storage / "isolated").exists())

    def test_symlink_storage_parent_and_leaf_refuse_without_touching_target(self):
        foreign = self.directory / "foreign"
        foreign.mkdir()
        foreign.chmod(0o700)
        before = foreign.stat()
        for relative in ["storage", "storage/isolated", "storage/isolated/uat/cache"]:
            with self.subTest(relative=relative):
                path = self.app / relative
                path.parent.mkdir(parents=True, exist_ok=True)
                if path.exists(): shutil.rmtree(path)
                path.symlink_to(foreign, target_is_directory=True)
                self.assert_refused(self.deploy())
                self.assertEqual((before.st_gid, before.st_mode),
                                 (foreign.stat().st_gid, foreign.stat().st_mode))
                path.unlink()
                if relative == "storage":
                    path.mkdir(); path.chmod(0o2775)

    def test_existing_non_setgid_or_unwritable_leaf_refuses(self):
        root = self.storage / "isolated/uat"
        root.mkdir(parents=True)
        root.chmod(0o2775)
        root.parent.chmod(0o2775)
        leaf = root / "cache"
        leaf.mkdir()
        for mode in [0o775, 0o2755]:
            leaf.chmod(mode)
            self.assert_refused(self.deploy())
            self.assertEqual(leaf.stat().st_mode & 0o7777, mode)

    def test_production_ignores_runtime_configuration_and_isolated_storage(self):
        result = self.deploy(profile="production", runtime="")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse((self.storage / "isolated").exists())
        self.assertNotIn("storage:link", self.calls.read_text())

    def test_rejected_isolation_mutates_no_isolated_storage(self):
        self.assert_refused(self.deploy(REFUSE_ISOLATION="true"))
        self.assertFalse((self.storage / "isolated").exists())


class RealRuntimeIdentityTest(StorageAccessTest):
    # Real identity tests run explicitly in CI, not as silent successful skips.
    def setUp(self):
        super().setUp()
        self.write("id", "#!/bin/bash\nexec /usr/bin/id \"$@\"\n")
        (self.bin / "sudo").unlink()
        self.uid = os.geteuid()
        self.gid = os.getegid()
        self.runtime_gid = int(subprocess.check_output(["id", "-g", "nobody"], text=True))
        subprocess.run(["sudo", "-n", "chgrp", str(self.runtime_gid), str(self.storage)], check=True)
        self.storage.chmod(0o2775)

    def deploy(self, profile="uat", runtime="nobody", umask=0o077, **env):
        groups = sorted(set(os.getgroups()) | {self.gid, self.runtime_gid})
        command = ["sudo", "-n", "setpriv", f"--reuid={self.uid}", f"--regid={self.gid}",
                   "--groups=" + ",".join(map(str, groups)), "env",
                   f"PATH={self.bin}:/usr/bin:/bin", f"CALLS={self.calls}",
                   "bash", str(SCRIPT), str(self.app), profile, runtime]
        return subprocess.run(command, capture_output=True, text=True, umask=umask, timeout=20)

    def tearDown(self):
        # Runtime-owned data is confined to this newly-created temporary fixture.
        subprocess.run(["sudo", "-n", "rm", "-rf", "--", str(self.directory)], check=True)

    def test_distinct_deploy_and_runtime_can_really_write_with_private_umask(self):
        result = self.deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        root = self.storage / "isolated/uat"
        subprocess.run(["sudo", "-n", "-u", "nobody", "--", "bash", "-c",
                        'umask 007; printf runtime > "$1/sessions/runtime-proof"', "--", str(root)], check=True)
        self.assertNotEqual((root / "sessions/runtime-proof").stat().st_uid, self.uid)
        self.assertEqual(subprocess.check_output(["sudo", "-n", "-u", "nobody", "--", "cat",
                                                 str(root / "sessions/runtime-proof")], text=True), "runtime")
        (root / "cache/deploy-proof").write_text("deploy")
        subprocess.run(["sudo", "-n", "-u", "nobody", "--", "/usr/bin/test", "-r",
                        str(root / "cache/deploy-proof")], check=True)
        # Blade refreshes a stale compiled view by setting its timestamp, which
        # only the owner may do, so the deploy must leave compiling to the runtime.
        self.assertEqual(list((root / "views").iterdir()), [])
        subprocess.run(["sudo", "-n", "-u", "nobody", "--", "bash", "-c",
                        'umask 007; printf compiled > "$1/views/runtime-compiled"'
                        ' && touch -d @1700000000 "$1/views/runtime-compiled"', "--", str(root)], check=True)
        (root / "views/deploy-compiled").write_text("compiled")
        refused = subprocess.run(["sudo", "-n", "-u", "nobody", "--", "touch", "-d", "@1700000000",
                                  str(root / "views/deploy-compiled")], capture_output=True, text=True)
        self.assertNotEqual(refused.returncode, 0, "the runtime set the time on a deploy-owned view")

    def test_actual_runtime_cannot_traverse_existing_private_parent(self):
        parent = self.storage / "isolated"
        parent.mkdir(mode=0o700)
        self.assert_refused(self.deploy())
        self.assertEqual(parent.stat().st_mode & 0o7777, 0o700)
        self.assertFalse((parent / "uat").exists())

    def test_existing_runtime_owned_directories_keep_their_owner(self):
        root = self.storage / "isolated/uat"
        root.mkdir(parents=True)
        root.chmod(0o2775)
        root.parent.chmod(0o2775)
        for name in ["cache", "sessions", "private", "public", "logs", "views"]:
            folder = root / name
            folder.mkdir()
            subprocess.run(["sudo", "-n", "chown", f"nobody:{self.runtime_gid}", str(folder)], check=True)
            subprocess.run(["sudo", "-n", "chmod", "2775", str(folder)], check=True)
        result = self.deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        for name in ["cache", "sessions", "private", "public", "logs", "views"]:
            self.assertNotEqual((root / name).stat().st_uid, self.uid)


if __name__ == "__main__":
    suite = unittest.defaultTestLoader.loadTestsFromTestCase(StorageAccessTest)
    if os.getenv("REQUIRE_REAL_RUNTIME_IDENTITIES") == "true":
        # Only these three additional cases use real identities; inherited stub
        # cases stay in the portable suite. Missing sudo is an error, never skip.
        for name in ["test_distinct_deploy_and_runtime_can_really_write_with_private_umask",
                     "test_actual_runtime_cannot_traverse_existing_private_parent",
                     "test_existing_runtime_owned_directories_keep_their_owner"]:
            suite.addTest(RealRuntimeIdentityTest(name))
    result = unittest.TextTestRunner(verbosity=2).run(suite)
    sys.exit(not result.wasSuccessful())
