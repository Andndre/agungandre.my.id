<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    #[Fillable]
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $slug = null,
        public ?string $cover_image = null,
        public ?array $images = null,
        public ?array $tech_stack = null,
        public ?string $live_url = null,
        public ?string $repo_url = null,
        public int $sort_order = 0,
        public bool $is_featured = false,
        public bool $is_published = false,
    ) {}

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'tech_stack' => 'array',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_published', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('created_at');
    }
}
