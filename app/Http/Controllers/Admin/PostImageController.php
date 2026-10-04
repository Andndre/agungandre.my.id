<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadPostImageRequest;
use App\Support\WebImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PostImageController extends Controller
{
    public function __invoke(UploadPostImageRequest $request, WebImageOptimizer $images): JsonResponse
    {
        $disk = (string) config('blog.media_disk');
        $path = $images->store($request->file('image'), $disk, 'blog/images', 'image');

        return response()->json(['url' => Storage::disk($disk)->url($path)], 201);
    }
}
