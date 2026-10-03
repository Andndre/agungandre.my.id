<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use RuntimeException;

abstract class DuskTestCase extends BaseTestCase
{
    private const DATABASE = 'storage/framework/testing/dusk.sqlite';

    protected function setUp(): void
    {
        parent::setUp();

        foreach (static::$browsers as $browser) {
            $browser->driver->manage()->deleteAllCookies();
        }
    }

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $database = str_replace('\\', '/', (string) $app['config']->get('database.connections.sqlite.database'));
        $expected = $app->basePath(self::DATABASE);
        $configured = $database === self::DATABASE ? $expected : $database;
        $normalize = static fn (string $path): string => PHP_OS_FAMILY === 'Windows'
            ? strtolower(str_replace('\\', '/', $path))
            : str_replace('\\', '/', $path);

        if ($app->configurationIsCached()
            || ! $app->environment('dusk')
            || $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.url')
            || $normalize($configured) !== $normalize($expected)
            || is_link($expected)) {
            throw new RuntimeException('Dusk requires an uncached dusk environment and the isolated storage/framework/testing/dusk.sqlite database.');
        }

        $app['files']->ensureDirectoryExists(dirname($expected));
        if (! is_file($expected)) {
            $app['files']->put($expected, '');
        }

        $app['config']->set('database.connections.sqlite.database', $expected);

        return $app;
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail() && ! static::environmentVariable('DUSK_DRIVER_URL')) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--no-sandbox',
            '--disable-dev-shm-usage',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        if ($binary = $this->chromeBinary()) {
            $options->setBinary($binary);
        }

        return RemoteWebDriver::create(
            static::environmentVariable('DUSK_DRIVER_URL') ?: 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    private function chromeBinary(): ?string
    {
        if ($override = static::environmentVariable('DUSK_CHROME_BINARY')) {
            if (! static::environmentVariable('DUSK_DRIVER_URL') && ! is_file($override)) {
                throw new RuntimeException('DUSK_CHROME_BINARY must point to an existing Chrome or Chromium executable.');
            }

            return $override;
        }

        if (static::environmentVariable('DUSK_DRIVER_URL')) {
            return null;
        }

        $candidates = match (PHP_OS_FAMILY) {
            'Windows' => [
                static::environmentVariable('ProgramFiles').'/Google/Chrome/Application/chrome.exe',
                static::environmentVariable('ProgramFiles(x86)').'/Google/Chrome/Application/chrome.exe',
                static::environmentVariable('LOCALAPPDATA').'/Google/Chrome/Application/chrome.exe',
                static::environmentVariable('LOCALAPPDATA').'/Chromium/Application/chrome.exe',
            ],
            'Darwin' => ['/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', '/Applications/Chromium.app/Contents/MacOS/Chromium'],
            default => ['/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium', '/usr/bin/chromium-browser', '/snap/bin/chromium'],
        };

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function environmentVariable(string $name): string
    {
        return (string) ($_ENV[$name] ?? $_SERVER[$name] ?? getenv($name));
    }
}
