<?php

use Illuminate\Support\Facades\Artisan;
use Tests\Fixtures\PortfolioQaEnvironment;
use Tests\Fixtures\PortfolioQaSeeder;

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('The QA fixture command is CLI-only.');
}

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = PortfolioQaEnvironment::boot();
if (in_array('--check', $argv, true)) {
    echo 'QA isolation verified: '.$app['config']->get('database.connections.sqlite.database').PHP_EOL;
    exit(0);
}

Artisan::call('migrate', ['--force' => true]);
$app->make(PortfolioQaSeeder::class)->run();
echo 'Seeded isolated QA fixtures: 3 published projects, 1 draft project, 1 published article, 1 scheduled article, and a test admin.'.PHP_EOL;
