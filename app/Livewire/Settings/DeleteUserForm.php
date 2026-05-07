<?php

namespace App\Livewire\Settings;

use App\Livewire\Actions\Logout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteUserForm extends Component
{
    public string $password = '';

    public bool $showDeleteModal = false;

    public function deleteUser(Logout $logout): void
    {
        if (! Auth::user()->isOAuth()) {
            $this->validate([
                'password' => ['required', 'string', 'current_password'],
            ]);
        }

        tap(Auth::user(), $logout(...))->delete();

        session()->flash('status', __('Your account has been deleted.'));

        $this->redirect(route('login'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.settings.delete-user-form', [
            'isOAuthUser' => Auth::user()->isOAuth(),
        ]);
    }
}
