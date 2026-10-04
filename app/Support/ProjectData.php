<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class ProjectData
{
    public static function summary(Project $project): array
    {
        return [
            ...$project->only('id', 'title', 'slug', 'description', 'live_url', 'repo_url', 'is_featured'),
            'cover_image_url' => self::coverUrl($project),
            'tech_stack' => $project->tech_stack ?? [],
        ];
    }

    public static function detail(Project $project): array
    {
        return [...self::summary($project), 'gallery_urls' => self::galleryUrls($project)];
    }

    public static function admin(Project $project): array
    {
        return [
            ...$project->toArray(),
            'tech_stack' => $project->tech_stack ?? [],
            'cover_image_url' => self::coverUrl($project),
            'gallery_urls' => self::galleryUrls($project),
        ];
    }

    private static function coverUrl(Project $project): ?string
    {
        return $project->cover_image ? Storage::disk((string) config('filesystems.project_media_disk'))->url($project->cover_image) : null;
    }

    private static function galleryUrls(Project $project): array
    {
        return array_map(
            static fn (string $path): string => Storage::disk((string) config('filesystems.project_media_disk'))->url($path),
            $project->images ?? [],
        );
    }
}
