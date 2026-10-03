<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Project;
use App\Support\ProjectData;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'projects' => Project::published()->ordered()
                ->get(['id', 'title', 'slug', 'description', 'cover_image', 'tech_stack', 'live_url', 'repo_url', 'is_featured'])
                ->map(ProjectData::summary(...)),
            'latestPosts' => Post::published()->orderByDesc('published_at')->orderByDesc('id')
                ->limit(3)->get(['id', 'title', 'slug', 'excerpt', 'reading_time', 'published_at']),
        ]);
    }
}
