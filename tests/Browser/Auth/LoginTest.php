<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

test('login page renders Indonesian labels', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitFor('input[name="email"]')
            ->assertSee('Alamat email')
            ->assertSee('Kata sandi')
            ->assertSee('Masuk')
            ->assertSee('Ingat saya');
    });
});

test('valid credentials log in without a remember cookie when unchecked', function () {
    $user = User::factory()->create();
    $rememberCookie = Auth::guard()->getRecallerName();

    $this->browse(function (Browser $browser) use ($user, $rememberCookie) {
        $browser->visit('/login')
            ->waitFor('input[name="email"]')
            ->type('input[name="email"]', $user->email)
            ->type('input[name="password"]', 'password')
            ->assertNotChecked('remember')
            ->press('[data-test="login-button"]')
            ->waitForLocation('/admin')
            ->assertPathIs('/admin')
            ->assertAuthenticatedAs($user)
            ->assertCookieMissing($rememberCookie);
    });
});

test('invalid credentials show a field error', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->waitFor('input[name="email"]')
            ->type('input[name="email"]', $user->email)
            ->type('input[name="password"]', 'wrong-password')
            ->press('[data-test="login-button"]')
            ->waitForText('These credentials do not match our records.')
            ->assertSee('These credentials do not match our records.');
    });
});

test('password reset link opens the reset request form', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->waitFor('input[name="email"]')
            ->assertSee('Lupa kata sandi?')
            ->clickLink('Lupa kata sandi?')
            ->waitForLocation('/forgot-password')
            ->assertPathIs('/forgot-password');
    });
});

test('checked remember choice persists a remember cookie', function () {
    $user = User::factory()->create();
    $rememberCookie = Auth::guard()->getRecallerName();

    $this->browse(function (Browser $browser) use ($user, $rememberCookie) {
        $browser->visit('/login')
            ->waitFor('input[name="email"]')
            ->type('input[name="email"]', $user->email)
            ->type('input[name="password"]', 'password')
            ->click('label[for="remember"]')
            ->assertChecked('remember')
            ->press('[data-test="login-button"]')
            ->waitForLocation('/admin')
            ->assertPathIs('/admin')
            ->assertAuthenticatedAs($user)
            ->assertHasCookie($rememberCookie);
    });
});
