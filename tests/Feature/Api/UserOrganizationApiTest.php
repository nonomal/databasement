<?php

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;

test('returns the authenticated user organizations with roles', function () {
    $user = User::factory()->create();
    $otherOrg = Organization::factory()->create(['name' => 'Second Org']);
    $user->organizations()->attach($otherOrg->id, ['role' => UserRole::Admin]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/user/organizations');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.role', UserRole::Member->value)
        ->assertJsonPath('data.1.name', 'Second Org')
        ->assertJsonPath('data.1.role', UserRole::Admin->value);
});

test('requires authentication', function () {
    $this->getJson('/api/v1/user/organizations')->assertUnauthorized();
});
