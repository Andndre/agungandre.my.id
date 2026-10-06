<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/** @return array<int, array{source: string, target_suffix: string, args: array<int, string>}> */
function deploymentAssetTransfers(): array
{
    $workflow = Yaml::parseFile(base_path('.github/workflows/deploy.yml'));
    $profile = json_decode(File::get(base_path('.github/hostinger/profile.json')), true, 512, JSON_THROW_ON_ERROR);

    $steps = $workflow['jobs']['deploy']['steps'] ?? [];
    $hasTransferStep = false;
    foreach ($steps as $step) {
        if (($step['run'] ?? '') === 'python3 .github/hostinger/deploy.py transfer') {
            $hasTransferStep = true;
            break;
        }
    }

    if (! $hasTransferStep) {
        return [];
    }

    $transfers = [];

    // Stage 1: Upload immutable assets without delete
    foreach ($profile['immutable_dirs'] as $dir) {
        $transfers[] = [
            'source' => $dir.'/',
            'target_suffix' => '/'.$dir.'/',
            'args' => ['-rlzp', '--chmod=Du=rwx,Dgo=rx,Fu=rwX,Fgo=rX', '--checksum', '--delay-updates'],
        ];
    }

    // Stage 2: Publish application files with delete-delay and protected excludes
    $excludes = array_map(fn (string $path): string => "--exclude=/{$path}", [
        ...$profile['protected_paths'],
        ...$profile['immutable_dirs'],
    ]);

    $transfers[] = [
        'source' => '',
        'target_suffix' => '/',
        'args' => ['-rlzp', '--chmod=Du=rwx,Dgo=rx,Fu=rwX,Fgo=rX', '--checksum', '--delay-updates', '--delete-delay', ...$excludes],
    ];

    return $transfers;
}

test('deployment publishes assets before exposing the new manifest', function () {
    $transfers = deploymentAssetTransfers();

    expect($transfers)->toHaveCount(2)
        ->and($transfers[0]['source'])->toBe('public/build/assets/')
        ->and($transfers[0]['target_suffix'])->toEndWith('/public/build/assets/')
        ->and($transfers[0]['args'])->not->toContain('--delete')
        ->and($transfers[0]['args'])->not->toContain('--delete-delay')
        ->and(implode(' ', $transfers[1]['args']))->toContain('--exclude=/public/build/assets')
        ->not->toContain('--delete-excluded');
});

test('deployment consumes the verified artifact without rebuilding frontend dependencies', function () {
    $workflow = Yaml::parseFile(base_path('.github/workflows/deploy.yml'));
    $verify = $workflow['jobs']['verify'];
    $deploy = $workflow['jobs']['deploy'];
    $commands = implode("\n", array_column($deploy['steps'], 'run'));
    $uses = array_column($deploy['steps'], 'uses');

    expect($deploy['needs'])->toContain('verify')
        ->and($commands)->not->toContain('npm ci')->not->toContain('npm run build')
        ->toContain('composer install --no-dev')->toContain('build_artifact.py restore')
        ->and($uses)->toContain('actions/download-artifact@v4')->not->toContain('actions/setup-node@v4')
        ->and(substr_count(implode("\n", array_column($verify['steps'], 'run')), 'npm run build'))->toBe(1);

    $stepNames = array_column($verify['steps'], 'name');
    expect(array_search('Seal Verified Frontend Build', $stepNames))->toBeGreaterThan(array_search('Run Automated Tests (Pest)', $stepNames));

    $download = array_values(array_filter($deploy['steps'], fn (array $step): bool => ($step['uses'] ?? '') === 'actions/download-artifact@v4'))[0];
    expect($download['with']['artifact-ids'])->toBe('${{ needs.verify.outputs.artifact_id }}')
        ->and($download['with'])->not->toHaveKey('run-id');
});

test('dependency caches include lockfiles and runtime and never cache a build', function () {
    $workflow = Yaml::parseFile(base_path('.github/workflows/deploy.yml'));
    foreach ($workflow['jobs'] as $job) {
        foreach ($job['steps'] as $step) {
            if (($step['uses'] ?? '') === 'actions/cache@v4') {
                expect($step['with']['key'])->toContain('runner.os')->toContain('PHP_VERSION')->toContain('composer2')->toContain("hashFiles('composer.lock')")
                    ->and($step['with']['path'])->toBe('${{ steps.composer-path.outputs.path }}');
            }
            if (($step['uses'] ?? '') === 'actions/setup-node@v4') {
                expect($step['with']['cache'])->toBe('npm')
                    ->and($step['with']['cache-dependency-path'])->toContain('package-lock.json')->toContain('.node-cache-runtime');
            }
        }
    }
    $commands = implode("\n", array_column($workflow['jobs']['verify']['steps'], 'run'));
    expect($commands)->toContain('"$NODE_VERSION"')->toContain('npm ci')->toContain('composer install');
});

test('rsync keeps open browser chunks and persistent data across a deployment', function () {
    $rsync = (new ExecutableFinder)->find('rsync');

    if ($rsync === null) {
        test()->markTestSkipped('The rsync integration test runs on the Linux CI runner.');
    }

    $directory = storage_path('framework/testing/deploy-assets-'.bin2hex(random_bytes(8)));
    $source = $directory.'/source';
    $target = $directory.'/target';
    $oldManifest = json_encode(['app' => ['file' => 'assets/app-old.js']], JSON_THROW_ON_ERROR);
    $newManifest = json_encode(['app' => ['file' => 'assets/app-new.js']], JSON_THROW_ON_ERROR);
    $sourceFiles = [
        'public/build/assets/app-new.js' => 'import("./editor-new.js");',
        'public/build/assets/editor-new.js' => 'export default "new editor";',
        'public/build/manifest.json' => $newManifest,
        'app.txt' => 'new application',
    ];
    $persistentFiles = [
        '.env' => 'production environment',
        'database/database.sqlite' => 'production database',
        'database/database.sqlite-wal' => 'database journal',
        'storage/app/public/image.webp' => 'uploaded image',
        'docs/private.txt' => 'existing server documentation',
    ];
    $targetFiles = [
        ...$persistentFiles,
        'public/build/assets/app-old.js' => 'import("./editor-old.js");',
        'public/build/assets/editor-old.js' => 'export default "old editor";',
        'public/build/manifest.json' => $oldManifest,
        'app.txt' => 'old application',
        'obsolete.txt' => 'removed application file',
    ];

    try {
        foreach ([$source => $sourceFiles, $target => $targetFiles] as $root => $files) {
            foreach ($files as $path => $content) {
                File::ensureDirectoryExists(dirname($root.'/'.$path));
                File::put($root.'/'.$path, $content);
            }
        }

        foreach (deploymentAssetTransfers() as $index => $transfer) {
            $destination = rtrim($target, '/').$transfer['target_suffix'];
            $process = new Process([
                $rsync,
                ...$transfer['args'],
                $source.'/'.$transfer['source'],
                rtrim($destination, '/').'/',
            ]);
            $process->mustRun();

            expect(File::get($target.'/public/build/assets/app-new.js'))->toBe($sourceFiles['public/build/assets/app-new.js'])
                ->and(File::get($target.'/public/build/assets/editor-new.js'))->toBe($sourceFiles['public/build/assets/editor-new.js'])
                ->and(File::get($target.'/public/build/assets/app-old.js'))->toBe($targetFiles['public/build/assets/app-old.js'])
                ->and(File::get($target.'/public/build/assets/editor-old.js'))->toBe($targetFiles['public/build/assets/editor-old.js'])
                ->and(File::get($target.'/public/build/manifest.json'))->toBe($index === 0 ? $oldManifest : $newManifest)
                ->and(File::get($target.'/app.txt'))->toBe($index === 0 ? 'old application' : 'new application');

            foreach ($persistentFiles as $path => $content) {
                expect(File::get($target.'/'.$path))->toBe($content);
            }
        }

        expect(File::exists($target.'/obsolete.txt'))->toBeFalse();
    } finally {
        File::deleteDirectory($directory);
    }
});
