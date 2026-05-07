<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Services\CurrentOrganization;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class OrganizationSwitcher extends Component
{
    public string $currentOrgId = '';

    public function mount(): void
    {
        $this->currentOrgId = app(CurrentOrganization::class)->id();
    }

    public function switchOrg(string $orgId): void
    {
        $org = Organization::findOrFail($orgId);

        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->belongsToOrganization($org)) {
            return;
        }

        $currentOrg = app(CurrentOrganization::class);
        $currentOrg->switchTo($org);

        $this->redirect(route('dashboard'), navigate: true);
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function getOrganizations(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return Organization::orderByDesc('is_main')->orderBy('name')->get(['id', 'name'])->toArray();
        }

        return $user->organizations()->orderByDesc('organizations.is_main')->orderBy('organizations.name')->get(['organizations.id', 'organizations.name'])->toArray();
    }

    public function render(): View
    {
        return view('livewire.organization-switcher', [
            'organizations' => $this->getOrganizations(),
        ]);
    }
}
