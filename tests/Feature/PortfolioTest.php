<?php

use App\Models\Post;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

test('portfolio exposes published projects in their configured order without storage paths', function () {
    Project::factory()->unpublished()->create(['sort_order' => 0, 'is_featured' => true]);
    $older = Project::factory()->create(['is_published' => true, 'sort_order' => 2, 'created_at' => now()->subDays(2)]);
    $newer = Project::factory()->create(['is_published' => true, 'sort_order' => 2, 'created_at' => now()->subDay()]);
    $first = Project::factory()->featured()->create([
        'sort_order' => 1,
        'cover_image' => 'projects/covers/first.webp',
        'images' => ['projects/gallery/first.webp'],
        'tech_stack' => ['Laravel', 'Svelte'],
    ]);

    get(route('home'))->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->component('Welcome')
        ->has('projects', 3)
        ->where('projects.0.id', $first->id)
        ->where('projects.1.id', $newer->id)
        ->where('projects.2.id', $older->id)
        ->where('projects.0.cover_image_url', Storage::disk('public')->url($first->cover_image))
        ->where('projects.0.tech_stack', ['Laravel', 'Svelte'])
        ->where('projects.0.is_featured', true)
        ->missing('projects.0.cover_image')
        ->missing('projects.0.images')
        ->missing('projects.0.is_published')
        ->etc());
});

test('portfolio shows only the latest three published articles', function () {
    Post::factory()->create(['published_at' => null]);
    Post::factory()->create(['published_at' => now()->addDay()]);
    Post::factory()->create(['published_at' => now()->subDays(5)]);
    $third = Post::factory()->create(['published_at' => now()->subDays(3)]);
    $second = Post::factory()->create(['published_at' => now()->subDays(2)]);
    $first = Post::factory()->create(['published_at' => now()->subDay()]);

    get(route('home'))->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->has('latestPosts', 3)
        ->where('latestPosts.0.id', $first->id)
        ->where('latestPosts.1.id', $second->id)
        ->where('latestPosts.2.id', $third->id)
        ->has('latestPosts.0.excerpt')
        ->has('latestPosts.0.reading_time')
        ->missing('latestPosts.0.content')
        ->etc());
});

test('only the initial welcome response preloads its featured hero cover with an ordered fallback', function () {
     /** @var Tests\TestCase $this */
    $this->withoutVite();
    Project::factory()->featured()->unpublished()->create(['sort_order' => 0]);
    $first = Project::factory()->create(['is_published' => true, 'is_featured' => false, 'sort_order' => 1, 'cover_image' => 'projects/covers/first.png']);
    $featured = Project::factory()->featured()->create(['sort_order' => 2, 'cover_image' => 'projects/covers/featured.png']);
    $laterFeatured = Project::factory()->featured()->create(['sort_order' => 3, 'cover_image' => 'projects/covers/later-featured.png']);
    $preload = fn (Project $project): string => '<link rel="preload" as="image" href="'.e(Storage::disk('public')->url($project->cover_image)).'" fetchpriority="high">';

    get(route('home'))->assertSuccessful()
        ->assertSee($preload($featured), false)
        ->assertDontSee($preload($first), false)
        ->assertDontSee($preload($laterFeatured), false);
    get(route('projects.show', $featured->slug))->assertSuccessful()->assertDontSee('as="image"', false);
    get(route('home'), ['X-Inertia' => 'true', 'X-Inertia-Version' => Inertia\Inertia::getVersion()])
        ->assertSuccessful()->assertDontSee('<link rel="preload"', false);

    $featured->update(['is_featured' => false]);
    $laterFeatured->update(['is_featured' => false]);
    get(route('home'))->assertSuccessful()->assertSee($preload($first), false);

    $first->update(['cover_image' => null]);
    get(route('home'))->assertSuccessful()->assertDontSee('as="image"', false);
});

test('portfolio supports an empty collection and projects without media or technologies', function () {
    get(route('home'))->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->has('projects', 0)->has('latestPosts', 0)->etc());

    $project = Project::factory()->create(['is_published' => true, 'cover_image' => null, 'images' => null, 'tech_stack' => null]);

    get(route('projects.show', $project->slug))->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->component('projects/Show', false)
        ->where('project.cover_image_url', null)
        ->where('project.tech_stack', [])
        ->where('project.gallery_urls', [])
        ->etc());
});

test('public project detail uses the slug and returns media URLs with plain text description', function () {
    $project = Project::factory()->create([
        'is_published' => true,
        'slug' => 'case-study',
        'description' => "Problem and contribution.\n\n<script>alert('unsafe')</script>",
        'cover_image' => 'projects/covers/detail.webp',
        'images' => ['projects/gallery/one.webp', 'projects/gallery/two.webp'],
    ]);

    get(route('projects.show', $project->slug))->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->component('projects/Show', false)
        ->where('project.slug', $project->slug)
        ->where('project.description', $project->description)
        ->where('project.cover_image_url', Storage::disk('public')->url($project->cover_image))
        ->where('project.gallery_urls', array_map(fn (string $path): string => Storage::disk('public')->url($path), $project->images))
        ->missing('project.cover_image')->missing('project.images')->missing('contentHtml')
        ->etc());

    get(route('projects.show', $project->id))->assertNotFound();
});

test('draft and missing project detail pages are unavailable', function () {
    $project = Project::factory()->unpublished()->create();

    get(route('projects.show', $project->slug))->assertNotFound();
    get(route('projects.show', 'missing-project'))->assertNotFound();
});
