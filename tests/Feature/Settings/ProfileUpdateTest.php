<?php

use App\Livewire\Settings\DeleteUserForm;
use App\Livewire\Settings\Profile;
use App\Models\OAuthIdentity;
use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('oauth user sees disabled email field', function () {
    $user = User::factory()->create();
    OAuthIdentity::create([
        'user_id' => $user->id,
        'provider' => 'github',
        'provider_user_id' => 'gh-123',
        'email' => $user->email,
    ]);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSee(__('Email cannot be changed for SSO/OAuth users.'));
});

test('oauth user email is not updated on save', function () {
    $user = User::factory()->create([
        'name' => 'OAuth User',
        'email' => 'oauth@example.com',
    ]);
    OAuthIdentity::create([
        'user_id' => $user->id,
        'provider' => 'oidc',
        'provider_user_id' => 'oidc-123',
        'email' => $user->email,
    ]);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('name', 'Updated Name')
        ->set('email', 'hacked@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('oauth@example.com');
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(DeleteUserForm::class)
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('oauth user can delete account without password', function () {
    $user = User::factory()->create(['password' => null]);
    OAuthIdentity::create([
        'user_id' => $user->id,
        'provider' => 'github',
        'provider_user_id' => 'gh-123',
        'email' => $user->email,
    ]);

    $this->actingAs($user);

    Livewire::test(DeleteUserForm::class)
        ->call('deleteUser')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});
