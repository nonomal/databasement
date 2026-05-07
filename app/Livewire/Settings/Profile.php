<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Traits\Toast;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profile')]
class Profile extends Component
{
    use Toast;

    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $isOAuth = $user->isOAuth();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        if (! $isOAuth) {
            $rules['email'] = [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ];
        }

        $validated = $this->validate($rules);

        $user->fill($validated);

        $user->save();

        $this->success(__('Profile updated successfully.'));
    }

    public function render(): View
    {
        return view('livewire.settings.profile', [
            'isOAuthUser' => Auth::user()->isOAuth(),
        ]);
    }
}
