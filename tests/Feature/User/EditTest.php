<?php

use App\Enums\UserRole;
use App\Livewire\User\Edit;
use App\Models\OAuthIdentity;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
});

describe('access control', function () {
    test('super admin can edit any user', function () {
        actingAs($this->admin);

        $user = User::factory()->create();

        get(route('users.edit', $user))->assertOk();
    });

    test('org admin can edit non-super-admin users in their org', function () {
        $orgAdmin = User::factory()->admin()->create();
        actingAs($orgAdmin);

        $user = User::factory()->create();

        get(route('users.edit', $user))->assertOk();
    });

    test('org admin cannot edit super admin users', function () {
        $orgAdmin = User::factory()->admin()->create();
        actingAs($orgAdmin);

        get(route('users.edit', $this->admin))->assertForbidden();
    });

    test('org admin cannot edit users outside their org', function () {
        $orgAdmin = User::factory()->admin()->create();
        actingAs($orgAdmin);

        $otherOrg = Organization::factory()->create();
        $outsideUser = User::factory()->create();
        $outsideUser->organizations()->sync([$otherOrg->id => ['role' => UserRole::Member]]);

        get(route('users.edit', $outsideUser))->assertForbidden();
    });

    test('non-admin cannot edit users', function (string $role) {
        $user = User::factory()->create(['role' => $role]);
        $target = User::factory()->create();
        actingAs($user);

        get(route('users.edit', $target))->assertForbidden();
    })->with(['member', 'viewer']);
});

test('can update user name email and role', function () {
    actingAs($this->admin);

    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'role' => UserRole::Member,
    ]);

    Livewire::test(Edit::class, ['user' => $user])
        ->set('form.name', 'Updated Name')
        ->set('form.email', 'updated@example.com')
        ->set('form.role', UserRole::Viewer->value)
        ->call('save')
        ->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('updated@example.com')
        ->and($user->roleIn(Organization::default()))->toBe(UserRole::Viewer);
});

test('cannot remove last super admin', function () {
    actingAs($this->admin);

    expect(User::where('super_admin', true)->count())->toBe(1);

    Livewire::test(Edit::class, ['user' => $this->admin])
        ->set('form.superAdmin', false)
        ->call('save')
        ->assertNoRedirect();

    $this->admin->refresh();
    expect($this->admin->super_admin)->toBeTrue();
});

test('can demote super admin when multiple exist', function () {
    actingAs($this->admin);

    $anotherAdmin = User::factory()->superAdmin()->create();
    expect(User::where('super_admin', true)->count())->toBe(2);

    Livewire::test(Edit::class, ['user' => $anotherAdmin])
        ->set('form.role', UserRole::Member->value)
        ->call('save')
        ->assertRedirect(route('users.index'));

    $anotherAdmin->refresh();
    expect($anotherAdmin->roleIn(Organization::default()))->toBe(UserRole::Member);
});

test('can promote user to admin', function () {
    actingAs($this->admin);

    $member = User::factory()->create(['role' => UserRole::Member]);

    Livewire::test(Edit::class, ['user' => $member])
        ->set('form.role', UserRole::Admin->value)
        ->call('save')
        ->assertRedirect(route('users.index'));

    $member->refresh();
    expect($member->roleIn(Organization::default()))->toBe(UserRole::Admin);
});

test('oauth user email field is disabled', function () {
    actingAs($this->admin);

    $user = User::factory()->create();
    OAuthIdentity::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_user_id' => 'oauth-123',
        'email' => $user->email,
    ]);

    Livewire::test(Edit::class, ['user' => $user])
        ->assertSee(__('Email cannot be changed for SSO/OAuth users.'));
});

test('oauth user email is not updated on save', function () {
    actingAs($this->admin);

    $user = User::factory()->create([
        'name' => 'OAuth User',
        'email' => 'oauth@example.com',
    ]);
    OAuthIdentity::create([
        'user_id' => $user->id,
        'provider' => 'oidc',
        'provider_user_id' => 'oauth-456',
        'email' => $user->email,
    ]);

    Livewire::test(Edit::class, ['user' => $user])
        ->set('form.name', 'Updated Name')
        ->set('form.email', 'hacked@example.com')
        ->call('save')
        ->assertRedirect(route('users.index'));

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('oauth@example.com');
});
