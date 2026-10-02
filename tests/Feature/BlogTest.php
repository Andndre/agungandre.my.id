<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('published blog index and article are available', function () {
    $post = Post::factory()->create(['title' => 'A first devlog']);

    $this->get('/blog')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('blog/Index')
        ->has('posts.data', 1));
    $this->get('/blog/'.$post->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('blog/Show')
        ->where('post.slug', $post->slug)
        ->etc());
});

test('draft, future and missing posts are hidden', function () {
    $draft = Post::factory()->create(['published_at' => null]);
    $future = Post::factory()->create(['published_at' => now()->addDay()]);

    $this->get('/blog')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('blog/Index')
        ->has('posts.data', 0));
    $this->get('/blog/'.$draft->slug)->assertNotFound();
    $this->get('/blog/'.$future->slug)->assertNotFound();
    $this->get('/blog/does-not-exist')->assertNotFound();
});

test('blog index paginates ten posts newest first', function () {
    Post::factory()->count(10)->create(['published_at' => now()->subDays(2)]);
    $newest = Post::factory()->create(['title' => 'Newest post', 'published_at' => now()->subDay()]);

    $this->get('/blog')->assertOk()->assertInertia(fn (Assert $page) => $page
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

    $this->get('/blog/'.$post->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('contentHtml', fn (string $html) => ! str_contains($html, '<script>')
            && ! str_contains($html, 'href="javascript:')
            && str_contains($html, 'blog-callout-info')
            && str_contains($html, 'youtube-nocookie.com/embed/'))
        ->etc());
});

test('only the configured owner can manage posts', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    $other = User::factory()->create(['email' => 'other@example.com']);
    $owner = User::factory()->create(['email' => 'owner@example.com']);

    $this->get(route('admin.posts.index'))->assertRedirect(route('login'));
    $this->actingAs($other)->get(route('admin.posts.index'))->assertForbidden();
    $this->actingAs($owner)->get(route('admin.posts.index'))->assertOk();

    config()->set('blog.owner_email', '');
    $this->get(route('admin.posts.index'))->assertForbidden();
});

test('owner can create, schedule, update, unpublish and delete a post', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));
    $data = ['title' => 'Release Notes', 'excerpt' => 'What changed', 'content' => 'One useful update', 'publication' => 'draft'];

    $this->post(route('admin.posts.store'), $data)->assertRedirect(route('admin.posts.index'));
    $post = Post::firstOrFail();
    expect($post->published_at)->toBeNull();

    $this->put(route('admin.posts.update', $post), [...$data, 'publication' => 'scheduled', 'published_at' => now()->addDay()->toIso8601String()])
        ->assertRedirect(route('admin.posts.index'));
    expect($post->fresh()->published_at->isFuture())->toBeTrue();

    $this->put(route('admin.posts.update', $post), [...$data, 'publication' => 'now'])
        ->assertRedirect(route('admin.posts.index'));
    expect($post->fresh()->published_at->isPast())->toBeTrue();

    $this->put(route('admin.posts.update', $post), $data)->assertRedirect(route('admin.posts.index'));
    expect($post->fresh()->published_at)->toBeNull();

    $this->delete(route('admin.posts.destroy', $post))->assertRedirect(route('admin.posts.index'));
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('invalid embed URL is rejected when saving', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));

    $this->post(route('admin.posts.store'), [
        'title' => 'Unsafe embed',
        'excerpt' => 'Rejected content',
        'content' => "```embed\nhttp://example.com/video\n```",
        'publication' => 'draft',
    ])->assertSessionHasErrors('content');

    $this->assertDatabaseCount('posts', 0);
});

test('owner can upload a validated article image', function () {
    config()->set('blog.owner_email', 'owner@example.com');
    config()->set('blog.media_disk', 's3');
    Storage::fake('s3');
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));

    $this->postJson(route('admin.posts.images.store'), ['image' => UploadedFile::fake()->image('photo.jpg')])
        ->assertCreated()
        ->assertJsonStructure(['url']);

    expect(Storage::disk('s3')->allFiles('blog/images'))->toHaveCount(1);

    $filename = basename(Storage::disk('s3')->allFiles('blog/images')[0]);
    $this->get(route('blog.media.show', $filename))->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
    $this->get(route('blog.media.show', 'missing.jpg'))->assertNotFound();
    $this->get('/media/blog/images/../missing.jpg')->assertNotFound();

    $this->postJson(route('admin.posts.images.store'), ['image' => UploadedFile::fake()->create('vector.svg', 1, 'image/svg+xml')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});
