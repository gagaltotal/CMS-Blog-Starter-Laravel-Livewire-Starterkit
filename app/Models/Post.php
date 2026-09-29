<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Services\MarkdownRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $category_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $body
 * @property string|null $featured_image
 * @property PostStatus $status
 * @property Carbon|null $published_at
 * @property int $views_count
 */
class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    /**
     * user_id is intentionally absent: the author is always set server-side
     * from the authenticated user (see Livewire\Admin\Posts\PostForm),
     * never taken from client input, which is what stops one author from
     * being able to spoof another as the post's owner.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'featured_image',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if (blank($post->slug)) {
                $post->slug = $post->title ? Str::slug($post->title) : Str::random(8);
            }

            $post->slug = static::uniqueSlug($post->slug, $post->id);

            if (blank($post->excerpt) && filled($post->body)) {
                $post->excerpt = MarkdownRenderer::toExcerpt($post->body);
            }

            // Keep published_at consistent with status rather than trusting
            // whatever value a form happened to submit.
            if ($post->status === PostStatus::Published && blank($post->published_at)) {
                $post->published_at = now();
            }

            if ($post->status === PostStatus::Draft) {
                $post->published_at = null;
            }
        });
    }

    protected static function uniqueSlug(string $slug, ?int $ignoreId): string
    {
        $original = $slug ?: Str::random(8);
        $attempt = $original;
        $i = 1;

        while (
            static::withoutGlobalScopes()
                ->where('slug', $attempt)
                ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $attempt = "{$original}-{$i}";
            $i++;
        }

        return $attempt;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        // Parameter-bound LIKE query via the query builder — never
        // string-concatenated into a raw SQL fragment, so this is not
        // susceptible to SQL injection regardless of what $term contains.
        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', '%'.$term.'%')
                ->orWhere('excerpt', 'like', '%'.$term.'%');
        });
    }

    /**
     * Post body is authored as Markdown and only ever converted to HTML
     * through this accessor, with raw HTML in the source stripped and
     * unsafe link schemes (e.g. javascript:) disabled. This is the single
     * choke point that keeps stored post content from becoming a stored-XSS
     * vector — never render $post->body directly with {!! !!} anywhere else.
     */
    protected function renderedBody(): Attribute
    {
        return Attribute::make(
            get: fn () => MarkdownRenderer::toHtml($this->body),
        );
    }

    /**
     * Built from asset() (i.e. the host the visitor actually used) rather
     * than Storage::url(), which is pinned to APP_URL and would produce
     * cross-origin image URLs — blocked by our CSP — whenever the site is
     * opened via a different host/port than APP_URL says.
     */
    protected function featuredImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->featured_image ? asset('storage/'.$this->featured_image) : null,
        );
    }

    protected function readingTime(): Attribute
    {
        return Attribute::make(
            get: fn () => max(1, (int) round(str_word_count(strip_tags($this->body)) / 200)),
        );
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}
