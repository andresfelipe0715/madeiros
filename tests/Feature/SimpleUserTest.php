<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a user with a role and permissions', function () {
    $role = Role::create(['name' => 'Test Role']);
    $user = \App\Models\User::factory()->create(['role_id' => $role->id]);

    $role->orderPermission()->create(['can_view' => true, 'can_edit' => true, 'can_create' => true]);
    $role->clientPermission()->create(['can_view' => true, 'can_create' => true, 'can_edit' => true]);

    $role->refresh();

    expect($user->role_id)->toBe($role->id);
    expect($role->orderPermission)->not->toBeNull();
    expect($role->orderPermission->can_view)->toBeTrue();
});
