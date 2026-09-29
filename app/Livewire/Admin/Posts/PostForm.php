<?php

namespace App\Livewire\Admin\Posts;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\MarkdownRenderer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Create + edit form for posts.
 *
 * Security model (each point is enforced on the SERVER — hiding a control
 * in the Blade view is only ever cosmetic):
 *
 *  1. Which post is being edited lives in a #[Locked] property, so a
 *     crafted request can't retarget an open form at someone else's post.
 *  2. mount() authorises access to the page, and save() authorises AGAIN:
 *     mount() runs once per page load, while Livewire actions can be
 *     invoked directly at any time by anyone who can craft a request.
 *  3. The author (user_id) is always taken from the session, never from
 *     a form field.
 *  4. Publishing is a separate permission: whatever `status` the browser
 *     sends, it is only honoured if the user may publish.
 *  5. Uploads are validated by real content (not the client's filename or
 *     claimed MIME), restricted to raster image types (no SVG — a classic
 *     stored-XSS vector), size-capped, and stored under a random name.
 */
#[Layout('components.layout.admin')]
class PostForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $postId = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $body = '';

    public string $categoryId = '';

    public string $tagList = '';

    public string $status = 'draft';

    public string $metaTitle = '';

    public string $metaDescription = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $newImage = null;

    #[Locked]
    public ?string $existingImage = null;

    public bool $removeImage = false;

    public bool $showPreview = false;

    /**
     * $post is the numeric id from the /posts/{post}/edit URL (constrained
     * with whereNumber() in routes/web.php), or null on the create route.
     * It is looked up explicitly rather than relying on implicit model
     * binding, so a missing post is a clean 404 either way.
     */
    public function mount(?int $post = null): void
    {
        if ($post !== null) {
            $post = Post::query()->findOrFail($post);

            $this->authorize('update', $post);

            $this->postId = $post->id;
            $this->title = $post->title;
            $this->slug = $post->slug;
            $this->excerpt = (string) $post->excerpt;
            $this->body = $post->body;
            $this->categoryId = (string) ($post->category_id ?? '');
            $this->tagList = $post->tags()->pluck('name')->implode(', ');
            $this->status = $post->status->value;
            $this->metaTitle = (string) $post->meta_title;
            $this->metaDescription = (string) $post->meta_description;
            $this->existingImage = $post->featured_image;

            return;
        }

        $this->authorize('create', Post::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'slug' => [
                'nullable', 'string', 'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')->ignore($this->postId),
            ],
            'excerpt' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'min:10', 'max:100000'],
            'categoryId' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'tagList' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'metaTitle' => ['nullable', 'string', 'max:70'],
            'metaDescription' => ['nullable', 'string', 'max:200'],
            'newImage' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:max_width=6000,max_height=6000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers and single hyphens.',
            'newImage.max' => 'The image may not be larger than 2 MB.',
            'newImage.mimes' => 'Only JPG, PNG or WebP images are allowed.',
            'newImage.image' => 'The file must be a valid image.',
        ];
    }

    /**
     * Validate a picked image straight away, so a bad file is rejected
     * (and never previewed) before the form is even submitted.
     */
    public function updatedNewImage(): void
    {
        $this->validateOnly('newImage');
        $this->removeImage = false;
    }

    public function clearNewImage(): void
    {
        $this->reset('newImage');
        $this->resetErrorBag('newImage');
    }

    public function showWrite(): void
    {
        $this->showPreview = false;
    }

    public function showPreviewTab(): void
    {
        $this->showPreview = true;
    }

    /**
     * Rendered through the one shared, XSS-safe Markdown pipeline — the
     * same one the public site uses — so the preview can never show
     * something the published page wouldn't, or vice versa.
     */
    #[Computed]
    public function previewHtml(): string
    {
        return MarkdownRenderer::toHtml($this->body);
    }

    #[Computed]
    public function imagePreviewUrl(): ?string
    {
        if (! $this->newImage || $this->getErrorBag()->has('newImage')) {
            return null;
        }

        try {
            return $this->newImage->temporaryUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    public function save(): void
    {
        $user = Auth::user();

        $validated = $this->validate();

        $post = $this->postId !== null
            ? Post::query()->findOrFail($this->postId)
            : new Post;

        // Re-authorise inside the action itself (see class docblock #2).
        if ($post->exists) {
            $this->authorize('update', $post);
        } else {
            $this->authorize('create', Post::class);
        }

        // Publishing is its own permission (#4). Whatever the browser
        // claims, a user who can't publish can neither publish a new post
        // nor change the status of an existing one.
        $requested = PostStatus::from($validated['status']);
        $mayPublish = $user->can('publish', $post);

        $status = match (true) {
            $mayPublish => $requested,
            $post->exists => $post->status,
            default => PostStatus::Draft,
        };

        $oldImage = $post->featured_image;
        $imagePath = $oldImage;
        $storedNewImage = null;

        if ($this->newImage) {
            $imagePath = $storedNewImage = $this->newImage->storePublicly('posts', 'public');
        } elseif ($this->removeImage) {
            $imagePath = null;
        }

        $post->fill([
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?: null,
            'excerpt' => $validated['excerpt'] ?: null,
            'body' => $validated['body'],
            'category_id' => ($validated['categoryId'] ?? '') !== '' ? (int) $validated['categoryId'] : null,
            'featured_image' => $imagePath,
            'status' => $status,
            'meta_title' => $validated['metaTitle'] ?: null,
            'meta_description' => $validated['metaDescription'] ?: null,
        ]);

        if (! $post->exists) {
            // The author is always the authenticated user (#3).
            $post->user_id = $user->id;
        }

        try {
            DB::transaction(function () use ($post, $validated) {
                $post->save();
                $post->tags()->sync(Tag::idsFromCommaList($validated['tagList'] ?? ''));
            });
        } catch (\Throwable $e) {
            // Don't leave an orphaned upload behind if the save failed.
            if ($storedNewImage) {
                Storage::disk('public')->delete($storedNewImage);
            }

            throw $e;
        }

        // Only now that the database write succeeded is it safe to remove
        // the file that was replaced or cleared.
        if ($oldImage && $oldImage !== $imagePath) {
            Storage::disk('public')->delete($oldImage);
        }

        session()->flash('status', 'Post saved.');

        $this->redirectRoute('admin.posts.index', navigate: true);
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.admin.posts.post-form', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'canPublish' => $user->can('publish posts'),
            'isEditing' => $this->postId !== null,
        ])->title($this->postId !== null ? 'Edit post' : 'New post');
    }
}
