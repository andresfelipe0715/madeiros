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
        'resource_type' => 'bodega',
        'can_view' => true,
        'can_edit' => true,
    ]);

    $this->material = Material::factory()->create([
        'name' => 'Test Material',
        'bodega_quantity' => 100,
    ]);

    // Create some bodega logs
    InventoryLog::create([
        'material_id' => $this->material->id,
        'user_id' => $this->admin->id,
        'action' => 'bodega_entry',
        'previous_stock_quantity' => 0,
        'new_stock_quantity' => 100,
        'notes' => 'Initial bodega entry',
    ]);
});

it('allows authorized users to view general bodega logs', function () {
    $this->actingAs($this->admin)
        ->get(route('bodega.logs.all'))
        ->assertSuccessful()
        ->assertSee('Historial de Movimientos')
        ->assertSee('General Bodega')
        ->assertSee('Initial bodega entry');
});

it('allows authorized users to view logs for a specific material in bodega', function () {
    $this->actingAs($this->admin)
        ->get(route('bodega.logs', $this->material))
        ->assertSuccessful()
        ->assertSee('Historial de Movimientos')
        ->assertSee($this->material->name)
        ->assertSee('Initial bodega entry');
});

it('prevents unauthorized users from viewing bodega logs', function () {
    $nonAdmin = User::factory()->create();
    // No permissions granted
    
    $this->actingAs($nonAdmin)
        ->get(route('bodega.logs.all'))
        ->assertForbidden();

    $this->actingAs($nonAdmin)
        ->get(route('bodega.logs', $this->material))
        ->assertForbidden();
});
