<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected to the login page', function () {
    $response = get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->createOne();
    actingAs($user);

    $response = get(route('admin.dashboard'));
    $response->assertOk();
});
