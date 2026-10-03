<?php

namespace Tests\Fixtures;

use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use GdImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PortfolioQaSeeder extends Seeder
{
    public function run(): void
    {
        PortfolioQaEnvironment::assertSafe(app());
        PortfolioQaEnvironment::useQaMedia(app());
        $this->createImage('qa-orbit.png', 'ORBIT / QA WORKSPACE', false);
        $this->createImage('qa-orbit-detail.png', 'ORBIT / QA DETAIL VIEW', true);
        $this->createImage('qa-schedule.png', 'SCHEDULE / QA WORKSPACE', true);

        $projects = [
            ['slug' => 'qa-orbit-workspace', 'title' => '[QA] Orbit workspace', 'cover_image' => 'projects/qa-orbit.png', 'images' => ['projects/qa-orbit-detail.png'], 'sort_order' => 1, 'is_featured' => true, 'is_published' => true],
            ['slug' => 'qa-scheduling-workspace', 'title' => '[QA] A deliberately long project title for a scheduling workspace that keeps complex delivery plans readable across small and large screens', 'cover_image' => 'projects/qa-schedule.png', 'images' => [], 'sort_order' => 2, 'is_featured' => false, 'is_published' => true],
            ['slug' => 'qa-missing-image', 'title' => '[QA] Missing image fallback', 'cover_image' => 'projects/qa-unavailable.png', 'images' => [], 'sort_order' => 3, 'is_featured' => false, 'is_published' => true],
            ['slug' => 'qa-unpublished-project', 'title' => '[QA] Unpublished project', 'cover_image' => null, 'images' => [], 'sort_order' => 0, 'is_featured' => false, 'is_published' => false],
        ];

        foreach ($projects as $project) {
            Project::updateOrCreate(['slug' => $project['slug']], [
                'description' => "QA sample only. This is synthetic content for visual verification, not a real portfolio project.\n\nProblem: check how a project explanation reads alongside its preview on desktop and mobile.\n\nContribution: exercise the layout, gallery, technology labels, and contact action.\n\nResult: verification content only; no client or performance claims.",
                'tech_stack' => $project['slug'] === 'qa-missing-image' ? [] : ['Laravel', 'Inertia.js', 'Svelte 5', 'Tailwind CSS', 'SQLite'],
                'live_url' => null,
                'repo_url' => null,
                ...$project,
            ]);
        }

        $this->post('qa-published-notes', '[QA] Notes on building clear product interfaces', now()->subDay()->toDateTimeString());
        $this->post('qa-scheduled-notes', '[QA] Scheduled writing preview', now()->addDay()->toDateTimeString());

        $owner = trim((string) config('blog.owner_email')) ?: 'qa-owner@example.test';
        $user = User::updateOrCreate(['email' => $owner], [
            'name' => 'QA Portfolio Admin',
            'password' => Hash::make('qa-preview-password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
    }

    private function post(string $slug, string $title, string $publishedAt): void
    {
        $post = Post::firstOrNew(['slug' => $slug]);
        $post->fill([
            'title' => $title,
            'excerpt' => 'Explicitly labeled QA writing used to verify typography, reading time, and publication status.',
            'content' => "# QA sample article\n\nThis is synthetic verification content, not a published claim about real work.\n\n## A clear hierarchy\n\nA useful interface makes the next action easy to find. This paragraph verifies readable line length, paragraph spacing, and theme contrast.\n\n```callout:info\n**QA note:** all screenshots and project examples in this test environment are labeled samples.\n```\n\n- Check the mobile layout.\n- Check the keyboard focus.\n- Check both themes.\n\n```php\nreturn Inertia::render('Welcome');\n```",
            'published_at' => $publishedAt,
        ]);
        $post->save();
        $post->forceFill(['slug' => $slug])->save();
    }

    private function createImage(string $filename, string $title, bool $detail): void
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('The QA screenshot generator requires the PHP GD extension.');
        }

        $directory = public_path('.qa/projects');
        app('files')->ensureDirectoryExists($directory);
        $image = imagecreatetruecolor(1440, 960);
        $background = imagecolorallocate($image, 244, 244, 252);
        $ink = imagecolorallocate($image, 29, 29, 56);
        $muted = imagecolorallocate($image, 99, 102, 126);
        $indigo = imagecolorallocate($image, 84, 67, 219);
        $white = imagecolorallocate($image, 255, 255, 255);
        $line = imagecolorallocate($image, 219, 220, 235);
        $green = imagecolorallocate($image, 26, 118, 84);

        imagefill($image, 0, 0, $background);
        imagefilledrectangle($image, 0, 0, 260, 959, $ink);
        $this->text($image, 26, 32, 68, $white, 'QA / STUDIO');
        foreach (['Workspace', 'Projects', 'Delivery', 'Settings'] as $index => $label) {
            $this->text($image, 20, 32, 162 + $index * 68, $index === 1 ? $white : $line, $label);
        }
        $this->text($image, 15, 32, 894, $line, 'SYNTHETIC QA SAMPLE');
        $this->text($image, 17, 312, 72, $muted, 'WORKSPACE / PROJECT OVERVIEW');
        $this->text($image, 38, 312, 140, $ink, $title);
        $this->text($image, 18, 312, 184, $muted, 'QA diagram - not real portfolio work');
        imagefilledrectangle($image, 1178, 52, 1372, 104, $indigo);
        $this->text($image, 17, 1202, 87, $white, '+ New project');

        foreach (['In progress', 'In review', 'Ready to share'] as $index => $label) {
            $left = 312 + $index * 358;
            imagefilledrectangle($image, $left, 238, $left + 324, 900, $white);
            $this->text($image, 22, $left + 22, 285, $ink, $label);
            imagefilledrectangle($image, $left + 22, 310, $left + 302, 312, $line);
            foreach (range(0, $detail ? 2 : 1) as $row) {
                $top = 346 + $row * 170;
                imagefilledrectangle($image, $left + 22, $top, $left + 302, $top + 142, $background);
                imagefilledrectangle($image, $left + 36, $top + 18, $left + 132, $top + 44, $index === 2 ? $green : $indigo);
                $this->text($image, 12, $left + 43, $top + 37, $white, 'QA SAMPLE');
                $this->text($image, 19, $left + 36, $top + 80, $ink, ['Interface review', 'Delivery checklist', 'Layout verification'][$row]);
                $this->text($image, 15, $left + 36, $top + 113, $muted, 'Sample / no client data');
            }
        }

        if (! imagepng($image, $directory.'/'.$filename, 8)) {
            throw new RuntimeException('Unable to write the QA screenshot.');
        }
        imagedestroy($image);
    }

    private function text(GdImage $image, int $size, int $x, int $y, int $color, string $text): void
    {
        $candidates = [
            (getenv('WINDIR') ?: 'C:/Windows').'/Fonts/segoeui.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/System/Library/Fonts/Supplemental/Arial.ttf',
        ];
        foreach ($candidates as $font) {
            if (is_file($font)) {
                imagettftext($image, $size, 0, $x, $y, $color, $font, $text);

                return;
            }
        }

        imagestring($image, 5, $x, $y - 15, $text, $color);
    }
}
