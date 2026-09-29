<div>
    <div>
        <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-sm text-ink-muted hover:text-ink">Back to posts</a>
        <h1 class="mt-1 font-display text-3xl font-semibold text-ink">{{ $isEditing ? 'Edit post' : 'New post' }}</h1>
    </div>

    <form wire:submit="save" class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">

        {{-- Main column --}}
        <div class="min-w-0 space-y-6">
            <div>
                <x-forms.label for="title">Title</x-forms.label>
                <x-forms.input wire:model="title" id="title" type="text" class="font-display text-lg" placeholder="A clear, specific headline" maxlength="150" required />
                <x-forms.error for="title" />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <x-forms.label for="body" class="mb-0">Content</x-forms.label>

                    <div class="inline-flex rounded-md border border-line bg-white p-0.5 text-sm" role="tablist">
                        <button type="button" role="tab" aria-selected="{{ $showPreview ? 'false' : 'true' }}" wire:click="showWrite"
                                class="rounded px-3 py-1 transition-colors {{ $showPreview ? 'text-ink-muted hover:text-ink' : 'bg-accent text-paper' }}">Write</button>
                        <button type="button" role="tab" aria-selected="{{ $showPreview ? 'true' : 'false' }}" wire:click="showPreviewTab"
                                class="rounded px-3 py-1 transition-colors {{ $showPreview ? 'bg-accent text-paper' : 'text-ink-muted hover:text-ink' }}">Preview</button>
                    </div>
                </div>

                @if ($showPreview)
                    <div class="mt-2 min-h-[28rem] rounded-md border border-line bg-white p-6">
                        @if (trim($body) === '')
                            <p class="text-sm text-ink-muted">Nothing to preview yet.</p>
                        @else
                            {{-- Safe: produced by MarkdownRenderer (raw HTML stripped, unsafe links disabled) --}}
                            <div class="prose-post">{!! $this->previewHtml !!}</div>
                        @endif
                    </div>
                @else
                    <x-forms.textarea wire:model="body" id="body" rows="20" class="mt-2 font-mono text-[13px] leading-relaxed" placeholder="Write in Markdown…" required />
                @endif

                <x-forms.error for="body" />
                <p class="mt-1.5 text-xs text-ink-faint">Markdown is supported. Raw HTML is removed when the post is displayed.</p>
            </div>

            <div>
                <x-forms.label for="excerpt">Excerpt</x-forms.label>
                <x-forms.textarea wire:model="excerpt" id="excerpt" rows="3" maxlength="200" placeholder="Optional. A short summary shown in listings. Generated from the content if left empty." />
                <x-forms.error for="excerpt" />
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">

            <section class="rounded-lg border border-line bg-paper p-5">
                <h2 class="font-display text-base font-semibold">Publish</h2>

                @if ($canPublish)
                    <div class="mt-3">
                        <x-forms.label for="status">Status</x-forms.label>
                        <x-forms.select wire:model="status" id="status">
                            @foreach (\App\Enums\PostStatus::cases() as $case)
                                <option value="{{ $case->value }}">{{ $case->label() }}</option>
                            @endforeach
                        </x-forms.select>
                        <x-forms.error for="status" />
                    </div>
                @else
                    <p class="mt-3 text-sm text-ink-muted">
                        {{ $isEditing ? 'Current status: '.ucfirst($status).'.' : 'New posts are saved as drafts.' }}
                        An editor publishes posts.
                    </p>
                @endif

                <div class="mt-5 flex items-center gap-4">
                    <x-forms.button type="submit" wire:loading.attr="disabled" wire:target="save,newImage">
                        <span wire:loading.remove wire:target="save">Save post</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </x-forms.button>
                    <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-sm text-ink-muted hover:text-ink">Cancel</a>
                </div>
            </section>

            <section class="space-y-5 rounded-lg border border-line bg-paper p-5">
                <div>
                    <x-forms.label for="categoryId">Category</x-forms.label>
                    <x-forms.select wire:model="categoryId" id="categoryId">
                        <option value="">Uncategorized</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-forms.error for="categoryId" />
                </div>

                <div>
                    <x-forms.label for="tagList">Tags</x-forms.label>
                    <x-forms.input wire:model="tagList" id="tagList" type="text" placeholder="laravel, php, tutorial" maxlength="255" />
                    <p class="mt-1.5 text-xs text-ink-faint">Separate with commas. Up to 10 tags.</p>
                    <x-forms.error for="tagList" />
                </div>

                <div>
                    <x-forms.label for="slug">URL slug</x-forms.label>
                    <x-forms.input wire:model="slug" id="slug" type="text" placeholder="generated-from-title" maxlength="160" />
                    <p class="mt-1.5 text-xs text-ink-faint">Leave empty to generate it from the title.</p>
                    <x-forms.error for="slug" />
                </div>
            </section>

            <section class="rounded-lg border border-line bg-paper p-5">
                <h2 class="font-display text-base font-semibold">Featured image</h2>

                @if ($this->imagePreviewUrl)
                    <img src="{{ $this->imagePreviewUrl }}" alt="Preview of the selected image" class="mt-3 aspect-[16/10] w-full rounded-md object-cover">
                    <button type="button" wire:click="clearNewImage" class="mt-2 text-sm text-danger hover:underline">Discard selected image</button>
                @elseif ($existingImage && ! $removeImage)
                    <img src="{{ asset('storage/'.$existingImage) }}" alt="Current featured image" class="mt-3 aspect-[16/10] w-full rounded-md object-cover">
                    <label class="mt-2 flex items-center gap-2 text-sm text-ink-muted">
                        <input type="checkbox" wire:model="removeImage" class="rounded border-line text-accent focus:ring-accent">
                        Remove this image on save
                    </label>
                @endif

                <div class="mt-3">
                    <label for="newImage" class="sr-only">Choose an image</label>
                    <input type="file" wire:model="newImage" id="newImage" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-ink-muted file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-paper-alt file:px-3.5 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:bg-line">
                    <p wire:loading wire:target="newImage" class="mt-1.5 text-sm text-ink-muted">Uploading…</p>
                    <x-forms.error for="newImage" />
                    <p class="mt-1.5 text-xs text-ink-faint">JPG, PNG or WebP, up to 2 MB.</p>
                </div>
            </section>

            <section class="space-y-5 rounded-lg border border-line bg-paper p-5">
                <h2 class="font-display text-base font-semibold">Search appearance</h2>

                <div>
                    <x-forms.label for="metaTitle">Meta title</x-forms.label>
                    <x-forms.input wire:model="metaTitle" id="metaTitle" type="text" maxlength="70" placeholder="Defaults to the post title" />
                    <x-forms.error for="metaTitle" />
                </div>

                <div>
                    <x-forms.label for="metaDescription">Meta description</x-forms.label>
                    <x-forms.textarea wire:model="metaDescription" id="metaDescription" rows="3" maxlength="200" placeholder="Defaults to the excerpt" />
                    <x-forms.error for="metaDescription" />
                </div>
            </section>
        </aside>
    </form>
</div>
