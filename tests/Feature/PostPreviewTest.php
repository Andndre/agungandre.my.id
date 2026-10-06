<?php

use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

test('only the configured owner can preview an article', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    $data = ['content' => 'Draft text'];

    postJson(route('admin.posts.preview'), $data)->assertUnauthorized();
    actingAs(User::factory()->createOne(['email' => 'other@example.com']))
        ->postJson(route('admin.posts.preview'), $data)->assertForbidden();
    actingAs(User::factory()->createOne(['email' => 'owner@example.com']))
        ->postJson(route('admin.posts.preview'), $data)->assertSuccessful();

    config()->set('blog.owner_email', '');
    postJson(route('admin.posts.preview'), $data)->assertForbidden();
});

test('article preview shares sanitized markdown rendering without saving content', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    actingAs(User::factory()->createOne(['email' => 'owner@example.com']));
    $post = Post::factory()->create(['title' => 'Existing post', 'content' => 'Original content']);
    $original = $post->refresh()->getAttributes();
    $content = "<script>alert(1)</script>\n\n[bad](javascript:alert(1))\n\n```callout:info\n**Useful** note\n```\n\n```embed\nhttps://youtu.be/dQw4w9WgXcQ\n```";

    $html = postJson(route('admin.posts.preview'), ['content' => $content])
        ->assertSuccessful()->assertJsonStructure(['contentHtml'])->json('contentHtml');

    expect($html)->not->toContain('<script>', 'href="javascript:')
        ->toContain('blog-callout-info', '<strong>Useful</strong>', 'youtube-nocookie.com/embed/');
    assertDatabaseCount('posts', 1);
    expect($post->fresh()->getAttributes())->toBe($original);
});

test('article preview rejects empty content and invalid embed URLs', function (mixed $content) {
    config()->set('blog.owner_email', 'owner@example.com');
    actingAs(User::factory()->createOne(['email' => 'owner@example.com']));

    postJson(route('admin.posts.preview'), ['content' => $content])
        ->assertUnprocessable()->assertJsonValidationErrors('content');
    assertDatabaseCount('posts', 0);
})->with([
    'empty' => [''],
    'not text' => [[]],
    'HTTP embed' => ["```embed\nhttp://example.com/video\n```"],
    'multiple URLs' => ["```embed\nhttps://example.com/one\nhttps://example.com/two\n```"],
]);

test('preview and public link cards allow only the trusted application SVG', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    actingAs(User::factory()->createOne(['email' => 'owner@example.com']));
    $content = '<svg onload="alert(1)"><path d="M0 0" /></svg>'."\n\n"
        .'<script>alert(1)</script>'."\n\n"
        .'[Unsafe](javascript:alert(1))'."\n\n"
        .'<img src="x" onerror="alert(1)">'."\n\n"
        ."```embed\nhttps://example.com/article?first=one&second=two\n```";
    $post = Post::factory()->create(['content' => $content]);
    $original = $post->refresh()->getAttributes();

    $html = postJson(route('admin.posts.preview'), ['content' => $content])
        ->assertSuccessful()->json('contentHtml');

    expect($html)->not->toContain('<script', 'onload=', 'onerror=', 'href="javascript:', 'M0 0', '↗')
        ->toContain('href="https://example.com/article?first=one&amp;second=two"', 'rel="noopener noreferrer"', 'target="_blank">example.com ')
        ->toContain('<svg class="blog-link-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg>');
    expect(substr_count($html, '<svg'))->toBe(1);
    assertDatabaseCount('posts', 1);
    expect($post->fresh()->getAttributes())->toBe($original);

    get(route('blog.show', $post->slug))->assertOk()->assertViewIs('blog.show')
        ->assertViewHas('contentHtml', $html)
        ->assertSee($html, false);
});
