<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title' => ucwords($title),
            'description' => fake()->paragraphs(2, true),
            'slug' => Str::slug($title),
            'cover_image' => null,
            'images' => null,
            'tech_stack' => fake()->randomElements(
                ['Flutter', 'Laravel', 'Next.js', 'Svelte', 'Tailwind CSS', 'React', 'Node.js', 'Python'],
                fake()->numberBetween(2, 5)
            ),
            'live_url' => fake()->optional()->url(),
            'repo_url' => fake()->optional()->url(),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_featured' => fake()->boolean(20),
            'is_published' => fake()->boolean(80),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_published' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
