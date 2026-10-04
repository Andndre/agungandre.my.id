<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

beforeEach(function () {
    config()->set('filesystems.project_media_disk', 's3');
    Storage::fake('s3');
});

function validProjectData(array $overrides = []): array
{
    return [
        'title' => 'A useful project',
        'description' => "Problem.\n\nContribution and results.",
        'slug' => 'a-useful-project',
        'tech_stack' => ['Laravel', 'Svelte'],
        'live_url' => 'https://example.com',
        'repo_url' => 'https://github.com/example/project',
        'sort_order' => 3,
        'is_featured' => '1',
        'is_published' => '1',
        ...$overrides,
    ];
}

test('project management retains authenticated access and ID binding', function () {
    $project = Project::factory()->create(['slug' => 'project-with-slug']);

    foreach (['admin.projects.index', 'admin.projects.create'] as $route) {
        get(route($route))->assertRedirect(route('login'));
    }
    get(route('admin.projects.edit', $project))->assertRedirect(route('login'));
    post(route('admin.projects.store'), [])->assertRedirect(route('login'));
    put(route('admin.projects.update', $project), [])->assertRedirect(route('login'));
    delete(route('admin.projects.destroy', $project))->assertRedirect(route('login'));

    config()->set('blog.owner_email', 'different-owner@example.com');
    actingAs(User::factory()->createOne())->get(route('admin.projects.create'))->assertSuccessful();
    get(route('admin.projects.edit', $project->slug))->assertNotFound();
});

test('admin project list and edit receive raw paths and resolved media URLs including drafts', function () {
    actingAs(User::factory()->createOne());
    $project = Project::factory()->unpublished()->create([
        'sort_order' => 0,
        'cover_image' => 'projects/covers/existing.webp',
        'images' => ['projects/gallery/existing.webp'],
        'tech_stack' => null,
    ]);
    Project::factory()->create(['sort_order' => 9]);

    get(route('admin.projects.index'))->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Projects/Index')
        ->has('projects', 2)
        ->where('projects.0.id', $project->id)
        ->where('projects.0.is_published', false)
        ->where('projects.0.cover_image', $project->cover_image)
        ->where('projects.0.cover_image_url', Storage::disk('s3')->url($project->cover_image))
        ->where('projects.0.tech_stack', [])
        ->has('projects.0.slug')->has('projects.0.created_at')
        ->etc());

    get(route('admin.projects.edit', $project))->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Projects/Edit')
        ->where('project.id', $project->id)
        ->where('project.images', $project->images)
        ->where('project.tech_stack', [])
        ->where('project.gallery_urls', [Storage::disk('s3')->url($project->images[0])])
        ->etc());
});

test('authenticated user can create a project with cover and gallery uploads', function () {
    Storage::fake('s3');
    actingAs(User::factory()->createOne());

    post(route('admin.projects.store'), validProjectData([
        'cover_image' => UploadedFile::fake()->image('cover.webp'),
        'images' => [UploadedFile::fake()->image('first.jpg'), UploadedFile::fake()->image('second.png')],
    ]))->assertRedirect(route('admin.projects.index'))->assertSessionHasNoErrors();

    $project = Project::firstOrFail();
    expect($project->is_published)->toBeTrue()
        ->and($project->is_featured)->toBeTrue()
        ->and($project->sort_order)->toBe(3)
        ->and($project->tech_stack)->toBe(['Laravel', 'Svelte'])
        ->and($project->images)->toHaveCount(2);
    Storage::disk('s3')->assertExists([$project->cover_image, ...$project->images]);
});

test('PUT and legacy POST updates can unpublish a project and remove its featured state', function (string $method) {
    Storage::fake('s3');
    actingAs(User::factory()->createOne());
    $project = Project::factory()->featured()->create();

    $response = $method === 'put'
        ? put(route('admin.projects.update', $project), validProjectData(['is_published' => '0', 'is_featured' => '0']))
        : post(route('admin.projects.update', $project), validProjectData(['is_published' => '0', 'is_featured' => '0']));

    $response->assertRedirect(route('admin.projects.index'))->assertSessionHasNoErrors();

    expect($project->fresh()->is_published)->toBeFalse()
        ->and($project->fresh()->is_featured)->toBeFalse();
    get(route('projects.show', $project->fresh()->slug))->assertNotFound();
})->with(['put', 'post']);

test('complete project edits normalize omitted checkbox and technology fields while retaining images', function () {
    Storage::fake('s3');
    actingAs(User::factory()->createOne());
    $project = Project::factory()->featured()->create([
        'cover_image' => 'projects/covers/retained.webp',
        'images' => ['projects/gallery/retained.webp'],
        'tech_stack' => ['Laravel'],
    ]);
    $data = validProjectData();
    unset($data['is_featured'], $data['is_published'], $data['tech_stack']);

    put(route('admin.projects.update', $project), $data)->assertSessionHasNoErrors();

    expect($project->fresh()->is_featured)->toBeFalse()
        ->and($project->fresh()->is_published)->toBeFalse()
        ->and($project->fresh()->tech_stack)->toBe([])
        ->and($project->fresh()->cover_image)->toBe('projects/covers/retained.webp')
        ->and($project->fresh()->images)->toBe(['projects/gallery/retained.webp']);
});

test('multipart method spoofed project edit replaces existing media as a complete gallery', function () {
    Storage::fake('s3');
    actingAs(User::factory()->createOne());
    $project = Project::factory()->featured()->create([
        'cover_image' => 'projects/covers/old.jpg',
        'images' => ['projects/gallery/old-one.jpg', 'projects/gallery/old-two.jpg'],
    ]);
    $oldPaths = [$project->cover_image, ...$project->images];
    foreach ($oldPaths as $path) {
        Storage::disk('s3')->put($path, 'old image');
    }

    post(route('admin.projects.update', $project), validProjectData([
        '_method' => 'put',
        'cover_image' => UploadedFile::fake()->image('new-cover.jpg'),
        'images' => [UploadedFile::fake()->image('new-gallery.jpg')],
    ]))->assertRedirect(route('admin.projects.index'))->assertSessionHasNoErrors();

    $project->refresh();
    expect($project->images)->toHaveCount(1);
    Storage::disk('s3')->assertMissing($oldPaths);
    Storage::disk('s3')->assertExists([$project->cover_image, ...$project->images]);
});

test('project validation rejects missing cover duplicate slug and invalid state without writing files', function () {
    Storage::fake('s3');
    actingAs(User::factory()->createOne());
    Project::factory()->create(['slug' => 'a-useful-project']);

    post(route('admin.projects.store'), validProjectData(['is_featured' => 'invalid']))
        ->assertSessionHasErrors(['cover_image', 'slug', 'is_featured']);

    assertDatabaseCount('projects', 1);
    expect(Storage::disk('s3')->allFiles())->toBe([]);
});

test('project validation rejects unsafe or excessive media and technology values', function (string $scenario, string $error) {
    Storage::fake('s3');
    actingAs(User::factory()->createOne());
    $extra = match ($scenario) {
        'not image' => ['cover_image' => UploadedFile::fake()->create('payload.svg', 1, 'image/svg+xml')],
        'large image' => ['cover_image' => UploadedFile::fake()->image('large.jpg')->size(2049)],
        'many images' => ['images' => array_map(fn (): UploadedFile => UploadedFile::fake()->image('gallery.jpg'), range(1, 11))],
        'many technologies' => ['tech_stack' => array_fill(0, 21, 'Laravel')],
    };

    post(route('admin.projects.store'), validProjectData(['cover_image' => UploadedFile::fake()->image('valid.jpg'), ...$extra]))
        ->assertSessionHasErrors($error);
    assertDatabaseCount('projects', 0);
    expect(Storage::disk('s3')->allFiles())->toBe([]);
})->with([
    'non-raster upload' => ['not image', 'cover_image'],
    'cover over 2 MB' => ['large image', 'cover_image'],
    'gallery over ten files' => ['many images', 'images'],
    'technology list over twenty items' => ['many technologies', 'tech_stack'],
]);

test('project slugs reject characters that cannot form the public route segment', function (string $slug) {
    Storage::fake('s3');
    actingAs(User::factory()->createOne());

    post(route('admin.projects.store'), validProjectData([
        'slug' => $slug,
        'cover_image' => UploadedFile::fake()->image('cover.jpg'),
    ]))->assertSessionHasErrors('slug');

    assertDatabaseCount('projects', 0);
    expect(Storage::disk('s3')->allFiles())->toBe([]);
})->with(['nested/path', 'demo?view=other', 'demo#work', 'Uppercase', 'two words']);

test('project deletion removes stored cover and gallery files', function () {
    Storage::fake('s3');
    actingAs(User::factory()->createOne());
    $project = Project::factory()->create(['cover_image' => 'projects/covers/deleted.jpg', 'images' => ['projects/gallery/deleted.jpg']]);
    $paths = [$project->cover_image, ...$project->images];
    foreach ($paths as $path) {
        Storage::disk('s3')->put($path, 'image');
    }

    delete(route('admin.projects.destroy', $project))->assertRedirect(route('admin.projects.index'));

    assertModelMissing($project);
    Storage::disk('s3')->assertMissing($paths);
});
