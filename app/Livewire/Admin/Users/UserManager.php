<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\SecurityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

/**
 * Admin-only user management. This is the most privilege-sensitive screen
 * in the app (it can grant the admin role), so every rule is enforced on
 * the server, in the action, regardless of what the UI shows:
 *
 *  - Only roles that actually exist can be assigned (whitelist via Rule::in).
 *  - Nobody can change their OWN role or deactivate/delete themselves.
 *  - The last active administrator can never be demoted, deactivated or
 *    deleted, so the CMS can't be locked out of its own admin area.
 *  - Ids being edited/deleted live in #[Locked] properties.
 */
#[Layout('components.layout.admin')]
#[Title('Users')]
class UserManager extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $confirmingDeleteId = null;

    #[Locked]
    public ?string $notice = null;

    public bool $showForm = false;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public string $password = '';

    public bool $isActive = true;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->notice = null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            // '' means "no role". Anything else must be an existing role.
            'role' => ['nullable', 'string', Rule::in(Role::query()->pluck('name')->all())],
            'password' => [$this->editingId !== null ? 'nullable' : 'required', 'string', Password::defaults()],
            'isActive' => ['boolean'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', User::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $userId): void
    {
        $user = User::query()->with('roles')->findOrFail($userId);

        $this->authorize('update', $user);

        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = (string) $user->roles->first()?->name;
        $this->isActive = $user->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $actor = Auth::user();
        $newRole = ($validated['role'] ?? '') !== '' ? $validated['role'] : null;

        if ($this->editingId === null) {
            $this->authorize('create', User::class);
            $this->createUser($validated, $newRole);

            return;
        }

        $target = User::query()->with('roles')->findOrFail($this->editingId);

        $this->authorize('update', $target);

        $currentRole = $target->roles->first()?->name;
        $isSelf = $actor->id === $target->id;

        // Role changes (#: not on yourself, and never demote the last admin).
        if ($newRole !== $currentRole) {
            $this->authorize('changeRole', $target);

            if ($currentRole === 'admin' && $this->isLastActiveAdmin($target)) {
                $this->addError('role', 'This is the only active administrator, so the role cannot be removed.');

                return;
            }
        }

        // Deactivation: not on yourself, and never the last admin.
        if (! $validated['isActive']) {
            if ($isSelf) {
                $this->addError('isActive', 'You cannot deactivate your own account.');

                return;
            }

            if ($this->isLastActiveAdmin($target)) {
                $this->addError('isActive', 'This is the only active administrator, so it cannot be deactivated.');

                return;
            }
        }

        $emailChanged = $target->email !== $validated['email'];

        $target->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (($validated['password'] ?? '') !== '') {
            $target->password = Hash::make($validated['password']);
        }

        if ($emailChanged) {
            $target->email_verified_at = null;
        }

        // is_active is deliberately not mass-assignable on User.
        $target->is_active = (bool) $validated['isActive'];
        $target->save();

        if ($emailChanged) {
            $target->sendEmailVerificationNotification();
        }

        if ($newRole !== $currentRole) {
            $target->syncRoles($newRole ? [$newRole] : []);

            SecurityLog::info('role_changed', [
                'target_id' => $target->id,
                'from' => $currentRole,
                'to' => $newRole,
            ]);
        }

        if ($target->wasChanged('is_active')) {
            SecurityLog::info($target->is_active ? 'user_activated' : 'user_deactivated', ['target_id' => $target->id]);
        }

        $this->notice = 'User updated.';
        $this->resetForm();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createUser(array $validated, ?string $role): void
    {
        $user = new User;
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Not mass-assignable on purpose. Accounts created by an admin are
        // treated as verified: the admin vouches for the address.
        $user->is_active = (bool) $validated['isActive'];
        $user->email_verified_at = now();
        $user->save();

        if ($role !== null) {
            $user->syncRoles([$role]);
        }

        SecurityLog::info('user_created', ['target_id' => $user->id, 'role' => $role]);

        $this->notice = 'User created.';
        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function confirmDelete(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $this->authorize('delete', $user);

        $this->confirmingDeleteId = $user->id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        abort_if($this->confirmingDeleteId === null, 422);

        $user = User::query()->findOrFail($this->confirmingDeleteId);

        // UserPolicy::delete also blocks self-deletion and removing the
        // last administrator.
        $this->authorize('delete', $user);

        if ($user->posts()->exists()) {
            $this->confirmingDeleteId = null;
            $this->notice = 'That user still owns posts. Deactivate the account instead, or delete/reassign the posts first.';

            return;
        }

        $user->delete();

        SecurityLog::info('user_deleted', ['target_id' => $user->id]);

        $this->confirmingDeleteId = null;
        $this->notice = 'User deleted.';
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->hasRole('admin') || ! $user->is_active) {
            return false;
        }

        return User::role('admin')->where('is_active', true)->count() <= 1;
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'email', 'role', 'password', 'showForm');
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', function (Builder $q) {
                $q->where(function (Builder $q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.users.user-manager', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }
}
