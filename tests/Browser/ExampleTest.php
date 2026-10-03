<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

test('portfolio opens with the public navigation', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->waitForText("I'm Andre.")
            ->assertSee("I'm Andre.")
            ->assertSee('Work')
            ->assertSee('Get in touch');
    });
});
