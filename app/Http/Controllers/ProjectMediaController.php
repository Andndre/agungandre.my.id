<?php

namespace App\Http\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectMediaController extends Controller
{
    public function __invoke(string $directory, string $filename): StreamedResponse
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('filesystems.project_media_disk'));
        $path = 'projects/'.$directory.'/'.$filename;

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
