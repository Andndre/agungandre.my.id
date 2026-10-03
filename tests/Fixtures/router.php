<?php

use Illuminate\Http\Request;
use Tests\Fixtures\PortfolioQaEnvironment;

if (PHP_SAPI !== 'cli-server') {
    throw new RuntimeException('The QA router is only for the PHP development server.');
}

define('LARAVEL_START', microtime(true));
$root = dirname(__DIR__, 2);
$public = realpath($root.'/public');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$file = realpath($public.'/'.ltrim($path, '/'));
if ($file && is_file($file)
    && str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $public).'/')
    && strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
    return false;
}

require $root.'/vendor/autoload.php';
$app = PortfolioQaEnvironment::boot(http: true);
PortfolioQaEnvironment::useQaMedia($app);
$app->handleRequest(Request::capture());
