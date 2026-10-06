"""Artifact and remote filesystem tests run entirely on disposable fixtures."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tarfile
import tempfile
import unittest
from unittest.mock import patch

if os.name == 'posix':
    import pwd

ROOT = Path(__file__).resolve().parents[2]
HELPERS = ROOT / '.github/hostinger'
sys.path.insert(0, str(HELPERS))
import build_artifact as artifact
import deploy
import measure


class TimingTests(unittest.TestCase):
    def test_command_failure_stops_the_job_and_records_duration_cache_miss_and_exit_code(self):
        with tempfile.TemporaryDirectory() as directory:
            result = subprocess.run([sys.executable, str(HELPERS / 'measure.py'), '--cache-hit', '', 'frontend-build', '--',
                                     sys.executable, '-c', 'raise SystemExit(13)'], cwd=directory, capture_output=True, text=True,
                                    env=os.environ | dict(GITHUB_STEP_SUMMARY='', HOSTINGER_TIMING_JOB='test'))
            self.assertEqual(result.returncode, 13)
            metrics = list((Path(directory) / 'deploy-diagnostics').glob('timings-*.jsonl'))
            metric = json.loads(metrics[0].read_text())
            self.assertEqual(metric['stage'], 'frontend-build')
            self.assertEqual(metric['outcome'], 'failure')
            self.assertEqual(metric['cache_hit'], 'false')
            self.assertGreaterEqual(metric['seconds'], 0)


class ArtifactTests(unittest.TestCase):
    def setUp(self):
        recording = patch.object(measure, 'record')
        recording.start()
        self.addCleanup(recording.stop)
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.source = self.root / 'source'
        self.build = self.source / 'public/build'
        (self.build / 'assets').mkdir(parents=True)
        manifest = {entry: dict(file=f'assets/entry-{index}.js', isEntry=True,
                               dynamicImports=['chunk']) for index, entry in enumerate(artifact.ENTRIES)}
        manifest['chunk'] = dict(file='assets/lazy.js', css=['assets/lazy.css'])
        (self.build / 'manifest.json').write_text(json.dumps(manifest))
        (self.build / 'fonts-manifest.json').write_text(json.dumps(dict(style=dict(file='assets/fonts.css'), preloads=[dict(file='assets/font.woff2')])))
        for name in ('entry-0.js', 'entry-1.js', 'entry-2.js', 'lazy.js', 'lazy.css', 'fonts.css', 'font.woff2'):
            (self.build / 'assets' / name).write_text(name)
        self.folder = self.root / 'artifact'
        self.env = dict(GITHUB_SHA='a' * 40, GITHUB_RUN_ID='123', RUNNER_TEMP=str(self.root))
        self.destination = self.root / 'destination'
        self.destination.mkdir()

    def seal(self):
        artifact.seal(self.source, self.folder, self.env)
        return artifact.digest(self.folder / 'build.tar')

    def test_roundtrip_keeps_manifest_fonts_dynamic_chunks_and_exact_bytes(self):
        artifact.restore(self.destination, self.folder, self.seal(), self.env)
        self.assertEqual(artifact.inventory(self.build), artifact.inventory(self.destination / 'public/build'))
        artifact.verify_build(self.destination, self.env)
        (self.destination / 'public/build/assets/lazy.js').write_text('tampered')
        with self.assertRaisesRegex(ValueError, 'differ'):
            artifact.verify_build(self.destination, self.env)

    def test_other_commit_run_missing_corrupt_or_wrong_digest_fails_before_restore(self):
        digest = self.seal()
        for env in (self.env | dict(GITHUB_SHA='b' * 40), self.env | dict(GITHUB_RUN_ID='999')):
            with self.subTest(env=env), self.assertRaises(ValueError):
                artifact.restore(self.destination, self.folder, digest, env)
            self.assertFalse((self.destination / 'public/build').exists())
        with self.assertRaises(ValueError):
            artifact.restore(self.destination, self.folder, '0' * 64, self.env)
        with (self.folder / 'build.tar').open('ab') as stream:
            stream.write(b'corrupt')
        with self.assertRaises(ValueError):
            artifact.restore(self.destination, self.folder, digest, self.env)
        (self.folder / 'build.tar').unlink()
        with self.assertRaises(FileNotFoundError):
            artifact.restore(self.destination, self.folder, digest, self.env)

    def test_incomplete_build_cannot_be_sealed(self):
        (self.build / 'assets/lazy.js').unlink()
        with self.assertRaisesRegex(ValueError, 'Missing Vite dependency'):
            self.seal()
        self.assertFalse(self.folder.exists())

    def test_unpreloaded_font_missing_from_families_is_rejected(self):
        fonts = json.loads((self.build / 'fonts-manifest.json').read_text())
        fonts['families'] = dict(example=dict(variants={'500:normal': dict(files=[dict(file='assets/missing.woff2')])}))
        (self.build / 'fonts-manifest.json').write_text(json.dumps(fonts))
        with self.assertRaisesRegex(ValueError, 'Missing font dependency'):
            self.seal()

    def test_artifact_is_validated_before_any_server_operation(self):
        config = json.loads((HELPERS / 'profile.json').read_text()) | dict(output_dir=str(self.source))
        environment = self.env | dict(HOSTINGER_TARGET_DIR='/home/test/app', HOSTINGER_SSH_HOST='example.test', HOSTINGER_SSH_USER='test')
        instance = deploy.Deployment(config, environment)
        with patch.object(instance, 'remote') as remote, patch.object(instance, 'rsync') as rsync:
            with self.assertRaises(FileNotFoundError):
                instance.transfer()
        remote.assert_not_called()
        rsync.assert_not_called()

    def test_archive_links_traversal_and_duplicate_files_are_rejected(self):
        for attack in ('link', 'traversal', 'duplicate'):
            with self.subTest(attack=attack):
                self.seal()
                with tarfile.open(self.folder / 'build.tar', 'a') as stream:
                    member = tarfile.TarInfo('public/build/manifest.json' if attack == 'duplicate' else 'public/build/../../outside')
                    if attack == 'link':
                        member.type, member.linkname = tarfile.SYMTYPE, '/tmp/outside'
                    stream.addfile(member)
                digest = artifact.digest(self.folder / 'build.tar')
                metadata = json.loads((self.folder / 'provenance.json').read_text())
                metadata['archive_sha256'] = digest
                (self.folder / 'provenance.json').write_text(json.dumps(metadata))
                with self.assertRaises(ValueError):
                    artifact.restore(self.destination, self.folder, digest, self.env)
                self.assertFalse((self.destination / 'public/build').exists())


@unittest.skipUnless(os.name == 'posix' and shutil.which('rsync'), 'Linux and rsync required')
class RemoteTests(unittest.TestCase):
    def setUp(self):
        recording = patch.object(measure, 'record')
        recording.start()
        self.addCleanup(recording.stop)
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.target = self.root / 'site'
        (self.target / 'public/build/assets').mkdir(parents=True)
        self.state = self.root / '.site.hostinger-ci'
        self.tools = self.root / 'tools'
        self.tools.mkdir()
        php = self.tools / 'php'
        php.write_text('''#!/usr/bin/env python3
import os, sys
from pathlib import Path
if sys.argv[1:2] == ['-r']:
    print(os.getuid(), end='')
elif sys.argv[1:2] == ['artisan']:
    command = sys.argv[2]
    with open('commands.log', 'a') as stream: stream.write(command + '\\n')
    if os.environ.get('FAIL_COMMAND') == command: sys.exit(1)
    if command == 'down': Path('maintenance').touch()
    if command == 'up': Path('maintenance').unlink(missing_ok=True)
else:
    sys.stdin.read()
''')
        php.chmod(0o755)
        self.env = os.environ | dict(PATH=str(self.tools) + ':' + os.environ['PATH'])
        (self.target / '.env').touch()
        (self.target / 'artisan').touch()
        self.run = 'a' * 32
        self.script = (HELPERS / 'remote-deploy.sh').read_text()
        self.asset = self.target / 'public/build/assets/app-12345678.js'
        self.asset.write_text('new')
        self.inventory = artifact.digest(self.asset) + '  public/build/assets/' + self.asset.name

    def execute(self, phase, verified=True, max_files=10000, **environment):
        script = "incoming='" + self.inventory + "'\n" + self.script
        return subprocess.run(['bash', '-s', '--', str(self.target), phase, self.run, 'public/build/assets',
                               '2', '7', 'laravel-vite', 'true', str(max_files), '536870912', '3', '', str(verified).lower(), 'true',
                               pwd.getpwuid(os.getuid()).pw_name], input=script, text=True, capture_output=True,
                              env=self.env | environment)

    def test_new_writable_roots_and_maintenance_success_with_stage_timings(self):
        result = self.execute('prepare')
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue((self.target / 'storage/app/private').is_dir())
        self.assertTrue((self.target / 'maintenance').exists())
        result = self.execute('optimize')
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse((self.target / 'maintenance').exists())
        for stage in ('storage-permissions', 'public-permissions', 'migration', 'cache-build'):
            self.assertIn('"stage":"' + stage + '"', result.stdout)
        self.assertEqual((self.target / 'commands.log').read_text().splitlines(),
                         ['down', 'optimize:clear', 'migrate', 'config:cache', 'route:cache', 'view:cache', 'up'])

    def test_python_remote_transport_collects_metrics_and_propagates_failures(self):
        ssh = self.root / 'local-ssh'
        ssh.write_text('#!/bin/bash\nexec bash -c "$2"\n')
        ssh.chmod(0o755)
        config = json.loads((HELPERS / 'profile.json').read_text()) | dict(php_version='', php_web_user=pwd.getpwuid(os.getuid()).pw_name)
        environment = dict(HOSTINGER_TARGET_DIR=str(self.target), HOSTINGER_SSH_HOST='example.test', HOSTINGER_SSH_USER='test', RUNNER_TEMP=str(self.root))
        instance = deploy.Deployment(config, environment)
        instance.ssh = [str(ssh)]
        with patch.dict(os.environ, self.env), patch.object(deploy, 'record') as metrics:
            instance.remote('prepare', self.run, self.inventory, incoming_bytes=3)
            self.assertTrue((self.target / 'maintenance').exists())
            self.assertTrue(any(call.kwargs.get('stage') == 'storage-permissions' for call in metrics.call_args_list))
            with patch.dict(os.environ, dict(FAIL_COMMAND='migrate')):
                with self.assertRaises(subprocess.CalledProcessError):
                    instance.remote('optimize', self.run)
            self.assertTrue((self.target / 'maintenance').exists())
            self.assertTrue(any(call.kwargs.get('stage') == 'migration' and call.kwargs.get('outcome') == 'failure' for call in metrics.call_args_list))

    def test_upload_tree_is_never_walked_or_chmodded(self):
        uploads = self.target / 'storage/app/public/old/nested'
        uploads.mkdir(parents=True)
        old = uploads / 'photo.jpg'
        old.write_text('legacy upload')
        old.chmod(0o600)
        old_time = old.stat().st_ctime_ns
        find = self.tools / 'find'
        find.write_text('#!/bin/bash\nprintf "%s\\n" "$*" >> "$FIND_LOG"\nexec /usr/bin/find "$@"\n')
        find.chmod(0o755)
        log = self.root / 'find.log'
        for phase in ('prepare', 'optimize', 'cleanup'):
            result = self.execute(phase, FIND_LOG=str(log))
            self.assertEqual(result.returncode, 0, result.stderr)
        self.assertNotIn('storage', log.read_text())
        self.assertEqual(old.stat().st_ctime_ns, old_time)
        self.assertEqual(old.stat().st_mode & 0o777, 0o600)

    def test_bad_permission_and_external_symlink_fail_before_maintenance(self):
        storage = self.target / 'storage'
        storage.mkdir()
        storage.chmod(0o500)
        result = self.execute('prepare')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('no recursive chmod', result.stderr)
        self.assertFalse((self.target / 'maintenance').exists())
        storage.chmod(0o755)
        outside = self.root / 'outside'
        outside.mkdir()
        (storage / 'app').symlink_to(outside, target_is_directory=True)
        result = self.execute('prepare')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('Symlink in runtime directory', result.stderr)
        self.assertEqual(list(outside.iterdir()), [])

    def test_wrong_ownership_is_diagnosed(self):
        if os.getuid() != 0:
            self.skipTest('Ownership fixture requires container root')
        storage = self.target / 'storage'
        storage.mkdir()
        os.chown(storage, 1001, 1001)
        result = self.execute('prepare')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('Ownership/access mismatch', result.stderr)
        self.assertIn('uid=1001', result.stderr)

    def test_down_migration_cache_and_post_transfer_permission_failures(self):
        result = self.execute('prepare', FAIL_COMMAND='down')
        self.assertNotEqual(result.returncode, 0)
        self.assertFalse((self.target / 'maintenance').exists())
        for command in ('migrate', 'config:cache', 'route:cache', 'view:cache'):
            with self.subTest(command=command):
                self.assertEqual(self.execute('prepare').returncode, 0)
                result = self.execute('optimize', FAIL_COMMAND=command)
                self.assertNotEqual(result.returncode, 0)
                self.assertTrue((self.target / 'maintenance').exists())
                self.assertIn('"outcome":"failure"', result.stdout)
        (self.target / 'public').chmod(0o700)
        result = self.execute('optimize')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('Public directory', result.stderr)
        self.assertTrue((self.target / 'maintenance').exists())

    def test_immutable_collisions_and_retention_budget_fail_closed(self):
        self.inventory = '0' * 64 + '  public/build/assets/' + self.asset.name
        result = self.execute('prepare')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('Immutable asset collision', result.stderr)
        self.assertFalse((self.target / 'maintenance').exists())
        self.assertFalse(self.state.exists())
        self.inventory = artifact.digest(self.asset) + '  public/build/assets/' + self.asset.name
        result = self.execute('prepare', max_files=1)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('budget would be exceeded', result.stderr)
        self.assertFalse((self.target / 'maintenance').exists())

    def test_http_failure_cleanup_retains_last_verified_release_and_grace_period(self):
        ancient = self.target / 'public/build/assets/ancient-12345678.js'
        ancient.write_text('ancient')
        self.assertEqual(self.execute('prepare').returncode, 0)
        self.assertEqual(self.execute('optimize').returncode, 0)
        self.assertEqual(self.execute('cleanup').returncode, 0)
        history = self.state / 'history'
        for path in history.iterdir():
            os.utime(path, (1, 1))
            if 'baseline' not in path.name:
                path.rename(history / '19900101000000-verified.txt')
        latest = self.target / 'public/build/assets/latest-12345678.js'
        latest.write_text('latest')
        self.inventory = artifact.digest(latest) + '  public/build/assets/' + latest.name
        for stamp in ('20000101000000', '20010101000000'):
            path = history / (stamp + '-unverified.txt')
            path.write_text(self.inventory + '\n')
            os.utime(path, (1, 1))
        self.run = 'b' * 32
        self.assertEqual(self.execute('prepare').returncode, 0)
        self.assertEqual(self.execute('optimize').returncode, 0)
        result = self.execute('cleanup', verified=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(self.asset.exists())
        self.assertTrue(latest.exists())
        self.assertFalse(ancient.exists())

    def test_asset_transfer_failure_does_not_publish_application_or_optimize(self):
        source = self.root / 'source'
        (source / 'public/build/assets').mkdir(parents=True)
        (source / 'public/build/assets/current-12345678.js').write_text('new')
        (source / 'public/build/manifest.json').write_text('{}')
        config = json.loads((HELPERS / 'profile.json').read_text()) | dict(output_dir=str(source), require_build_artifact=False)
        environment = dict(HOSTINGER_TARGET_DIR=str(self.target), HOSTINGER_SSH_HOST='example.test', HOSTINGER_SSH_USER='test', RUNNER_TEMP=str(self.root))
        instance = deploy.Deployment(config, environment)
        with patch.object(instance, 'remote') as remote, patch.object(instance, 'rsync', side_effect=subprocess.CalledProcessError(1, 'rsync')) as transfer:
            with self.assertRaises(subprocess.CalledProcessError):
                instance.transfer()
        self.assertEqual(transfer.call_count, 1)
        self.assertEqual(remote.call_args.args[0], 'prepare')

    def test_rsync_new_file_modes_executable_and_protected_old_assets(self):
        source = self.root / 'source'
        (source / 'public/build/assets').mkdir(parents=True)
        (source / 'public/build/assets/new-12345678.js').write_text('new')
        (source / 'public/build/manifest.json').write_text('new manifest')
        executable = source / 'artisan'
        executable.write_text('#!/bin/sh\nexit 0\n')
        executable.chmod(0o755)
        (source / 'ordinary.txt').write_text('read me')
        ssh = self.root / 'local-ssh'
        ssh.write_text('''#!/usr/bin/env python3
import os, sys
args = sys.argv[1:]
while args[0].startswith('-'):
    args = args[2:] if args[0] in ('-l', '-p', '-i', '-o') else args[1:]
args = args[1:]
if len(args) == 1: os.execvp('bash', ['bash', '-c', args[0]])
os.execvp(args[0], args)
''')
        ssh.chmod(0o755)
        config = json.loads((HELPERS / 'profile.json').read_text()) | dict(output_dir=str(source), require_build_artifact=False)
        env = dict(HOSTINGER_TARGET_DIR=str(self.target), HOSTINGER_SSH_HOST='example.test', HOSTINGER_SSH_USER='test', RUNNER_TEMP=str(self.root))
        instance = deploy.Deployment(config, env)
        instance.ssh = [str(ssh)]
        instance.rsync(source / 'public/build/assets', str(self.target / 'public/build/assets'))
        instance.rsync(source, str(self.target), config['protected_paths'] + config['immutable_dirs'], delete=True)
        self.assertEqual(executable.stat().st_mode & 0o777, 0o755)
        self.assertEqual((self.target / 'artisan').stat().st_mode & 0o777, 0o755)
        self.assertEqual((self.target / 'ordinary.txt').stat().st_mode & 0o777, 0o644)
        self.assertEqual((self.target / 'public').stat().st_mode & 0o777, 0o755)
        self.assertTrue(self.asset.exists())
        self.assertEqual((self.target / 'public/build/assets/new-12345678.js').stat().st_mode & 0o777, 0o644)
        self.assertTrue((self.target / '.env').exists())


if __name__ == '__main__':
    unittest.main()
