<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/** @return array<int, array{env: array<string, string>}> */
function deploymentAssetTransfers(): array
{
    $workflow = Yaml::parseFile(base_path('.github/workflows/deploy.yml'));

    return array_values(array_filter(
        $workflow['jobs']['deploy']['steps'],
        fn (array $step): bool => str_starts_with($step['uses'] ?? '', 'easingthemes/ssh-deploy@'),
    ));
}

test('deployment publishes assets before exposing the new manifest', function () {
    $transfers = deploymentAssetTransfers();

    expect($transfers)->toHaveCount(2)
        ->and($transfers[0]['env']['SOURCE'])->toBe('public/build/assets/')
        ->and($transfers[0]['env']['TARGET'])->toEndWith('/public/build/assets/')
        ->and($transfers[0]['env']['ARGS'])->not->toContain('--delete')
        ->and($transfers[1]['env']['ARGS'])->toContain('--exclude=/public/build/assets/')
        ->not->toContain('--delete-excluded');
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
            $environment = $transfer['env'];
            $destination = str_replace('${{ secrets.HOSTINGER_TARGET_DIR }}', $target, $environment['TARGET']);
            $process = new Process([
                $rsync,
                ...preg_split('/\s+/', trim($environment['ARGS'])),
                $source.'/'.($environment['SOURCE'] ?? ''),
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
