"""Opt-in real installs/builds on this project's isolated source copy.

Run with HOSTINGER_BENCHMARK=1 locally; CI retains exactly one frontend build.
"""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time
import unittest

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / '.github/hostinger'))
import build_artifact as artifact


@unittest.skipUnless(os.environ.get('HOSTINGER_BENCHMARK') == '1', 'Opt-in isolated install/build benchmark')
class RealBuildTests(unittest.TestCase):
    def test_cold_warm_lock_change_and_source_freshness(self):
        scratch = ROOT / 'storage/framework/testing/hostinger'
        scratch.mkdir(parents=True, exist_ok=True)
        with tempfile.TemporaryDirectory(dir=scratch) as temporary:
            self.fixture = Path(temporary).resolve()
            source = self.fixture / 'source'
            source.mkdir()
            tracked = subprocess.check_output(['git', 'ls-files', '-z'], cwd=ROOT).decode().split('\0')
            for name in tracked:
                if not name or name.split('/')[0] not in {'app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'artisan', 'package.json', 'package-lock.json', 'composer.json', 'composer.lock', 'vite.config.ts', 'tsconfig.json', '.env.example'}:
                    continue
                path = ROOT / name
                if path.is_file() and not path.is_symlink():
                    target = source / name
                    target.parent.mkdir(parents=True, exist_ok=True)
                    shutil.copyfile(path, target)
            for directory in ('storage/framework/cache/data', 'storage/framework/views', 'storage/framework/sessions', 'storage/logs', 'bootstrap/cache'):
                (source / directory).mkdir(parents=True, exist_ok=True)
            shutil.copyfile(source / '.env.example', source / '.env')
            npm, composer, php = (shutil.which(tool) for tool in ('npm', 'composer', 'php'))
            self.assertTrue(npm and composer and php, 'npm, Composer, and PHP are required')
            report = dict(platform=sys.platform, stages=[], runtime={})
            environment = os.environ | dict(COMPOSER_CACHE_DIR=str(self.fixture / 'composer-downloads'))
            def run(stage, command, env=None, cache_hit=None, expect_success=True):
                started = time.monotonic()
                result = subprocess.run(command, cwd=source, env=env or environment, capture_output=True, text=True)
                seconds = round(time.monotonic() - started, 3)
                report['stages'].append(dict(stage=stage, seconds=seconds, cache_hit=cache_hit, exit_code=result.returncode))
                (scratch / (stage + '.log')).write_text(result.stdout + result.stderr, encoding='utf-8')
                (scratch / 'benchmark.json').write_text(json.dumps(report, indent=2), encoding='utf-8')
                print(f'{stage}: {seconds}s (exit {result.returncode}, cache hit {cache_hit})', flush=True)
                if expect_success:
                    self.assertEqual(result.returncode, 0, (result.stdout + result.stderr)[-4000:])
                else:
                    self.assertNotEqual(result.returncode, 0, 'The intentional build error must fail')
                return result.stdout
            report['runtime'] = dict(node=run('node-version', ['node', '--version']).strip(),
                                     php=run('php-version', [php, '--version']).splitlines()[0],
                                     composer=run('composer-version', [composer, '--version']).strip())
            lock = source / 'package-lock.json'
            original_lock = lock.read_bytes()
            npm_cache = self.fixture / 'npm-downloads'
            expected = json.loads(original_lock)['packages']['node_modules/clsx']['version']
            for label, options, hit in [('cold', [], False), ('warm', ['--offline'], True)]:
                run('npm-' + label, [npm, 'ci', '--cache', str(npm_cache), *options], cache_hit=hit)
                self.assertEqual(lock.read_bytes(), original_lock)
                self.assertEqual(json.loads((source / 'node_modules/clsx/package.json').read_text(encoding='utf-8'))['version'], expected)
            production = [composer, 'install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction', '--no-scripts']
            composer_lock = (source / 'composer.lock').read_bytes()
            for label, hit in [('cold', False), ('warm', True)]:
                vendor = source / 'vendor'
                if vendor.exists():
                    self.assertTrue(vendor.resolve().is_relative_to(self.fixture))
                    shutil.rmtree(vendor)
                run('composer-' + label, production, environment | (dict(COMPOSER_DISABLE_NETWORK='1') if hit else {}), cache_hit=hit)
                self.assertEqual((source / 'composer.lock').read_bytes(), composer_lock)
                installed = json.loads((vendor / 'composer/installed.json').read_text(encoding='utf-8'))['packages']
                self.assertEqual({package['name']: package['version'] for package in installed},
                                 {package['name']: package['version'] for package in json.loads(composer_lock)['packages']})
                self.assertNotIn('pestphp/pest', {package['name'] for package in installed})
            run('package-discovery', [php, 'artisan', 'package:discover'])
            identity = dict(GITHUB_SHA=subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT).decode().strip(), GITHUB_RUN_ID='1', RUNNER_TEMP=str(self.fixture))
            run('build-baseline', [npm, 'run', 'build'])
            baseline = artifact.validate_build(source / 'public/build')
            blade = source / 'resources/views/app.blade.php'
            sentinel = 137
            blade.write_text(blade.read_text(encoding='utf-8') + f'\n<div class="min-h-[{sentinel}px]"></div>\n', encoding='utf-8')
            run('build-blade-tailwind', [npm, 'run', 'build'])
            blade_build = artifact.validate_build(source / 'public/build')
            self.assertNotEqual(baseline, blade_build)
            styles = ''.join(path.read_text(encoding='utf-8') for path in (source / 'public/build/assets').glob('*.css'))
            self.assertIn(f'min-height:{sentinel}px', styles)
            javascript = source / 'resources/js/public-blog.ts'
            marker = 'hostinger-pilot-' + self.fixture.name
            javascript.write_text(javascript.read_text(encoding='utf-8') + f'\nconsole.info("{marker}");\n', encoding='utf-8')
            run('build-javascript', [npm, 'run', 'build'])
            js_build = artifact.validate_build(source / 'public/build')
            self.assertNotEqual(blade_build, js_build)
            self.assertTrue(any(marker in path.read_text(encoding='utf-8') for path in (source / 'public/build/assets').glob('*.js')))
            css = source / 'resources/css/app.css'
            css.write_text(css.read_text(encoding='utf-8') + '\n:root { --hostinger-pilot: 812px; }\n', encoding='utf-8')
            run('build-css', [npm, 'run', 'build'])
            css_build = artifact.validate_build(source / 'public/build')
            self.assertNotEqual(js_build, css_build)
            folder = self.fixture / 'artifact'
            started = time.monotonic()
            artifact.seal(source, folder, identity)
            destination = self.fixture / 'destination'
            destination.mkdir()
            artifact.restore(destination, folder, artifact.digest(folder / 'build.tar'), identity)
            artifact.verify_build(destination, identity)
            self.assertEqual(css_build, artifact.validate_build(destination / 'public/build'))
            report['artifact_files'] = len(css_build)
            report['artifact_roundtrip_seconds'] = round(time.monotonic() - started, 3)
            report['source_checks'] = dict(blade_tailwind=True, javascript=True, css=True, artifact_identical=True)
            sealed_digest = artifact.digest(folder / 'build.tar')
            javascript.write_text(javascript.read_text(encoding='utf-8') + '\nconst = broken syntax;\n', encoding='utf-8')
            run('build-intentional-failure', [npm, 'run', 'build'], expect_success=False)
            self.assertEqual(artifact.digest(folder / 'build.tar'), sealed_digest)
            report['source_checks']['build_failure'] = True
            # Change the actual isolated lockfile to another allowed dependency version.
            package = json.loads((source / 'package.json').read_text(encoding='utf-8'))
            package['dependencies']['clsx'] = '2.1.0'
            (source / 'package.json').write_text(json.dumps(package))
            run('npm-lock-change', [npm, 'install', '--package-lock-only', '--ignore-scripts', '--cache', str(npm_cache)])
            changed_lock = lock.read_bytes()
            self.assertNotEqual(hashlib.sha256(original_lock).digest(), hashlib.sha256(changed_lock).digest())
            # Downloads are reused here; the Actions exact-key miss is checked by
            # the lock hash assertions and workflow tests, not a live cache API.
            run('npm-changed-lock', [npm, 'ci', '--cache', str(npm_cache)])
            self.assertEqual(json.loads((source / 'node_modules/clsx/package.json').read_text(encoding='utf-8'))['version'], '2.1.0')
            self.assertEqual(lock.read_bytes(), changed_lock)
            # Composer lock metadata changes invalidate the exact-key cache without changing versions.
            changed_composer = json.loads(composer_lock)
            changed_composer['_readme'].append('Isolated pilot cache-key test')
            (source / 'composer.lock').write_text(json.dumps(changed_composer))
            self.assertNotEqual(hashlib.sha256(composer_lock).digest(), hashlib.sha256((source / 'composer.lock').read_bytes()).digest())
            run('composer-changed-lock', production)
            report['lock_change_checks'] = dict(npm_version='2.1.0', composer_versions_unchanged=True,
                                              reused_local_downloads=True, live_actions_cache_not_measured=True)
            (scratch / 'benchmark.json').write_text(json.dumps(report, indent=2), encoding='utf-8')
