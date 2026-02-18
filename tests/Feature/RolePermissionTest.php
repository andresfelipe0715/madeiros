<?php

use App\Models\Role;
use App\Models\User;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::create(['name' => 'Custom Role']);
    $this->user = User::factory()->create(['role_id' => $this->role->id]);
});

it('denies access if no permission record exists', function () {
    expect($this->role->hasPermission('orders', 'view'))->toBeFalse();
    expect(Gate::forUser($this->user)->allows('view-orders'))->toBeFalse();
});

it('allows access when specific permission is granted', function () {
    $this->role->permissions()->create([
        'resource_type' => 'orders',
        'can_view' => true,
    ]);

    expect($this->role->hasPermission('orders', 'view'))->toBeTrue();
    expect($this->role->hasPermission('orders', 'create'))->toBeFalse();

    expect(Gate::forUser($this->user)->allows('view-orders'))->toBeTrue();
    expect(Gate::forUser($this->user)->allows('create-orders'))->toBeFalse();
});

it('handles multiple resource types independently', function () {
    $this->role->permissions()->create([
        'resource_type' => 'orders',
        'can_view' => true,
        'can_edit' => true,
    ]);

    $this->role->permissions()->create([
        'resource_type' => 'clients',
        'can_view' => true,
        'can_create' => true,
    ]);

    expect($this->role->hasPermission('orders', 'view'))->toBeTrue();
    expect($this->role->hasPermission('orders', 'create'))->toBeFalse();
    expect($this->role->hasPermission('clients', 'create'))->toBeTrue();
    expect($this->role->hasPermission('clients', 'edit'))->toBeFalse();
});

it('supports admin-style full permissions', function () {
    $this->role->permissions()->create([
        'resource_type' => 'orders',
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);

    expect($this->role->hasPermission('orders', 'view'))->toBeTrue();
    expect($this->role->hasPermission('orders', 'create'))->toBeTrue();
    expect($this->role->hasPermission('orders', 'edit'))->toBeTrue();
});

it('persists permissions correctly through seeder', function () {
    $this->artisan('db:seed', ['--class' => 'DefaultDataSeeder']);

    $adminRole = Role::where('name', 'Admin')->first();
    expect($adminRole->hasPermission('orders', 'view'))->toBeTrue();
    expect($adminRole->hasPermission('orders', 'edit'))->toBeTrue();
    expect($adminRole->hasPermission('clients', 'create'))->toBeTrue();

    $corteRole = Role::where('name', 'Empleado de corte')->first();
    expect($corteRole->hasPermission('orders', 'view'))->toBeFalse();
    expect($corteRole->hasPermission('clients', 'view'))->toBeFalse();
});
