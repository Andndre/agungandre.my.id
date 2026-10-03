<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeletePostRequest;
use App\Http\Requests\Admin\PreviewPostRequest;
use App\Http\Requests\Admin\SavePostRequest;
use App\Models\Post;
use App\Support\PostMarkdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Posts/Index', [
            'posts' => Post::query()->latest('updated_at')
                ->paginate(15, ['id', 'title', 'slug', 'reading_time', 'published_at', 'updated_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Posts/Create');
    }

    public function store(SavePostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['published_at'] = $this->publicationDate($data);
        unset($data['publication']);

        Post::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tulisan berhasil dibuat.']);

        return redirect()->route('admin.posts.index');
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('Admin/Posts/Edit', ['post' => $post]);
    }

    public function preview(PreviewPostRequest $request, PostMarkdown $markdown): JsonResponse
    {
        return response()->json(['contentHtml' => $markdown->render($request->validated('content'))]);
    }

    public function update(SavePostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();
        $data['published_at'] = $this->publicationDate($data, $post);
        unset($data['publication']);

        $post->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tulisan berhasil diperbarui.']);

        return redirect()->route('admin.posts.index');
    }

    public function destroy(DeletePostRequest $request, Post $post): RedirectResponse
    {
        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tulisan berhasil dihapus.']);

        return redirect()->route('admin.posts.index');
    }

    private function publicationDate(array $data, ?Post $post = null): ?string
    {
        return match ($data['publication']) {
            'draft' => null,
            'scheduled' => $data['published_at'],
            'now' => $post?->published_at?->isPast()
                ? $post->published_at->toDateTimeString()
                : now()->toDateTimeString(),
        };
    }
}
