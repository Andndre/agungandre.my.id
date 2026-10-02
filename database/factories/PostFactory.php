<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'excerpt' => fake()->paragraph(),
            'content' => '# '.fake()->sentence().'\n\n'.fake()->paragraph(),
            'published_at' => now()->subDay(),
        ];
    }
}
