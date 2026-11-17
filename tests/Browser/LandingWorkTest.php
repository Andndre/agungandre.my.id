<?php

use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

test('desktop scrolling moves multiple projects horizontally on a short viewport', function () {
    Project::factory()->count(3)->create(['is_published' => true]);

    $this->browse(function (Browser $browser): void {
        $browser->resize(1440, 800)->visit('/')
            ->waitFor('.work-horizontal')
            ->assertScript('window.innerHeight < 900', true)
            ->script('window.scrollTo(0, document.querySelector("#work").offsetTop + 300)');

        $browser->waitUntil('document.querySelector(".work-track").scrollLeft > 0')
            ->assertScript('getComputedStyle(document.querySelector(".work-layout")).position', 'sticky');
    });
});

test('one project appears beside its heading without extra pinned scrolling', function () {
    Project::factory()->create(['is_published' => true]);

    $this->browse(function (Browser $browser): void {
        $browser->resize(1440, 800)->visit('/')
            ->waitFor('.work-single')
            ->assertMissing('.work-horizontal')
            ->assertScript('document.querySelector(".work-track").getBoundingClientRect().left > document.querySelector(".work-intro").getBoundingClientRect().right', true);
    });
});

test('mobile and reduced motion keep projects in a vertical list', function () {
    Project::factory()->count(2)->create(['is_published' => true]);

    $this->browse(function (Browser $browser): void {
        $browser->resize(390, 800)->visit('/')
            ->waitFor('.work-card')
            ->assertMissing('.work-horizontal')
            ->assertScript('getComputedStyle(document.querySelector(".work-track")).display', 'grid');

        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
            'cmd' => 'Emulation.setEmulatedMedia',
            'params' => ['features' => [['name' => 'prefers-reduced-motion', 'value' => 'reduce']]],
        ]);

        try {
            $browser->resize(1440, 800)->refresh()
                ->waitFor('.work-card')
                ->assertMissing('.work-horizontal')
                ->assertScript('getComputedStyle(document.querySelector(".work-track")).display', 'grid');
        } finally {
            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                'cmd' => 'Emulation.setEmulatedMedia',
                'params' => ['features' => []],
            ]);
        }
    });
});
