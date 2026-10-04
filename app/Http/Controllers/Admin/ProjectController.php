<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProjectRequest;
use App\Models\Project;
use App\Support\ProjectData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Projects/Index', [
            'projects' => Project::ordered()->get()->map(ProjectData::admin(...)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Projects/Create');
    }

    public function store(SaveProjectRequest $request): RedirectResponse
    {
        Project::create([
            ...$request->safe()->except(['cover_image', 'images']),
            'cover_image' => $this->storeImage($request->file('cover_image'), 'projects/covers'),
            'images' => $this->storeImages($request->file('images', [])),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyek berhasil dibuat.']);

        return redirect()->route('admin.projects.index');
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('Admin/Projects/Edit', ['project' => ProjectData::admin($project)]);
    }

    public function update(SaveProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->safe()->except(['cover_image', 'images']);
        $replacedPaths = [];

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storeImage($request->file('cover_image'), 'projects/covers');
            $replacedPaths[] = $project->cover_image;
        }

        if ($request->hasFile('images')) {
            $data['images'] = $this->storeImages($request->file('images'));
            $replacedPaths = [...$replacedPaths, ...($project->images ?? [])];
        }

        $project->update($data);
        Storage::disk((string) config('filesystems.project_media_disk'))->delete(array_filter($replacedPaths));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyek berhasil diperbarui.']);

        return redirect()->route('admin.projects.index');
    }

    public function destroy(Project $project): RedirectResponse
    {
        Storage::disk((string) config('filesystems.project_media_disk'))->delete(array_filter([$project->cover_image, ...($project->images ?? [])]));
        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyek berhasil dihapus.']);

        return redirect()->route('admin.projects.index');
    }

    /** @param array<UploadedFile> $images */
    private function storeImages(array $images): array
    {
        return array_map(fn (UploadedFile $image): string => $this->storeImage($image, 'projects/gallery'), $images);
    }

    private function storeImage(UploadedFile $image, string $directory): string
    {
        $path = $image->store($directory, (string) config('filesystems.project_media_disk'));

        abort_unless($path, 500, 'Image upload failed.');

        return $path;
    }
}
