<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_can_render_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Email address')
                ->assertSee('Password')
                ->assertSee('Log in');
        });
    }

    public function test_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                ->type('input[name="email"]', $user->email)
                ->type('input[name="password"]', 'password')
                ->press('[data-test="login-button"]')
                ->pause(5000)
                ->assertUrlIs('**/admin');
        });
    }

    public function test_shows_error_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                ->type('input[name="email"]', $user->email)
                ->type('input[name="password"]', 'wrong-password')
                ->press('[data-test="login-button"]')
                ->waitForText('These credentials do not match our records.')
                ->assertSee('These credentials do not match our records.');
        });
    }

    public function test_can_see_forgot_password_link(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Forgot password?');
        });
    }

    public function test_can_login_with_remember_me_checked(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                ->type('input[name="email"]', $user->email)
                ->type('input[name="password"]', 'password')
                ->click('label[for="remember"]')
                ->press('[data-test="login-button"]')
                ->pause(5000)
                ->assertUrlIs('**/admin');
        });
    }
}
