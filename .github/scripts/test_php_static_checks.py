#!/usr/bin/env python3
"""Exercise the real static-check supervisor with owned child processes."""

import importlib.util
import json
import os
from pathlib import Path
import signal
import subprocess
import sys
import tempfile
import time
import unittest
from unittest.mock import patch

SCRIPT = Path(__file__).with_name('run-php-static-checks.py').resolve()
SPEC = importlib.util.spec_from_file_location('php_static_checks', SCRIPT)
policy = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(policy)

CHILD = '''#!/usr/bin/env python3
import json, os, signal, subprocess, sys, time
from pathlib import Path
root=Path(os.environ['STATIC_TEST_ROOT']); name=sys.argv[1]
settings=json.loads((root/'settings.json').read_text()).get(name,{})
if settings.get('ignore_term'): signal.signal(signal.SIGTERM, signal.SIG_IGN)
(root/(name+'.ready')).write_text(json.dumps({'pid':os.getpid(),'group':os.getpgrp()}))
if settings.get('descendant'):
 child=subprocess.Popen([sys.executable,'-c',"import os,time; from pathlib import Path; Path("+repr(str(root/(name+'.descendant')))+").write_text(str(os.getpid())); time.sleep(60)"])
while not (root/(name+'.finish')).exists(): time.sleep(.01)
if settings.get('kill'): os.kill(os.getpid(),signal.SIGKILL)
print(name+' stdout '+('X'*settings.get('output',0)),flush=True)
print(name+' stderr '+('Y'*settings.get('output',0)),file=sys.stderr,flush=True)
sys.exit(settings.get('exit',0))
'''


def live(pid):
    result = subprocess.run(['ps', '-o', 'stat=', '-p', str(pid)], capture_output=True, text=True)
    return result.returncode == 0 and bool(result.stdout.strip()) and not result.stdout.strip().startswith('Z')


class StaticSupervisorTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.root = Path(self.temporary.name)
        self.shim = self.root / 'composer'
        self.shim.write_text(CHILD.replace("#!/usr/bin/env python3", "#!" + sys.executable, 1))
        self.shim.chmod(0o755)
        self.settings({})
        self.children = []

    def tearDown(self):
        for process in self.children:
            if process.poll() is None:
                process.send_signal(signal.SIGTERM)
                try:
                    process.communicate(timeout=6)
                except subprocess.TimeoutExpired:
                    process.kill()
                    process.communicate(timeout=3)
        self.temporary.cleanup()

    def settings(self, settings):
        (self.root / 'settings.json').write_text(json.dumps(settings))

    def start(self, code=None, extra=()):
        env = dict(os.environ, STATIC_TEST_ROOT=str(self.root), PATH=str(self.root) + os.pathsep + os.environ['PATH'])
        command = [sys.executable, str(SCRIPT), *extra] if code is None else [sys.executable, '-c', code]
        process = subprocess.Popen(command, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        self.children.append(process)
        return process

    def ready(self, name, descendant=False):
        path = self.root / (name + ('.descendant' if descendant else '.ready'))
        deadline = time.monotonic() + 5
        while time.monotonic() < deadline:
            if path.exists():
                try:
                    return int(path.read_text()) if descendant else json.loads(path.read_text())
                except (ValueError, json.JSONDecodeError):
                    pass
            time.sleep(.01)
        self.fail(f'{name} child did not start')

    def finish(self, name):
        (self.root / (name + '.finish')).touch()

    def poc_shim(self):
        child = CHILD.replace("#!/usr/bin/env python3", "#!" + sys.executable, 1)
        child = child.replace("name=sys.argv[1]", "name='poc-php'; assert sys.argv[1:] == ['.github/scripts/run-poc-tests.py','php']")
        shim = self.root / 'python3'
        shim.write_text(child)
        shim.chmod(0o755)

    def test_poc_overlaps_every_complete_static_check_and_requires_all_three(self):
        self.poc_shim()
        process = self.start(extra=['--poc'])
        names = ['lint:check', 'types:check', 'poc-php']
        for name in names:
            child = self.ready(name)
            self.assertEqual(child['pid'], child['group'])
        for name in ['lint:check', 'poc-php']: self.finish(name)
        time.sleep(.1)
        self.assertIsNone(process.poll(), 'Passing POC cannot hide pending full static analysis')
        self.finish('types:check')
        stdout, stderr = process.communicate(timeout=5)
        self.assertEqual(process.returncode, 0)
        for name in names:
            self.assertIn(name + ' stdout', stdout)
            self.assertIn(name + ' stderr', stderr)

    def test_each_failed_or_killed_poc_check_refuses_without_skipping_other_checks(self):
        self.poc_shim()
        names = ['lint:check', 'types:check', 'poc-php']
        for name in names:
            for failure in [{'exit': 7}, {'kill': True}]:
                with self.subTest(check=name, failure=failure):
                    for pattern in ['*.ready', '*.finish', '*.descendant']:
                        for path in self.root.glob(pattern): path.unlink()
                    self.settings({name: dict(failure, descendant=True)})
                    process = self.start(extra=['--poc'])
                    for check in names: self.ready(check)
                    descendant = self.ready(name, descendant=True)
                    for check in names: self.finish(check)
                    stdout, stderr = process.communicate(timeout=5)
                    self.assertEqual(process.returncode, 1)
                    for other in names:
                        if other != name: self.assertIn(other + ' stdout', stdout)
                    self.assertFalse(live(descendant))

    def test_poc_cancellation_refuses_and_stops_all_three_owned_process_groups(self):
        self.poc_shim()
        for signum in [signal.SIGINT, signal.SIGTERM]:
            with self.subTest(signal=signum):
                for pattern in ['*.ready', '*.finish', '*.descendant']:
                    for path in self.root.glob(pattern): path.unlink()
                names = ['lint:check', 'types:check', 'poc-php']
                self.settings({name: {'descendant': True} for name in names})
                process = self.start(extra=['--poc'])
                pids = []
                for name in names:
                    pids += [self.ready(name)['pid'], self.ready(name, descendant=True)]
                process.send_signal(signum)
                process.communicate(timeout=6)
                self.assertEqual(process.returncode, 128 + signum)
                self.assertFalse(any(live(pid) for pid in pids))

    def test_poc_option_keeps_both_original_commands_and_catalog_runner(self):
        for arguments, expected in [([], policy.CHECKS), (['--poc'], policy.CHECKS + (('POC PHP safety catalog', ('python3', '.github/scripts/run-poc-tests.py', 'php')),))]:
            with self.subTest(arguments=arguments), patch.object(policy, 'run_checks', return_value=7) as checks, patch.object(policy.signal, 'signal'):
                self.assertEqual(policy.main(arguments), 7)
                self.assertEqual(checks.call_args.args[0], expected)
        with patch.object(policy, 'run_checks') as checks, self.assertRaises(SystemExit) as error:
            policy.main(['--skip-static'])
        self.assertEqual(error.exception.code, 2)
        checks.assert_not_called()

    def test_both_complete_commands_start_before_either_finishes_and_both_are_required(self):
        process = self.start()
        for name in ['lint:check', 'types:check']:
            child = self.ready(name)
            self.assertEqual(child['pid'], child['group'])
        self.finish('lint:check')
        time.sleep(.1)
        self.assertIsNone(process.poll(), 'One passing check cannot pass the gate')
        self.finish('types:check')
        stdout, stderr = process.communicate(timeout=5)
        self.assertEqual(process.returncode, 0)
        for name in ['lint:check', 'types:check']:
            self.assertIn(name + ' stdout', stdout)
            self.assertIn(name + ' stderr', stderr)

    def test_nonzero_and_killed_check_each_fail_without_skipping_the_other_tool(self):
        for name in ['lint:check', 'types:check']:
            for failure in [{'exit': 7}, {'kill': True}]:
                with self.subTest(check=name, failure=failure):
                    for path in self.root.glob('*.ready'): path.unlink()
                    for path in self.root.glob('*.finish'): path.unlink()
                    for path in self.root.glob('*.descendant'): path.unlink()
                    self.settings({name: dict(failure, descendant=True)})
                    process = self.start()
                    for check in ['lint:check', 'types:check']: self.ready(check)
                    descendant = self.ready(name, descendant=True)
                    for check in ['lint:check', 'types:check']: self.finish(check)
                    stdout, stderr = process.communicate(timeout=5)
                    self.assertEqual(process.returncode, 1)
                    other = 'types:check' if name == 'lint:check' else 'lint:check'
                    self.assertIn(other + ' stdout', stdout)
                    self.assertFalse(live(descendant))

    def test_outputs_larger_than_pipe_capacity_are_not_lost_or_deadlocked(self):
        self.settings({name: {'output': 200000} for name in ['lint:check', 'types:check']})
        process = self.start()
        for name in ['lint:check', 'types:check']:
            self.ready(name)
            self.finish(name)
        stdout, stderr = process.communicate(timeout=5)
        self.assertEqual(process.returncode, 0)
        self.assertEqual(stdout.count('X'), 400000)
        self.assertEqual(stderr.count('Y'), 400000)

    def test_sigint_and_sigterm_refuse_and_stop_owned_tool_descendants(self):
        for signum in [signal.SIGINT, signal.SIGTERM]:
            with self.subTest(signal=signum):
                for path in self.root.glob('*.ready'): path.unlink()
                for path in self.root.glob('*.descendant'): path.unlink()
                self.settings({name: {'descendant': True} for name in ['lint:check', 'types:check']})
                process = self.start()
                pids = []
                for name in ['lint:check', 'types:check']:
                    pids.append(self.ready(name)['pid'])
                    pids.append(self.ready(name, descendant=True))
                process.send_signal(signum)
                process.communicate(timeout=6)
                self.assertEqual(process.returncode, 128 + signum)
                self.assertFalse(any(live(pid) for pid in pids))

    def test_spawn_failure_refuses_and_cleans_an_already_started_tool(self):
        native, started = subprocess.Popen, []
        def spawn(command, **kwargs):
            if started:
                raise FileNotFoundError('required tool missing')
            env = dict(os.environ, STATIC_TEST_ROOT=str(self.root))
            process = native([sys.executable, str(self.shim), 'lint:check'], env=env, **kwargs)
            started.append(process)
            self.ready('lint:check')
            return process
        with patch.object(policy.subprocess, 'Popen', side_effect=spawn):
            self.assertEqual(policy.run_checks([('first', ['first']), ('missing', ['missing'])]), 1)
        self.assertIsNotNone(started[0].poll())
        self.assertFalse(live(started[0].pid))

    def test_cancellation_kills_a_tool_which_ignores_graceful_termination(self):
        self.settings({'lint:check': {'ignore_term': True}})
        process = self.start()
        pids = [self.ready(name)['pid'] for name in ['lint:check', 'types:check']]
        process.send_signal(signal.SIGTERM)
        process.communicate(timeout=6)
        self.assertEqual(process.returncode, 143)
        self.assertFalse(any(live(pid) for pid in pids))

    def test_no_checks_cannot_return_a_green_gate(self):
        with self.assertRaises(ValueError): policy.run_checks([])

    def test_cancellation_observed_during_final_poll_cannot_return_success(self):
        received = [0]
        class Finished:
            returncode = 0
            def poll(self):
                received[0] = signal.SIGTERM
                return 0
        with patch.object(policy.subprocess, 'Popen', return_value=Finished()), patch.object(policy, 'stop_checks') as cleanup:
            result = policy.run_checks([('lint', ['lint']), ('types', ['types'])], cancelled=lambda: received[0])
        self.assertEqual(result, 143)
        cleanup.assert_called_once()


if __name__ == '__main__':
    unittest.main()
