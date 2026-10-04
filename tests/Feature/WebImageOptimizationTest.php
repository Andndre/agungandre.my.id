<?php

use App\Models\Project;
use App\Models\User;
use App\Support\WebImageOptimizer;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config()->set('filesystems.project_media_disk', 's3');
    config()->set('blog.media_disk', 's3');
    config()->set('blog.owner_email', 'image-owner@example.com');
    Storage::fake('s3');
    actingAs(User::factory()->createOne(['email' => 'image-owner@example.com']));
});

function webImageBytes(string $format, int $width = 80, int $height = 40, bool $transparent = false): string
{
    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $colors = [
        imagecolorallocatealpha($image, 240, 20, 20, $transparent ? 127 : 0),
        imagecolorallocate($image, 20, 240, 20),
        imagecolorallocate($image, 20, 20, 240),
        imagecolorallocate($image, 240, 240, 20),
    ];

    imagefilledrectangle($image, 0, 0, (int) ($width / 2) - 1, (int) ($height / 2) - 1, $colors[0]);
    imagefilledrectangle($image, (int) ($width / 2), 0, $width - 1, (int) ($height / 2) - 1, $colors[1]);
    imagefilledrectangle($image, 0, (int) ($height / 2), (int) ($width / 2) - 1, $height - 1, $colors[2]);
    imagefilledrectangle($image, (int) ($width / 2), (int) ($height / 2), $width - 1, $height - 1, $colors[3]);

    ob_start();

    match ($format) {
        'jpg' => imagejpeg($image, null, 100),
        'png' => imagepng($image, null, 6),
        'webp' => imagewebp($image, null, 90),
        'gif' => imagegif($image),
    };

    $bytes = ob_get_clean();
    unset($image);

    return $bytes;
}

function webImageUpload(string $bytes, string $name = 'image.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $bytes);
}

/** @return array<string, mixed> */
function webImageProjectData(array $overrides = []): array
{
    return [
        'title' => 'Optimized project',
        'slug' => 'optimized-project',
        'description' => 'Screenshot with readable details.',
        'sort_order' => 0,
        'cover_image' => webImageUpload(webImageBytes('png')),
        ...$overrides,
    ];
}

function webImagePngChunk(string $type, string $data): string
{
    return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
}

function webImageAnimatedPng(): string
{
    $png = webImageBytes('png', 8, 8);
    $offset = 33;
    $compressed = '';

    while (substr($png, $offset + 4, 4) !== 'IEND') {
        $length = unpack('N', substr($png, $offset, 4))[1];

        if (substr($png, $offset + 4, 4) === 'IDAT') {
            $compressed .= substr($png, $offset + 8, $length);
        }

        $offset += $length + 12;
    }

    $control = pack('N5n2C2', 0, 8, 8, 0, 0, 1, 10, 0, 0);

    return substr($png, 0, 33)
        .webImagePngChunk('acTL', pack('N2', 2, 0))
        .webImagePngChunk('fcTL', $control)
        .webImagePngChunk('IDAT', $compressed)
        .webImagePngChunk('fcTL', pack('N', 1).substr($control, 4))
        .webImagePngChunk('fdAT', pack('N', 2).$compressed)
        .webImagePngChunk('IEND', '');
}

function webImageAnimatedWebp(): string
{
    $still = webImageBytes('webp', 8, 8);
    $chunk = static fn (string $type, string $data): string => $type.pack('V', strlen($data)).$data.(strlen($data) % 2 ? "\0" : '');
    $canvas = "\x02\0\0\0\x07\0\0\x07\0\0";
    $frame = str_repeat("\0", 6)."\x07\0\0\x07\0\0\x64\0\0\0".substr($still, 12);
    $chunks = $chunk('VP8X', $canvas).$chunk('ANIM', str_repeat("\0", 6)).$chunk('ANMF', $frame).$chunk('ANMF', $frame);

    return 'RIFF'.pack('V', strlen($chunks) + 4).'WEBP'.$chunks;
}

function webImageOrientedJpeg(int $orientation): string
{
    $jpeg = webImageBytes('jpg');
    $tiff = 'II'.pack('v', 42).pack('V', 8).pack('v', 1)
        .pack('vvV', 0x0112, 3, 1).pack('v', $orientation)."\0\0".pack('V', 0);
    $exif = "Exif\0\0".$tiff;

    return substr($jpeg, 0, 2)."\xff\xe1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
}

function webImageCorruptJpeg(): string
{
    $jpeg = webImageBytes('jpg');

    return substr($jpeg, 0, strpos($jpeg, "\xff\xda") + 2);
}

test('article uploads store actual WebP and preserve the existing response contract', function (string $format) {
    postJson(route('admin.posts.images.store'), [
        'image' => webImageUpload(webImageBytes($format), 'photo.'.$format),
    ])->assertCreated()->assertJsonStructure(['url']);

    $files = Storage::disk('s3')->allFiles('blog/images');
    expect($files)->toHaveCount(1);
    expect($files[0])->toEndWith('.webp');

    $bytes = Storage::disk('s3')->get($files[0]);
    $info = getimagesizefromstring($bytes);
    expect($info['mime'])->toBe('image/webp')
        ->and([$info[0], $info[1]])->toBe([80, 40]);
    expect(imagecreatefromstring($bytes))->toBeInstanceOf(GdImage::class);
    get(route('blog.media.show', basename($files[0])))
        ->assertSuccessful()->assertHeader('content-type', 'image/webp')->assertStreamedContent($bytes);
})->with(['jpg', 'png', 'webp']);

test('image optimization resizes landscape and portrait proportionally without upscaling', function (int $width, int $height, array $expected) {
    $path = app(WebImageOptimizer::class)->store(
        webImageUpload(webImageBytes('jpg', $width, $height), 'photo.jpg'),
        's3', 'blog/images', 'image',
    );
    $info = getimagesizefromstring(Storage::disk('s3')->get($path));

    expect([$info[0], $info[1]])->toBe($expected);
})->with([
    'landscape' => [2400, 1200, [1600, 800]],
    'portrait' => [1200, 2400, [800, 1600]],
    'small' => [80, 40, [80, 40]],
]);

test('transparent PNG and WebP retain transparent and opaque pixels', function (string $format) {
    $path = app(WebImageOptimizer::class)->store(
        webImageUpload(webImageBytes($format, 80, 40, true), 'transparent.'.$format),
        's3', 'blog/images', 'image',
    );
    $image = imagecreatefromstring(Storage::disk('s3')->get($path));
    $transparent = imagecolorsforindex($image, imagecolorat($image, 10, 10));
    $opaque = imagecolorsforindex($image, imagecolorat($image, 60, 30));

    expect($transparent['alpha'])->toBe(127)->and($opaque['alpha'])->toBe(0);
})->with(['png', 'webp']);

test('JPEG EXIF rotations and reflections are applied before metadata is stripped', function (int $orientation, array $expectedCorners) {
    $path = app(WebImageOptimizer::class)->store(
        webImageUpload(webImageOrientedJpeg($orientation), 'camera.jpg'),
        's3', 'blog/images', 'image',
    );
    $bytes = Storage::disk('s3')->get($path);
    $image = imagecreatefromstring($bytes);
    $width = imagesx($image);
    $height = imagesy($image);
    $corners = [];

    foreach ([[10, 10], [$width - 11, 10], [10, $height - 11], [$width - 11, $height - 11]] as [$x, $y]) {
        $pixel = imagecolorsforindex($image, imagecolorat($image, $x, $y));
        $corners[] = [$pixel['red'] > 120, $pixel['green'] > 120, $pixel['blue'] > 120];
    }

    expect([$width, $height])->toBe($orientation >= 5 ? [40, 80] : [80, 40])
        ->and($corners)->toBe($expectedCorners)
        ->and($bytes)->not->toContain('Exif');
})->with([
    1 => [1, [[true, false, false], [false, true, false], [false, false, true], [true, true, false]]],
    2 => [2, [[false, true, false], [true, false, false], [true, true, false], [false, false, true]]],
    3 => [3, [[true, true, false], [false, false, true], [false, true, false], [true, false, false]]],
    4 => [4, [[false, false, true], [true, true, false], [true, false, false], [false, true, false]]],
    5 => [5, [[true, false, false], [false, false, true], [false, true, false], [true, true, false]]],
    6 => [6, [[false, false, true], [true, false, false], [true, true, false], [false, true, false]]],
    7 => [7, [[true, true, false], [false, true, false], [false, false, true], [true, false, false]]],
    8 => [8, [[false, true, false], [true, true, false], [true, false, false], [false, false, true]]],
]);

test('unsupported animated and corrupt uploads are rejected without writing files', function (string $scenario) {
    $bytes = match ($scenario) {
        'gif' => webImageBytes('gif'),
        'apng' => webImageAnimatedPng(),
        'animated webp' => webImageAnimatedWebp(),
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg"/>',
        'corrupt' => 'not an image',
        'truncated png' => substr(webImageBytes('png'), 0, 40),
        'broken pixels' => webImageCorruptJpeg(),
    };
    $response = postJson(route('admin.posts.images.store'), [
        'image' => webImageUpload($bytes, 'photo.png'),
    ])->assertUnprocessable()->assertJsonValidationErrors('image');

    expect($response->json('errors.image.0'))->toBeString()
        ->and(Storage::disk('s3')->allFiles())->toBe([]);
})->with(['gif', 'apng', 'animated webp', 'svg', 'corrupt', 'truncated png', 'broken pixels']);

test('pixel and side limits reject large headers before decoding', function (int $width, int $height) {
    $png = webImageBytes('png', 8, 8);
    $header = pack('N2', $width, $height).substr($png, 24, 5);
    $png = substr($png, 0, 8).webImagePngChunk('IHDR', $header).substr($png, 33);

    postJson(route('admin.posts.images.store'), ['image' => webImageUpload($png)])
        ->assertUnprocessable()->assertJsonValidationErrors('image')
        ->assertJsonPath('errors.image.0', 'Dimensi gambar maksimal 8192 piksel per sisi dan 12 megapiksel. Perkecil gambar lalu unggah kembali.');

    expect(Storage::disk('s3')->allFiles())->toBe([]);
})->with([
    'side' => [8193, 1],
    'pixels' => [4000, 3001],
]);

test('project cover and gallery are optimized and served as WebP', function () {
    post(route('admin.projects.store'), webImageProjectData([
        'images' => [webImageUpload(webImageBytes('jpg'), 'one.jpg'), webImageUpload(webImageBytes('webp'), 'two.webp')],
    ]))->assertRedirect(route('admin.projects.index'))->assertSessionHasNoErrors();

    $project = Project::firstOrFail();

    foreach ([$project->cover_image, ...$project->images] as $path) {
        expect($path)->toEndWith('.webp');
        expect(getimagesizefromstring(Storage::disk('s3')->get($path))['mime'])->toBe('image/webp');
        $directory = str_contains($path, '/covers/') ? 'covers' : 'gallery';
        get(route('projects.media.show', [$directory, basename($path)]))
            ->assertSuccessful()->assertHeader('content-type', 'image/webp');
    }
});

test('upload byte limits and project gallery count remain enforced', function (string $scenario) {
    if ($scenario === 'blog size') {
        postJson(route('admin.posts.images.store'), ['image' => UploadedFile::fake()->image('large.jpg')->size(10241)])
            ->assertUnprocessable()->assertJsonValidationErrors('image');
    } else {
        $data = match ($scenario) {
            'cover size' => ['cover_image' => UploadedFile::fake()->image('large.jpg')->size(2049)],
            'gallery size' => ['images' => [UploadedFile::fake()->image('large.jpg')->size(2049)]],
            'gallery count' => ['images' => array_map(fn (): UploadedFile => webImageUpload(webImageBytes('jpg'), 'image.jpg'), range(1, 11))],
            'gallery format' => ['images' => [webImageUpload(webImageBytes('gif'), 'image.gif')]],
        };
        post(route('admin.projects.store'), webImageProjectData($data))
            ->assertSessionHasErrors(match ($scenario) {
                'cover size' => 'cover_image',
                'gallery count' => 'images',
                default => 'images.0',
            });
    }

    expect(Storage::disk('s3')->allFiles())->toBe([]);
    assertDatabaseCount('projects', 0);
})->with(['blog size', 'cover size', 'gallery size', 'gallery count', 'gallery format']);

test('legacy article media remains accessible without conversion', function () {
    $bytes = webImageBytes('jpg');
    Storage::disk('s3')->put('blog/images/legacy.jpg', $bytes);

    get(route('blog.media.show', 'legacy.jpg'))
        ->assertSuccessful()->assertHeader('content-type', 'image/jpeg')->assertStreamedContent($bytes);
});

test('optimization reduces a large screenshot JPEG without enforcing a universal byte target', function () {
    $image = imagecreatetruecolor(2400, 1400);

    for ($row = 0; $row < 1400; $row++) {
        $shade = 220 + (int) ($row / 50);
        imageline($image, 0, $row, 2399, $row, imagecolorallocate($image, $shade, $shade, 250));
    }

    $ink = imagecolorallocate($image, 35, 40, 65);

    for ($row = 0; $row < 30; $row++) {
        imagefilledrectangle($image, 80, 40 + $row * 40, 2320, 60 + $row * 40, imagecolorallocate($image, 180 + $row, 170, 220));
        imagestring($image, 5, 100, 42 + $row * 40, 'Image optimization QA screenshot - readable interface content', $ink);
    }

    ob_start();
    imagejpeg($image, null, 100);
    $original = ob_get_clean();
    unset($image);
    $path = app(WebImageOptimizer::class)->store(webImageUpload($original, 'screenshot.jpg'), 's3', 'blog/images', 'image');

    expect(strlen(Storage::disk('s3')->get($path)))->toBeLessThan(strlen($original));
});

test('failed project uploads clean up new files and preserve the previous record and media', function (bool $editing) {
    $storage = Storage::disk('s3');
    $oldPaths = ['projects/covers/old.jpg', 'projects/gallery/old.png'];
    $project = $editing ? Project::factory()->create(['cover_image' => $oldPaths[0], 'images' => [$oldPaths[1]]]) : null;

    foreach ($editing ? $oldPaths : [] as $path) {
        $storage->put($path, 'old media');
    }

    $disk = Mockery::mock(FilesystemAdapter::class);
    $writes = 0;
    $disk->shouldReceive('put')->times(3)->andReturnUsing(function (string $path, string $bytes, array $options) use ($storage, &$writes): bool {
        $storage->put($path, $bytes, $options);

        return ++$writes < 3;
    });
    $disk->shouldReceive('delete')->twice()->andReturnUsing(fn (string|array $paths): bool => $storage->delete($paths));
    Storage::shouldReceive('disk')->with('s3')->andReturn($disk);

    post($editing ? route('admin.projects.update', $project) : route('admin.projects.store'), webImageProjectData([
        ...($editing ? ['_method' => 'put'] : []),
        'images' => [webImageUpload(webImageBytes('png')), webImageUpload(webImageBytes('jpg'), 'last.jpg')],
    ]))->assertServerError();

    expect($storage->allFiles())->toEqualCanonicalizing($editing ? $oldPaths : []);
    assertDatabaseCount('projects', $editing ? 1 : 0);

    if ($editing) {
        expect($project->fresh()->cover_image)->toBe($oldPaths[0])->and($project->fresh()->images)->toBe([$oldPaths[1]]);
    }
})->with([false, true]);

test('failed project persistence rolls back the record and removes new media', function (bool $editing) {
    $project = $editing ? Project::factory()->create(['cover_image' => 'projects/covers/old.jpg', 'images' => []]) : null;

    if ($editing) {
        Storage::disk('s3')->put($project->cover_image, 'old media');
    }

    $events = Project::getEventDispatcher();
    Project::setEventDispatcher(clone $events);

    try {
        Project::saved(function (): void {
            throw new RuntimeException('Persistence hook failed.');
        });

        post($editing ? route('admin.projects.update', $project) : route('admin.projects.store'), webImageProjectData([
            ...($editing ? ['_method' => 'put'] : []),
        ]))->assertServerError();
    } finally {
        Project::setEventDispatcher($events);
    }

    assertDatabaseCount('projects', $editing ? 1 : 0);
    expect(Storage::disk('s3')->allFiles())->toBe($editing ? ['projects/covers/old.jpg'] : []);

    if ($editing) {
        expect($project->fresh()->cover_image)->toBe('projects/covers/old.jpg');
    }
})->with([false, true]);

test('corrupt gallery pixels clean up an already uploaded cover', function () {
    post(route('admin.projects.store'), webImageProjectData([
        'images' => [webImageUpload(webImageCorruptJpeg(), 'broken.jpg')],
    ]))->assertSessionHasErrors('images.0');

    expect(Storage::disk('s3')->allFiles())->toBe([]);
    assertDatabaseCount('projects', 0);
});

test('encoder failure does not store an original image', function () {
    $this->partialMock(WebImageOptimizer::class, function ($mock): void {
        $mock->shouldReceive('encode')->once()->andThrow(new RuntimeException('Encoder unavailable.'));
    });

    postJson(route('admin.posts.images.store'), ['image' => webImageUpload(webImageBytes('png'))])
        ->assertServerError();

    expect(Storage::disk('s3')->allFiles())->toBe([]);
});

test('failed article storage cleans up partial data', function () {
    $storage = Storage::disk('s3');
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('put')->once()->andReturnUsing(function (string $path, string $bytes, array $options) use ($storage): bool {
        $storage->put($path, $bytes, $options);

        return false;
    });
    $disk->shouldReceive('delete')->once()->andReturnUsing(fn (string $path): bool => $storage->delete($path));
    Storage::shouldReceive('disk')->with('s3')->andReturn($disk);

    postJson(route('admin.posts.images.store'), ['image' => webImageUpload(webImageBytes('png'))])
        ->assertServerError();

    expect($storage->allFiles())->toBe([]);
});

test('runtime preflight verifies real WebP encode and decode', function () {
    $this->artisan('media:check-image-runtime')->expectsOutputToContain('Image runtime ready')->assertSuccessful();
});

test('runtime preflight fails when required support is missing', function () {
    $this->partialMock(WebImageOptimizer::class, function ($mock): void {
        $mock->shouldReceive('checkRuntime')->once()->andThrow(new RuntimeException('Missing WebP support.'));
    });

    $this->artisan('media:check-image-runtime')->expectsOutputToContain('Missing WebP support.')->assertFailed();
});

test('insufficient decode memory is a validation failure without stored files', function () {
    $previous = ini_get('memory_limit');
    ini_set('memory_limit', (string) (memory_get_usage(true) + 20 * 1024 ** 2));

    try {
        expect(fn () => app(WebImageOptimizer::class)->store(webImageUpload(webImageBytes('png')), 's3', 'blog/images', 'image'))
            ->toThrow(ValidationException::class);
    } finally {
        ini_set('memory_limit', $previous);
    }

    expect(Storage::disk('s3')->allFiles())->toBe([]);
});

test('PNG text containing animation chunk names is still a static image', function () {
    $png = webImageBytes('png');
    $png = substr($png, 0, 33).webImagePngChunk('tEXt', "Caption\0acTL ANIM ANMF").substr($png, 33);

    postJson(route('admin.posts.images.store'), ['image' => webImageUpload($png)])->assertCreated();
});

test('deployment preflight runs independently before application transfer', function (bool $withExtensions) {
    $workflow = file_get_contents(base_path('.github/workflows/deploy.yml'));
    preg_match("/php <<'PHP'\\R(.*?)\\R\\s+PHP\\R/s", $workflow, $matches);
    $source = preg_replace('/^ {12}/m', '', $matches[1]);
    $process = new Process($withExtensions ? [PHP_BINARY] : [PHP_BINARY, '-n']);
    $process->setInput($source)->run();

    expect($process->getExitCode())->toBe($withExtensions ? 0 : 1);

    if ($withExtensions) {
        expect($process->getOutput())->toContain('Production image runtime ready.');
    } else {
        expect($process->getErrorOutput())->toContain('Production requires PHP GD and EXIF.');
    }
})->with([true, false]);
