<?php

namespace App\Http\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostMediaController extends Controller
{
    public function __invoke(string $filename): StreamedResponse
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('blog.media_disk'));
        $path = 'blog/images/'.$filename;

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
