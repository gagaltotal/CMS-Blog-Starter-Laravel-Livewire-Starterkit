<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag) {
            if (blank($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Find-or-create a set of Tag models from a comma-separated string typed
     * into the post editor (e.g. "laravel, php, tutorial"). Names are
     * trimmed and de-duplicated by their slug so "PHP" and "php" resolve to
     * the same tag instead of silently creating two near-identical rows.
     *
     * @return array<int>
     */
    public static function idsFromCommaList(?string $list): array
    {
        $names = collect(explode(',', (string) $list))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique(fn (string $name) => Str::slug($name))
            ->take(10); // sane upper bound so one field can't create hundreds of rows

        return $names->map(function (string $name) {
            return static::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            )->id;
        })->all();
    }
}
