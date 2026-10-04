<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProjectRequest;
use App\Models\Project;
use App\Support\ProjectData;
use App\Support\WebImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class ProjectController extends Controller
{
    public function __construct(private WebImageOptimizer $images) {}

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
        $this->saveProject($request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyek berhasil dibuat.']);

        return redirect()->route('admin.projects.index');
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('Admin/Projects/Edit', ['project' => ProjectData::admin($project)]);
    }

    public function update(SaveProjectRequest $request, Project $project): RedirectResponse
    {
        $this->saveProject($request, $project);

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

    private function saveProject(SaveProjectRequest $request, ?Project $project = null): void
    {
        $disk = (string) config('filesystems.project_media_disk');
        $data = $request->safe()->except(['cover_image', 'images']);
        $uploadedPaths = [];
        $replacedPaths = [];

        try {
            if ($request->hasFile('cover_image')) {
                $data['cover_image'] = $this->images->store($request->file('cover_image'), $disk, 'projects/covers', 'cover_image');
                $uploadedPaths[] = $data['cover_image'];
                $replacedPaths[] = $project?->cover_image;
            }

            if ($request->hasFile('images') || $project === null) {
                $data['images'] = [];

                foreach ($request->file('images', []) as $index => $image) {
                    $path = $this->images->store($image, $disk, 'projects/gallery', 'images.'.$index);
                    $uploadedPaths[] = $path;
                    $data['images'][] = $path;
                }

                $replacedPaths = [...$replacedPaths, ...($project?->images ?? [])];
            }

            DB::transaction(function () use ($project, $data): void {
                if ($project === null) {
                    $saved = Project::create($data)->exists;
                } else {
                    $saved = $project->update($data);
                }

                if (! $saved) {
                    throw new RuntimeException('Project persistence failed.');
                }
            });
        } catch (Throwable $exception) {
            $this->deleteImages($disk, $uploadedPaths);

            throw $exception;
        }

        $this->deleteImages($disk, array_values(array_filter($replacedPaths)));
    }

    /** @param array<string> $paths */
    private function deleteImages(string $disk, array $paths): void
    {
        if ($paths === []) {
            return;
        }

        try {
            if (! Storage::disk($disk)->delete($paths)) {
                report(new RuntimeException('Failed to clean up project images: '.implode(', ', $paths)));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
