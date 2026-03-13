<?php

use App\Models\Material;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\InventoryLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::create(['name' => 'Admin']);
    $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);

    RolePermission::create([
        'role_id' => $this->adminRole->id,
        'resource_type' => 'materials',
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);

    $this->material = Material::factory()->create([
        'name' => 'Test Material',
        'stock_quantity' => 100,
    ]);

    // Create some logs
    InventoryLog::create([
        'material_id' => $this->material->id,
        'user_id' => $this->admin->id,
        'action' => 'adjustment',
        'previous_stock_quantity' => 50,
        'new_stock_quantity' => 100,
        'notes' => 'Initial adjustment',
    ]);
});

it('allows authorized users to view general material logs', function () {
    $this->actingAs($this->admin)
        ->get(route('materials.logs.all'))
        ->assertSuccessful()
        ->assertSee('Historial de Movimientos de Materiales')
        ->assertSee('Test Material')
        ->assertSee('Initial adjustment');
});

it('allows authorized users to view logs for a specific material', function () {
    $this->actingAs($this->admin)
        ->get(route('materials.logs', $this->material))
        ->assertSuccessful()
        ->assertSee('Historial de Movimientos de Materiales')
        ->assertSee($this->material->name)
        ->assertSee('Initial adjustment');
});

it('prevents unauthorized users from viewing material logs', function () {
    $nonAdmin = User::factory()->create();
    // No permissions granted
    
    $this->actingAs($nonAdmin)
        ->get(route('materials.logs.all'))
        ->assertForbidden();
});
