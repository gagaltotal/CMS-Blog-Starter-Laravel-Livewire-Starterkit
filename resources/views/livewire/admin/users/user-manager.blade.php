<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-semibold text-ink">Users</h1>
            <p class="mt-1 text-sm text-ink-muted">Create accounts and decide who can do what.</p>
        </div>

        @can('create', \App\Models\User::class)
            <x-forms.button type="button" wire:click="create">New user</x-forms.button>
        @endcan
    </div>

    @if ($notice)
        <p class="mt-6 rounded-md bg-success-soft px-4 py-2.5 text-sm text-success" role="status">{{ $notice }}</p>
    @endif

    <div class="mt-6 max-w-md">
        <label for="user-search" class="sr-only">Search users</label>
        <x-forms.input wire:model.live.debounce.300ms="search" id="user-search" type="search" placeholder="Search by name or email" />
    </div>

    <div class="mt-6 overflow-hidden rounded-lg border border-line bg-paper" wire:loading.class="opacity-60" wire:target="search,previousPage,nextPage">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line bg-paper-alt text-ink-muted">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium">User</th>
                        <th scope="col" class="px-5 py-3 font-medium">Role</th>
                        <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Status</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($users as $user)
                        @php($userRole = $user->roles->first()?->name)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-paper-alt/60">
                            <td class="px-5 py-4">
                                <p class="font-medium text-ink">
                                    {{ $user->name }}
                                    @if ($user->id === auth()->id())
                                        <span class="ml-1 text-xs font-normal text-ink-faint">(you)</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-ink-muted">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-4">
                                @if ($userRole)
                                    <span class="rounded-full bg-accent-soft px-2.5 py-1 text-xs font-medium capitalize text-accent">{{ $userRole }}</span>
                                @else
                                    <span class="text-ink-faint">No role</span>
                                @endif
                            </td>
                            <td class="hidden px-5 py-4 sm:table-cell">
                                @if ($user->is_active)
                                    <span class="rounded-full bg-success-soft px-2.5 py-1 text-xs font-medium text-success">Active</span>
                                @else
                                    <span class="rounded-full bg-danger-soft px-2.5 py-1 text-xs font-medium text-danger">Deactivated</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-4 whitespace-nowrap">
                                    @can('update', $user)
                                        <button type="button" wire:click="edit({{ $user->id }})" class="font-medium text-accent hover:underline">Edit</button>
                                    @endcan
                                    @can('delete', $user)
                                        <button type="button" wire:click="confirmDelete({{ $user->id }})" class="text-danger hover:underline">Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center text-ink-muted">
                                {{ $search !== '' ? 'No users match that search.' : 'No users yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links('pagination.livewire-simple') }}

    {{-- Create / edit modal --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="user-form-title"
             x-data @keydown.escape.window="$wire.cancel()">
            <div class="fixed inset-0 bg-ink/50" wire:click="cancel"></div>

            <form wire:submit="save" class="relative my-8 w-full max-w-md rounded-lg bg-paper p-6 shadow-xl">
                <h3 id="user-form-title" class="font-display text-lg font-semibold text-ink">
                    {{ $editingId ? 'Edit user' : 'New user' }}
                </h3>

                <div class="mt-5 space-y-5">
                    <div>
                        <x-forms.label for="user-name">Name</x-forms.label>
                        <x-forms.input wire:model="name" id="user-name" type="text" maxlength="255" required autofocus />
                        <x-forms.error for="name" />
                    </div>

                    <div>
                        <x-forms.label for="user-email">Email</x-forms.label>
                        <x-forms.input wire:model="email" id="user-email" type="email" maxlength="255" autocomplete="off" required />
                        <x-forms.error for="email" />
                        @if ($editingId)
                            <p class="mt-1.5 text-xs text-ink-faint">Changing the address marks it unverified and sends a new verification email.</p>
                        @endif
                    </div>

                    <div>
                        <x-forms.label for="user-role">Role</x-forms.label>
                        <x-forms.select wire:model="role" id="user-role" :disabled="$editingId === auth()->id()">
                            <option value="">No role (no admin access)</option>
                            @foreach ($roles as $roleName)
                                <option value="{{ $roleName }}">{{ ucfirst($roleName) }}</option>
                            @endforeach
                        </x-forms.select>
                        <x-forms.error for="role" />
                        @if ($editingId === auth()->id())
                            <p class="mt-1.5 text-xs text-ink-faint">You can't change your own role. Ask another administrator.</p>
                        @endif
                    </div>

                    <div>
                        <x-forms.label for="user-password">{{ $editingId ? 'New password' : 'Password' }}</x-forms.label>
                        <x-forms.input wire:model="password" id="user-password" type="password" autocomplete="new-password"
                                       :placeholder="$editingId ? 'Leave empty to keep the current password' : ''" />
                        <x-forms.error for="password" />
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm text-ink">
                            <input type="checkbox" wire:model="isActive" class="rounded border-line text-accent focus:ring-accent">
                            Account is active
                        </label>
                        <x-forms.error for="isActive" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-forms.button type="button" variant="secondary" wire:click="cancel">Cancel</x-forms.button>
                    <x-forms.button type="submit" wire:loading.attr="disabled" wire:target="save">Save user</x-forms.button>
                </div>
            </form>
        </div>
    @endif

    {{-- Delete confirmation --}}
    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="user-delete-title"
             x-data @keydown.escape.window="$wire.cancelDelete()">
            <div class="absolute inset-0 bg-ink/50" wire:click="cancelDelete"></div>
            <div class="relative w-full max-w-md rounded-lg bg-paper p-6 shadow-xl">
                <h3 id="user-delete-title" class="font-display text-lg font-semibold text-ink">Delete this user?</h3>
                <p class="mt-2 text-sm text-ink-muted">The account is removed permanently. Users who still own posts can't be deleted. Deactivate them instead.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-forms.button type="button" variant="secondary" wire:click="cancelDelete">Cancel</x-forms.button>
                    <x-forms.button type="button" variant="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Delete user</x-forms.button>
                </div>
            </div>
        </div>
    @endif
</div>
