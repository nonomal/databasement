<?php

namespace App\Livewire\User;

use App\Enums\UserRole;
use App\Livewire\Forms\UserForm;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\CurrentOrganization;
use App\Traits\Toast;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Add User')]
class Create extends Component
{
    use AuthorizesRequests, Toast;

    public UserForm $form;

    public string $mode = 'invite';

    public string $existingUserId = '';

    public string $existingUserRole = UserRole::Member->value;

    public bool $showCopyModal = false;

    public string $invitationUrl = '';

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $user = $this->form->store();

        $this->invitationUrl = $user->getInvitationUrl();
        $this->showCopyModal = true;
    }

    public function addExisting(CurrentOrganization $currentOrg): void
    {
        $this->authorize('manageOrgMembership', User::class);

        $this->validate([
            'existingUserId' => 'required|exists:users,id',
            'existingUserRole' => 'required|'.UserRole::validationRule(),
        ]);

        $user = User::findOrFail($this->existingUserId);

        if ($user->belongsToOrganization($currentOrg->model())) {
            $this->addError('existingUserId', __('This user is already a member of this organization.'));

            return;
        }

        $user->organizations()->attach($currentOrg->id(), ['role' => $this->existingUserRole]);

        $this->success(
            title: __('User added to organization.'),
            redirectTo: route('users.index')
        );
    }

    public function closeAndRedirect(): void
    {
        $this->success(
            title: __('User created successfully!'),
            redirectTo: route('users.index')
        );
    }

    /**
     * @return array<int, array{id: string|int, name: string}>
     */
    #[Computed]
    public function availableUsers(): array
    {
        $currentOrg = app(CurrentOrganization::class);

        return User::withoutGlobalScope(OrganizationScope::class)
            ->whereDoesntHave('organizations', function ($query) use ($currentOrg) {
                $query->where('organizations.id', $currentOrg->id());
            })
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => ['id' => $user->id, 'name' => "{$user->name} ({$user->email})"])
            ->all();
    }

    #[Computed]
    public function hasMultipleOrganizations(): bool
    {
        return Organization::count() > 1;
    }

    public function render(): View
    {
        return view('livewire.user.create', [
            'roleOptions' => $this->form->roleOptions(),
            'availableUsers' => $this->availableUsers(),
        ]);
    }
}
