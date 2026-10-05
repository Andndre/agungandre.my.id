<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\PostMarkdown;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class BlogController extends Controller
{
    public function index(Request $request): View|Response
    {
        if ($request->header('X-Inertia')) {
            return Inertia::location($request->fullUrl());
        }

        return view('blog.index', [
            'posts' => Post::published()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(10, ['id', 'title', 'slug', 'excerpt', 'reading_time', 'published_at']),
        ]);
    }

    public function show(Request $request, string $slug, PostMarkdown $markdown): View|Response
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        if ($request->header('X-Inertia')) {
            return Inertia::location($request->fullUrl());
        }

        return view('blog.show', [
            'post' => $post,
            'contentHtml' => $markdown->render($post->content),
        ]);
    }
}
