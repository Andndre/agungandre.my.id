<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadPostImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PostImageController extends Controller
{
    public function __invoke(UploadPostImageRequest $request): JsonResponse
    {
        $disk = (string) config('blog.media_disk');
        $path = $request->file('image')->store('blog/images', $disk);

        abort_unless($path, 500, 'Image upload failed.');

        return response()->json(['url' => Storage::disk($disk)->url($path)], 201);
    }
}
