<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::ordered()->get();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Projects/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'slug' => ['required', 'string', 'max:255', 'unique:projects,slug'],
            'cover_image' => ['required', 'image', 'max:2048'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'max:2048'],
            'tech_stack' => ['nullable', 'array', 'max:20'],
            'tech_stack.*' => ['string', 'max:50'],
            'live_url' => ['nullable', 'url', 'max:500'],
            'repo_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
        ]);

        $coverPath = $request->file('cover_image')
            ->store('projects/covers', 'public');

        $imagesPaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagesPaths[] = $image->store('projects/gallery', 'public');
            }
        }

        Project::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'slug' => $validated['slug'],
            'cover_image' => $coverPath,
            'images' => $imagesPaths,
            'tech_stack' => $validated['tech_stack'] ?? [],
            'live_url' => $validated['live_url'] ?? null,
            'repo_url' => $validated['repo_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_featured' => $validated['is_featured'] ?? false,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Project created successfully.'),
        ]);

        return redirect()->route('admin.projects.index');
    }

    public function edit(Project $project)
    {
        return Inertia::render('Admin/Projects/Edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'slug' => ['required', 'string', 'max:255', 'unique:projects,slug,'.$project->id],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'max:2048'],
            'tech_stack' => ['nullable', 'array', 'max:20'],
            'tech_stack.*' => ['string', 'max:50'],
            'live_url' => ['nullable', 'url', 'max:500'],
            'repo_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
        ]);

        $data = collect($validated)->except(['cover_image', 'images'])->toArray();

        if ($request->hasFile('cover_image')) {
            if ($project->cover_image) {
                Storage::disk('public')->delete($project->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')
                ->store('projects/covers', 'public');
        }

        if ($request->hasFile('images')) {
            if ($project->images) {
                foreach ($project->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
            $imagesPaths = [];
            foreach ($request->file('images') as $image) {
                $imagesPaths[] = $image->store('projects/gallery', 'public');
            }
            $data['images'] = $imagesPaths;
        }

        $project->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Project updated successfully.'),
        ]);

        return redirect()->route('admin.projects.index');
    }

    public function destroy(Project $project)
    {
        if ($project->cover_image) {
            Storage::disk('public')->delete($project->cover_image);
        }
        if ($project->images) {
            foreach ($project->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $project->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Project deleted successfully.'),
        ]);

        return redirect()->route('admin.projects.index');
    }
}
