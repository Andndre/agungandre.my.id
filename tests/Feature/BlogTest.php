<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\put;

test('published blog index and article are available', function () {
    $post = Post::factory()->create(['title' => 'A first devlog']);

    get('/blog')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('blog/Index')
        ->has('posts.data', 1));
    get('/blog/'.$post->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('blog/Show')
        ->where('post.slug', $post->slug)
        ->etc());
});

test('draft, future and missing posts are hidden', function () {
    $draft = Post::factory()->create(['published_at' => null]);
    $future = Post::factory()->create(['published_at' => now()->addDay()]);

    get('/blog')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('blog/Index')
        ->has('posts.data', 0));
    get('/blog/'.$draft->slug)->assertNotFound();
    get('/blog/'.$future->slug)->assertNotFound();
    get('/blog/does-not-exist')->assertNotFound();
});

test('blog index paginates ten posts newest first', function () {
    Post::factory()->count(10)->create(['published_at' => now()->subDays(2)]);
    $newest = Post::factory()->create(['title' => 'Newest post', 'published_at' => now()->subDay()]);

    get('/blog')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('blog/Index')
        ->has('posts.data', 10)
        ->where('posts.total', 11)
        ->where('posts.data.0.slug', $newest->slug)
        ->etc());
});

test('slug is unique and stable while reading time follows content', function () {
    $first = Post::factory()->create(['title' => 'Same Title', 'content' => str_repeat('word ', 201)]);
    $second = Post::factory()->create(['title' => 'Same Title']);

    expect($first->slug)->toBe('same-title')
        ->and($second->slug)->toBe('same-title-2')
        ->and($first->reading_time)->toBe(2);

    $first->update(['title' => 'Changed Title', 'content' => 'Short text']);
    expect($first->fresh()->slug)->toBe('same-title')
        ->and($first->fresh()->reading_time)->toBe(1);
});

test('markdown HTML and unsafe links are removed while custom blocks render safely', function () {
    $post = Post::factory()->create([
        'content' => "<script>alert(1)</script>\n\n[bad](javascript:alert(1))\n\n```callout:info\n**Safe** note\n```\n\n```embed\nhttps://youtu.be/dQw4w9WgXcQ\n```",
    ]);

    get('/blog/'.$post->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('contentHtml', fn (string $html) => ! str_contains($html, '<script>')
            && ! str_contains($html, 'href="javascript:')
            && str_contains($html, 'blog-callout-info')
            && str_contains($html, 'youtube-nocookie.com/embed/'))
        ->etc());
});

test('only the configured owner can manage posts', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    $other = User::factory()->createOne(['email' => 'other@example.com']);
    $owner = User::factory()->createOne(['email' => 'owner@example.com']);

    get(route('admin.posts.index'))->assertRedirect(route('login'));
    actingAs($other)->get(route('admin.posts.index'))->assertForbidden();
    actingAs($owner)->get(route('admin.posts.index'))->assertOk();

    config()->set('blog.owner_email', '');
    get(route('admin.posts.index'))->assertForbidden();
});

test('owner can create, schedule, update, unpublish and delete a post', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    actingAs(User::factory()->createOne(['email' => 'owner@example.com']));
    $data = ['title' => 'Release Notes', 'excerpt' => 'What changed', 'content' => 'One useful update', 'publication' => 'draft'];

    $response = post(route('admin.posts.store'), $data);
    $post = Post::firstOrFail();
    $response->assertRedirect(route('admin.posts.edit', $post));
    expect($post->published_at)->toBeNull();

    put(route('admin.posts.update', $post), [...$data, 'publication' => 'scheduled', 'published_at' => now()->addDay()->toIso8601String()])
        ->assertRedirect(route('admin.posts.edit', $post));
    expect($post->fresh()->published_at->isFuture())->toBeTrue();

    put(route('admin.posts.update', $post), [...$data, 'publication' => 'now'])
        ->assertRedirect(route('admin.posts.edit', $post));
    expect($post->fresh()->published_at->isPast())->toBeTrue();

    put(route('admin.posts.update', $post), $data)->assertRedirect(route('admin.posts.edit', $post));
    expect($post->fresh()->published_at)->toBeNull();

    delete(route('admin.posts.destroy', $post))->assertRedirect(route('admin.posts.index'));
    assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('creating and updating an article preserves formatted multiline callout markdown', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    actingAs(User::factory()->createOne(['email' => 'owner@example.com']));
    $content = "Before the note.\n\n```callout:info\n**Useful** note\n\n- First item\n- Second item\n\n[Read more](https://example.com/guide)\n```\n\nAfter the note.";
    $data = ['title' => 'Callout article', 'excerpt' => 'A formatted note', 'content' => $content, 'publication' => 'draft'];

    $response = post(route('admin.posts.store'), $data);
    $post = Post::firstOrFail();
    $response->assertRedirect(route('admin.posts.edit', $post));
    expect($post->content)->toBe($content);
    get(route('admin.posts.edit', $post))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Posts/Edit')
        ->where('post.content', $content));

    $updatedContent = str_replace('callout:info', 'callout:warning', $content);
    put(route('admin.posts.update', $post), [...$data, 'content' => $updatedContent, 'publication' => 'now'])
        ->assertRedirect(route('admin.posts.edit', $post));
    expect($post->fresh()->content)->toBe($updatedContent);
    get(route('admin.posts.edit', $post))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Posts/Edit')
        ->where('post.content', $updatedContent));
    get('/blog/'.$post->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('contentHtml', fn (string $html) => str_contains($html, 'blog-callout-warning')
            && str_contains($html, '<strong>Useful</strong>')
            && str_contains($html, '<li>First item</li>')
            && str_contains($html, '<li>Second item</li>')
            && str_contains($html, 'href="https://example.com/guide"')
            && str_contains($html, 'After the note.'))
        ->etc());
});

test('invalid embed URL is rejected when saving', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    actingAs(User::factory()->createOne(['email' => 'owner@example.com']));

    post(route('admin.posts.store'), [
        'title' => 'Unsafe embed',
        'excerpt' => 'Rejected content',
        'content' => "```embed\nhttp://example.com/video\n```",
        'publication' => 'draft',
    ])->assertSessionHasErrors('content');

    assertDatabaseCount('posts', 0);
});

test('owner can upload a validated article image', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    config()->set('blog.media_disk', 's3');
    Storage::fake('s3');
    actingAs(User::factory()->createOne(['email' => 'owner@example.com']));

    postJson(route('admin.posts.images.store'), ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertCreated()
        ->assertJsonStructure(['url']);

    expect(Storage::disk('s3')->allFiles('blog/images'))->toHaveCount(1);

    $filename = basename(Storage::disk('s3')->allFiles('blog/images')[0]);
    get(route('blog.media.show', $filename))->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
    get(route('blog.media.show', 'missing.jpg'))->assertNotFound();
    get('/media/blog/images/../missing.jpg')->assertNotFound();

    postJson(route('admin.posts.images.store'), ['image' => UploadedFile::fake()->create('vector.svg', 1, 'image/svg+xml')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});
