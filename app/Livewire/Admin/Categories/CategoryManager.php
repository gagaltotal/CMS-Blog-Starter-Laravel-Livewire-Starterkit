<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layout.admin')]
#[Title('Categories')]
class CategoryManager extends Component
{
    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $confirmingDeleteId = null;

    #[Locked]
    public ?string $notice = null;

    public bool $showForm = false;

    public string $name = '';

    public string $description = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:2', 'max:60',
                Rule::unique('categories', 'name')->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', Category::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $this->authorize('update', $category);

        $this->resetForm();
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = (string) $category->description;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId !== null) {
            $category = Category::query()->findOrFail($this->editingId);
            $this->authorize('update', $category);
        } else {
            $category = new Category;
            $this->authorize('create', Category::class);
        }

        // The slug is generated once (Category::booted) and then left
        // alone on rename, so existing public URLs don't silently break.
        $category->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
        ])->save();

        $this->notice = $this->editingId !== null ? 'Category updated.' : 'Category created.';

        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function confirmDelete(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $this->authorize('delete', $category);

        $this->confirmingDeleteId = $category->id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        abort_if($this->confirmingDeleteId === null, 422);

        $category = Category::query()->findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $category);

        // posts.category_id is SET NULL on delete (see migration): the
        // posts survive, they just become "Uncategorized".
        $category->delete();

        $this->confirmingDeleteId = null;
        $this->notice = 'Category deleted. Its posts are now uncategorized.';
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'description', 'showForm');
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.categories.category-manager', [
            'categories' => Category::query()->withCount('posts')->orderBy('name')->get(),
        ]);
    }
}
