<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 */
class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }

            $original = $category->slug;
            $attempt = $original;
            $i = 1;

            while (
                static::where('slug', $attempt)
                    ->when($category->id, fn (Builder $q) => $q->where('id', '!=', $category->id))
                    ->exists()
            ) {
                $attempt = "{$original}-{$i}";
                $i++;
            }

            $category->slug = $attempt;
        });
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function publishedPostsCount(): int
    {
        return $this->posts()->published()->count();
    }
}
