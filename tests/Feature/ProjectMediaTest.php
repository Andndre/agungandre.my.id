<?php

use App\Models\Project;
use App\Models\User;
use App\Support\ProjectData;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\get;

test('project media streams S3 cover and gallery images through the local HTTPS media base', function (string $directory) {
    config()->set('filesystems.project_media_disk', 's3');
    Storage::fake('s3');
    Storage::fake('public');
    $image = UploadedFile::fake()->image('sample.png');
    $path = Storage::disk('s3')->putFileAs('projects/'.$directory, $image, 'sample.png');

    get(route('projects.media.show', [$directory, 'sample.png']))
        ->assertSuccessful()
        ->assertHeader('content-type', 'image/png')
        ->assertStreamedContent(file_get_contents($image->getRealPath()));

    Storage::disk('public')->assertMissing($path);
})->with(['covers', 'gallery']);

test('project media rejects missing images unsupported folders and unsafe paths', function (string $path) {
    config()->set('filesystems.project_media_disk', 's3');
    Storage::fake('s3');

    get($path)->assertNotFound();
})->with([
    '/media/projects/covers/missing.png',
    '/media/projects/private/sample.png',
    '/media/projects/covers/sample.svg',
    '/media/projects/covers/../sample.png',
    '/media/projects/covers/nested/sample.png',
]);

test('project URLs use the configured S3 public base instead of local storage', function () {
    config()->set('filesystems.project_media_disk', 's3');
    config()->set('filesystems.disks.s3', [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'test-projects',
        'endpoint' => 'http://127.0.0.1:9500',
        'url' => 'https://portfolio.example/media',
        'use_path_style_endpoint' => true,
    ]);
    Storage::forgetDisk('s3');
    $project = Project::factory()->make([
        'cover_image' => 'projects/covers/example.png',
        'images' => ['projects/gallery/example.png'],
    ]);

    expect(ProjectData::detail($project))
        ->cover_image_url->toBe('https://portfolio.example/media/projects/covers/example.png')
        ->gallery_urls->toBe(['https://portfolio.example/media/projects/gallery/example.png']);
});

test('failed S3 cover upload does not create a project', function () {
    config()->set('filesystems.project_media_disk', 's3');
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('put')->once()->andReturn(false);
    $disk->shouldReceive('delete')->once()->andReturn(true);
    Storage::shouldReceive('disk')->with('s3')->once()->andReturn($disk);

    actingAs(User::factory()->createOne())
        ->post(route('admin.projects.store'), [
            'title' => 'Failed upload',
            'description' => 'Storage is unavailable.',
            'slug' => 'failed-upload',
            'cover_image' => UploadedFile::fake()->image('cover.png'),
            'sort_order' => 0,
        ])->assertServerError();

    assertDatabaseCount('projects', 0);
});
