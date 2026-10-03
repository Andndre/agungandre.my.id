<?php

use App\Models\Post;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

test('only the configured owner can preview an article', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    $data = ['content' => 'Draft text'];

    postJson(route('admin.posts.preview'), $data)->assertUnauthorized();
    actingAs(User::factory()->create(['email' => 'other@example.com']))
        ->postJson(route('admin.posts.preview'), $data)->assertForbidden();
    actingAs(User::factory()->create(['email' => 'owner@example.com']))
        ->postJson(route('admin.posts.preview'), $data)->assertSuccessful();

    config()->set('blog.owner_email', '');
    postJson(route('admin.posts.preview'), $data)->assertForbidden();
});

test('article preview shares sanitized markdown rendering without saving content', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    actingAs(User::factory()->create(['email' => 'owner@example.com']));
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
    actingAs(User::factory()->create(['email' => 'owner@example.com']));

    postJson(route('admin.posts.preview'), ['content' => $content])
        ->assertUnprocessable()->assertJsonValidationErrors('content');
    assertDatabaseCount('posts', 0);
})->with([
    'empty' => [''],
    'not text' => [[]],
    'HTTP embed' => ["```embed\nhttp://example.com/video\n```"],
    'multiple URLs' => ["```embed\nhttps://example.com/one\nhttps://example.com/two\n```"],
]);
