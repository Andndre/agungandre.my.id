<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'excerpt', 'content', 'published_at'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'reading_time' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $post): void {
            $base = Str::slug($post->title) ?: 'post';
            $base = Str::limit($base, 200, '');
            $slug = $base;
            $suffix = 2;

            while (self::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix++;
            }

            $post->slug = $slug;
        });

        static::saving(function (self $post): void {
            preg_match_all('/[\p{L}\p{N}]+/u', $post->content ?? '', $matches);
            $post->reading_time = max(1, (int) ceil(count($matches[0]) / 200));
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
