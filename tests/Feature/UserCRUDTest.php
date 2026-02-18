<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::create(['name' => 'Admin']);
    $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);

    RolePermission::create([
        'role_id' => $this->adminRole->id,
        'resource_type' => 'users',
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);
});

it('allows admin to view users list', function () {
    $response = $this->actingAs($this->admin)->get(route('users.index'));
    $response->assertSuccessful();
    $response->assertSee($this->admin->name);
});

it('allows admin to create a user', function () {
    $role = Role::create(['name' => 'Employee']);

    $response = $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'John Doe',
        'document' => '123456789',
        'password' => 'pass12',
        'password_confirmation' => 'pass12',
        'role_id' => $role->id,
        'active' => 1,
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', ['name' => 'John Doe', 'document' => '123456789']);
});

it('locks document field if user has orders', function () {
    $employee = User::factory()->create(['role_id' => $this->adminRole->id]);

    // Create an order associated with this user
    Order::factory()->create([
        'created_by' => $employee->id,
        'client_id' => \App\Models\Client::factory()->create()->id,
    ]);

    $response = $this->actingAs($this->admin)->put(route('users.update', $employee), [
        'name' => 'Updated Name',
        'document' => 'NEW_DOC_123', // Trying to change document
        'role_id' => $this->adminRole->id,
        'active' => 1,
    ]);

    $response->assertSessionHasErrors('document');
    $this->assertDatabaseHas('users', ['id' => $employee->id, 'document' => $employee->document]);
});

it('denies access to non-authorized roles', function () {
    $badRole = Role::create(['name' => 'Limited']);
    $badUser = User::factory()->create(['role_id' => $badRole->id]);
    // No 'users' permission for this role

    $this->actingAs($badUser)->get(route('users.index'))->assertForbidden();
    $this->actingAs($badUser)->post(route('users.store'), [
        'name' => 'Unauthorized User',
        'document' => '999999',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role_id' => $badRole->id,
        'active' => 1,
    ])->assertForbidden();
});
