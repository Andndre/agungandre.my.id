<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => Project::count(),
                'featured' => Project::where('is_featured', true)->count(),
                'published' => Project::where('is_published', true)->count(),
                'drafts' => Project::where('is_published', false)->count(),
            ],
            'recentProjects' => Project::ordered()
                ->limit(5)
                ->get(['id', 'title', 'is_featured', 'is_published', 'created_at']),
        ]);
    }
}
