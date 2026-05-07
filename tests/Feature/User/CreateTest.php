<?php

use App\Enums\UserRole;
use App\Livewire\User\Create;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
});

describe('access control', function () {
    test('super admin can access create page', function () {
        actingAs($this->admin);

        get(route('users.create'))->assertOk();
    });

    test('org admin can access create page', function () {
        $orgAdmin = User::factory()->admin()->create();
        actingAs($orgAdmin);

        get(route('users.create'))->assertOk();
    });

    test('non-admin cannot access create page', function (string $role) {
        $user = User::factory()->create(['role' => $role]);
        actingAs($user);

        get(route('users.create'))->assertForbidden();
    })->with(['member', 'viewer']);
});

describe('invite new user', function () {
    test('creates user with invitation token attached to current org', function () {
        actingAs($this->admin);

        Livewire::test(Create::class)
            ->set('form.name', 'New User')
            ->set('form.email', 'newuser@example.com')
            ->set('form.role', UserRole::Member->value)
            ->call('save')
            ->assertSet('showCopyModal', true)
            ->assertSet('invitationUrl', fn ($url) => str_contains($url, '/invitation/'));

        $user = User::where('email', 'newuser@example.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('New User')
            ->and($user->roleIn(Organization::default()))->toBe(UserRole::Member)
            ->and($user->invitation_token)->not->toBeNull()
            ->and($user->password)->toBeNull();
    });

    test('super admin can set super admin flag', function () {
        actingAs($this->admin);

        Livewire::test(Create::class)
            ->set('form.name', 'New Super Admin')
            ->set('form.email', 'superadmin@example.com')
            ->set('form.role', UserRole::Admin->value)
            ->set('form.superAdmin', true)
            ->call('save');

        $user = User::where('email', 'superadmin@example.com')->first();
        expect($user->super_admin)->toBeTrue();
    });

    test('non-super-admin cannot set super admin flag', function () {
        $orgAdmin = User::factory()->admin()->create();
        actingAs($orgAdmin);

        Livewire::test(Create::class)
            ->set('form.name', 'Attempted Super')
            ->set('form.email', 'attempted@example.com')
            ->set('form.role', UserRole::Admin->value)
            ->set('form.superAdmin', true)
            ->call('save');

        $user = User::where('email', 'attempted@example.com')->first();
        expect($user->super_admin)->toBeFalse();
    });
});

describe('add existing user', function () {
    test('admin can add existing user to current organization', function () {
        actingAs($this->admin);

        $otherOrg = Organization::factory()->create();
        $existingUser = User::factory()->create();
        // Move user to other org only (detach from main)
        $existingUser->organizations()->sync([$otherOrg->id => ['role' => UserRole::Member]]);

        Livewire::test(Create::class)
            ->set('existingUserId', $existingUser->id)
            ->set('existingUserRole', UserRole::Viewer->value)
            ->call('addExisting')
            ->assertHasNoErrors();

        expect($existingUser->roleIn(Organization::default()))->toBe(UserRole::Viewer);
    });

    test('rejects adding user already in organization', function () {
        actingAs($this->admin);

        $existingUser = User::factory()->create();

        Livewire::test(Create::class)
            ->set('existingUserId', $existingUser->id)
            ->set('existingUserRole', UserRole::Member->value)
            ->call('addExisting')
            ->assertHasErrors('existingUserId');
    });
});
