<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\PostMarkdown;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('blog/Index', [
            'posts' => Post::published()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(10, ['id', 'title', 'slug', 'excerpt', 'reading_time', 'published_at']),
        ]);
    }

    public function show(string $slug, PostMarkdown $markdown): Response
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('blog/Show', [
            'post' => $post->only('title', 'slug', 'excerpt', 'reading_time', 'published_at'),
            'contentHtml' => $markdown->render($post->content),
        ]);
    }
}
