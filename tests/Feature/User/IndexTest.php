<?php

use App\Enums\UserRole;
use App\Livewire\User\Index;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
});

describe('access control', function () {
    test('super admin can access user index', function () {
        actingAs($this->admin);

        Livewire::test(Index::class)
            ->assertOk();
    });

    test('org admin can access user index', function () {
        $orgAdmin = User::factory()->admin()->create();
        actingAs($orgAdmin);

        Livewire::test(Index::class)
            ->assertOk();
    });

    test('non-admin cannot access user index', function (string $role) {
        $user = User::factory()->create(['role' => $role]);
        actingAs($user);

        Livewire::test(Index::class)
            ->assertForbidden();
    })->with(['member', 'viewer']);
});

test('displays users in current organization', function () {
    actingAs($this->admin);

    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Smith']);

    Livewire::test(Index::class)
        ->assertSee('John Doe')
        ->assertSee('Jane Smith');
});

test('can search users by name', function () {
    actingAs($this->admin);

    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    Livewire::test(Index::class)
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

describe('delete', function () {
    test('delete authorization', function (string $actorType, bool $targetSuperAdmin, int $targetOrgCount, bool $allowed) {
        $actor = $actorType === 'super_admin'
            ? $this->admin
            : User::factory()->admin()->create();

        $target = $targetSuperAdmin
            ? User::factory()->superAdmin()->create()
            : User::factory()->create();

        if ($targetOrgCount > 1) {
            $secondOrg = Organization::factory()->create();
            $target->organizations()->attach($secondOrg->id, ['role' => UserRole::Member]);
        }

        actingAs($actor);

        $component = Livewire::test(Index::class)
            ->call('confirmDelete', $target->id);

        if ($allowed) {
            $component->assertSet('showDeleteModal', true)
                ->call('delete')
                ->assertSet('showDeleteModal', false);
            expect(User::find($target->id))->toBeNull();
        } else {
            $component->assertForbidden();
        }
    })->with([
        'super admin deletes regular user (1 org)' => ['super_admin', false, 1, true],
        'super admin deletes regular user (2 orgs)' => ['super_admin', false, 2, true],
        'super admin deletes super admin (not last)' => ['super_admin', true, 1, true],
        'org admin deletes regular user (1 org)' => ['admin', false, 1, true],
        'org admin cannot delete user in multiple orgs' => ['admin', false, 2, false],
        'org admin cannot delete super admin' => ['admin', true, 1, false],
    ]);

    test('cannot delete yourself', function () {
        actingAs($this->admin);

        Livewire::test(Index::class)
            ->call('confirmDelete', $this->admin->id)
            ->assertForbidden();
    });
});

describe('remove from organization', function () {
    test('remove from org authorization', function (string $actorType, bool $targetSuperAdmin, int $targetOrgCount, bool $allowed) {
        $actor = $actorType === 'super_admin'
            ? $this->admin
            : User::factory()->admin()->create();

        $target = $targetSuperAdmin
            ? User::factory()->superAdmin()->create()
            : User::factory()->create();

        if ($targetOrgCount > 1) {
            $secondOrg = Organization::factory()->create();
            $target->organizations()->attach($secondOrg->id, ['role' => UserRole::Member]);
        }

        actingAs($actor);

        $component = Livewire::test(Index::class)
            ->call('confirmRemoveFromOrg', $target->id);

        if ($allowed) {
            $component->assertSet('showRemoveModal', true)
                ->call('removeFromOrg')
                ->assertSet('showRemoveModal', false);

            // User still exists but is no longer in the current org
            expect(User::find($target->id))->not->toBeNull();
            $currentOrgId = app(\App\Services\CurrentOrganization::class)->id();
            expect($target->fresh()->organizations()->where('organization_id', $currentOrgId)->exists())->toBeFalse();
        } else {
            $component->assertForbidden();
        }
    })->with([
        'super admin removes user (2 orgs)' => ['super_admin', false, 2, true],
        'org admin removes user (2 orgs)' => ['admin', false, 2, true],
        'cannot remove user in single org (super admin)' => ['super_admin', false, 1, false],
        'cannot remove user in single org (org admin)' => ['admin', false, 1, false],
        'org admin cannot remove super admin' => ['admin', true, 2, false],
    ]);

    test('cannot remove yourself from org', function () {
        actingAs($this->admin);

        Livewire::test(Index::class)
            ->call('confirmRemoveFromOrg', $this->admin->id)
            ->assertForbidden();
    });
});

describe('invitation link', function () {
    test('super admin can copy invitation link for pending user', function () {
        actingAs($this->admin);

        $pendingUser = User::factory()->create([
            'invitation_token' => 'test-token-123',
            'invitation_accepted_at' => null,
        ]);

        Livewire::test(Index::class)
            ->call('copyInvitationLink', $pendingUser->id)
            ->assertSet('showCopyModal', true)
            ->assertSet('invitationUrl', route('invitation.accept', 'test-token-123'));
    });

    test('org admin can copy invitation link for pending user in their org', function () {
        $orgAdmin = User::factory()->admin()->create();
        actingAs($orgAdmin);

        $pendingUser = User::factory()->create([
            'invitation_token' => 'test-token-456',
            'invitation_accepted_at' => null,
        ]);

        Livewire::test(Index::class)
            ->call('copyInvitationLink', $pendingUser->id)
            ->assertSet('showCopyModal', true)
            ->assertSet('invitationUrl', route('invitation.accept', 'test-token-456'));
    });
});
