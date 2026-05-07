<div>
    <!-- HEADER with filters (Desktop) -->
    <x-header title="{{ __('Users') }}" separator progress-indicator>
        <x-slot:actions>
            <div class="hidden lg:flex items-center gap-2">
                @include('livewire.user._filters', ['variant' => 'desktop'])
            </div>
            @can('create', App\Models\User::class)
                <x-button label="{{ __('Add User') }}" link="{{ route('users.create') }}" icon="o-plus" class="btn-primary btn-sm" wire:navigate />
            @endcan
        </x-slot:actions>
    </x-header>

    <!-- FILTERS (Tablet & Mobile) -->
    <div class="lg:hidden mb-4" x-data="{ showFilters: false }">
        @include('livewire.user._filters', ['variant' => 'mobile'])
    </div>

    <!-- TABLE -->
    <x-card shadow>
        <x-table :headers="$headers" :rows="$users" :sort-by="$sortBy" with-pagination>
            <x-slot:empty>
                <div class="text-center text-base-content/50 py-8">
                    @if($search || $roleFilter !== '' || $statusFilter !== '')
                        {{ __('No users found matching your filters.') }}
                    @else
                        {{ __('No users yet.') }}
                    @endif
                </div>
            </x-slot:empty>

            @scope('cell_name', $user)
                <div class="flex items-center gap-2">
                    <div class="table-cell-primary">{{ $user->name }}</div>
                    @if($user->id === auth()->id())
                        <span class="text-xs text-base-content/50">{{ __('(You)') }}</span>
                    @endif
                    @if($user->isOAuth())
                        <x-badge value="OAuth" class="badge-ghost badge-sm" />
                    @endif
                </div>
            @endscope

            @scope('cell_email', $user)
                {{ $user->email }}
            @endscope

            @scope('cell_role', $user)
                @php
                    $currentOrg = app(\App\Services\CurrentOrganization::class);
                    $orgRole = $user->roleIn($currentOrg->model());
                    $displayRole = $orgRole ?? \App\Enums\UserRole::Member;
                @endphp
                <div class="flex flex-wrap items-center gap-1">
                    @if($user->isSuperAdmin())
                        <x-badge value="Super Admin" icon="o-star" class="badge-warning whitespace-nowrap" />
                    @endif
                    @if($orgRole)
                        <x-badge :value="$displayRole->label()" :icon="$displayRole->icon()" class="{{ $displayRole->badgeClass() }}" />
                    @endif
                </div>
            @endscope

            @scope('cell_status', $user)
                @if($user->isActive())
                    <x-badge value="{{ __('Active') }}" class="badge-success" />
                @else
                    <x-badge value="{{ __('Pending') }}" class="badge-warning" />
                @endif
            @endscope

            @scope('cell_created_at', $user)
                <div class="table-cell-primary">{{ \App\Support\Formatters::humanDate($user->created_at) }}</div>
                <div class="text-sm text-base-content/70">{{ $user->created_at->diffForHumans() }}</div>
            @endscope

            @scope('actions', $user)
                <div class="flex gap-2 justify-end">
                    @can('copyInvitationLink', $user)
                        <x-button
                            icon="o-clipboard-document"
                            wire:click="copyInvitationLink({{ $user->id }})"
                            tooltip="{{ __('Copy Invitation Link') }}"
                            class="btn-ghost btn-sm text-info"
                        />
                    @endcan
                    @can('update', $user)
                        <x-button
                            icon="o-pencil"
                            link="{{ route('users.edit', $user) }}"
                            wire:navigate
                            tooltip="{{ __('Edit') }}"
                            class="btn-ghost btn-sm"
                        />
                    @endcan
                    @can('removeFromOrganization', $user)
                        <x-button
                            icon="o-user-minus"
                            wire:click="confirmRemoveFromOrg({{ $user->id }})"
                            tooltip="{{ __('Remove from organization') }}"
                            class="btn-ghost btn-sm text-warning"
                        />
                    @endcan
                    @can('delete', $user)
                        <x-button
                            icon="o-trash"
                            wire:click="confirmDelete({{ $user->id }})"
                            tooltip="{{ __('Delete') }}"
                            class="btn-ghost btn-sm text-error"
                        />
                    @endcan
                </div>
            @endscope
        </x-table>
    </x-card>

    <!-- DELETE CONFIRMATION MODAL -->
    <x-delete-confirmation-modal
        :title="__('Delete User')"
        :message="__('Are you sure you want to delete this user? This action cannot be undone.')"
        onConfirm="delete"
    >
        <x-alert icon="o-information-circle" class="alert-info mt-4">
            {!! __('Database servers, backups, snapshots, and other resources created by this user <strong>WILL NOT</strong> be deleted and will remain accessible.') !!}
        </x-alert>
    </x-delete-confirmation-modal>

    <!-- REMOVE FROM ORG CONFIRMATION MODAL -->
    <x-modal wire:model="showRemoveModal" :title="__('Remove User from Organization')" class="backdrop-blur">
        <p>{{ __('Are you sure you want to remove this user from the current organization? The user will retain access to other organizations they belong to.') }}</p>

        <x-slot:actions>
            <x-button :label="__('Cancel')" @click="$wire.showRemoveModal = false" />
            <x-button :label="__('Remove')" class="btn-warning" wire:click="removeFromOrg" spinner="removeFromOrg" />
        </x-slot:actions>
    </x-modal>

    <!-- COPY INVITATION LINK MODAL -->
    <x-invitation-link-modal
        :title="__('Invitation Link')"
        :message="__('Copy this link and send it to the user so they can complete their registration.')"
        :doneLabel="__('Close')"
    />
</div>
