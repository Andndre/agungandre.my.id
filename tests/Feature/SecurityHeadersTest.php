<?php

use function Pest\Laravel\get;

test('web responses include modern security headers', function () {
    $response = get('/');

    $response->assertSuccessful();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
});

test('secure requests include hsts header', function () {
    $response = $this->get('https://localhost/');

    $response->assertSuccessful();
    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
