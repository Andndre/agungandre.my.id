<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PortfolioQaEnvironment
{
    public static function boot(bool $http = false): Application
    {
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'dusk';
        putenv('APP_ENV=dusk');

        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->make($http ? HttpKernel::class : ConsoleKernel::class)->bootstrap();
        self::assertSafe($app);

        return $app;
    }

    public static function assertSafe(Application $app): void
    {
        $expected = $app->storagePath('framework/testing/dusk.sqlite');
        $configured = str_replace('\\', '/', (string) $app['config']->get('database.connections.sqlite.database'));
        if ($configured === 'storage/framework/testing/dusk.sqlite') {
            $configured = $expected;
        }
        $normalize = static fn (string $path): string => PHP_OS_FAMILY === 'Windows'
            ? strtolower(str_replace('\\', '/', $path)) : str_replace('\\', '/', $path);

        if ($app->configurationIsCached()
            || ! $app->environment('dusk')
            || $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.url')
            || $normalize($configured) !== $normalize($expected)
            || is_link($expected)
            || is_link($app->publicPath('.qa'))) {
            throw new RuntimeException('QA fixtures require the uncached dusk environment, its isolated SQLite database, and a local public/.qa directory.');
        }

        $app['files']->ensureDirectoryExists(dirname($expected));
        if (! is_file($expected)) {
            $app['files']->put($expected, '');
        }
        $app['config']->set('database.connections.sqlite.database', $expected);
    }

    public static function useQaMedia(Application $app): void
    {
        self::assertSafe($app);
        $app['config']->set('filesystems.disks.public.root', $app->publicPath('.qa'));
        $app['config']->set('filesystems.disks.public.url', '/.qa');
        $app['config']->set('blog.media_disk', 'public');
        Storage::forgetDisk('public');
    }
}
