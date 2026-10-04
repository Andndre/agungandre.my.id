<?php

use App\Support\WebImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('media:check-image-runtime', function (WebImageOptimizer $images): int {
    try {
        $images->checkRuntime();
        $image = imagecreatetruecolor(2, 2);
        $decoded = @imagecreatefromstring($images->encode($image));

        if ($decoded === false) {
            throw new RuntimeException('PHP GD cannot decode encoded WebP images.');
        }

        $this->info('Image runtime ready: JPEG, PNG, WebP, and EXIF.');

        return Command::SUCCESS;
    } catch (Throwable $exception) {
        report($exception);
        $this->error($exception->getMessage());

        return Command::FAILURE;
    } finally {
        unset($image, $decoded);
    }
})->purpose('Verify PHP image optimization support with a WebP encode/decode check');
