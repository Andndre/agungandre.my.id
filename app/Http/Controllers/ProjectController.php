<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectData;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function show(string $slug): Response
    {
        $project = Project::published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('projects/Show', ['project' => ProjectData::detail($project)]);
    }
}
