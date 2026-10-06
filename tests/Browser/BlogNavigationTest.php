<?php

use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;

uses(DatabaseMigrations::class);

test('blog articles and mobile navigation remain usable without JavaScript', function () {
    $post = Post::factory()->create([
        'title' => 'An article without JavaScript',
        'content' => "## Readable heading\n\nThe full article is available.\n\n    echo 'Hello';\n",
    ]);

    $this->browse(function (Browser $browser) use ($post): void {
        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
            'cmd' => 'Emulation.setScriptExecutionDisabled',
            'params' => ['value' => true],
        ]);

        try {
            $browser->resize(390, 900)->visit('/blog')
                ->assertSee($post->title)
                ->assertMissing('#blog-controls')
                ->clickLink($post->title)
                ->assertPathIs('/blog/'.$post->slug)
                ->assertSeeIn('.studio-prose', 'The full article is available.')
                ->assertPresent('.studio-prose pre code')
                ->assertMissing('#app')
                ->click('#blog-navigation-fallback summary')
                ->assertVisible('#blog-navigation-fallback nav')
                ->click('#blog-navigation-fallback a[href$="/blog"]')
                ->assertPathIs('/blog')
                ->assertSee($post->title);
        } finally {
            $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
                'cmd' => 'Emulation.setScriptExecutionDisabled',
                'params' => ['value' => false],
            ]);
        }
    });
});

test('blog document navigation preserves appearance and pagination', function () {
    $post = Post::factory()->create(['title' => 'The latest browser article', 'published_at' => now()->subDay()]);
    Post::factory()->count(10)->create(['published_at' => now()->subDays(2)]);

    $this->browse(function (Browser $browser) use ($post): void {
        $browser->resize(1440, 900)->visit('/')
            ->waitForText("I'm Andre.")
            ->click('nav[aria-label="Main navigation"] a[href$="/blog"]')
            ->waitForText('Writing & experiments.')
            ->waitUntil('document.querySelector("#blog-controls").hidden === false')
            ->click('#blog-controls button[aria-label="Dark theme"]')
            ->assertScript('document.documentElement.classList.contains("dark")', true)
            ->clickLink($post->title)
            ->waitForText($post->title)
            ->assertPathIs('/blog/'.$post->slug)
            ->assertScript('document.documentElement.classList.contains("dark")', true)
            ->clickLink('All writing')
            ->waitForText('Writing & experiments.')
            ->clickLink('Next')
            ->waitFor('nav[aria-label="Writing pagination"]')
            ->assertQueryStringHas('page', '2')
            ->assertSee('Previous')
            ->assertDontSee($post->title)
            ->back()
            ->waitForText($post->title)
            ->assertQueryStringMissing('page')
            ->click('a.wordmark')
            ->waitForText("I'm Andre.")
            ->assertScript('document.documentElement.classList.contains("dark")', true)
            ->click('#writing h3 a')
            ->waitForText($post->title)
            ->assertPathIs('/blog/'.$post->slug)
            ->assertMissing('#app');
    });
});

test('mobile blog navigation supports Escape focus return and cross-page links', function () {
    Post::factory()->create();

    $this->browse(function (Browser $browser): void {
        $browser->resize(390, 900)->visit('/blog')
            ->waitUntil('document.querySelector("#blog-controls").hidden === false')
            ->click('#blog-controls button[aria-label="Open navigation"]')
            ->waitFor('[role="dialog"]')
            ->assertSeeIn('[role="dialog"]', 'Explore')
            ->keys('[role="dialog"]', '{escape}')
            ->waitUntilMissing('[role="dialog"]')
            ->assertFocused('#blog-controls button[aria-label="Open navigation"]')
            ->click('#blog-controls button[aria-label="Open navigation"]')
            ->waitFor('[role="dialog"]')
            ->click('[role="dialog"] a[href$="#work"]')
            ->waitForText("I'm Andre.")
            ->assertPathIs('/')
            ->click('button[aria-label="Open navigation"]')
            ->waitFor('[role="dialog"]')
            ->click('[role="dialog"] a[href$="/blog"]')
            ->waitForText('Writing & experiments.')
            ->assertPathIs('/blog')
            ->waitUntil('document.querySelector("#blog-controls").hidden === false')
            ->assertMissing('#app');
    });
});
