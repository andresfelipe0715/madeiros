<?php

use App\Models\Material;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;

beforeEach(function () {
    $this->adminRole = Role::create(['name' => 'Admin']);
    $this->bodegaRole = Role::create(['name' => 'Bodega']);

    // Admin: Full permissions on both
    RolePermission::create([
        'role_id' => $this->adminRole->id,
        'resource_type' => 'materials',
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);
    RolePermission::create([
        'role_id' => $this->adminRole->id,
        'resource_type' => 'bodega',
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);

    // Bodega: Only view on materials, full bodega permissions (matches DefaultDataSeeder)
    RolePermission::create([
        'role_id' => $this->bodegaRole->id,
        'resource_type' => 'materials',
        'can_view' => true,
        'can_create' => false,
        'can_edit' => false,
    ]);
    RolePermission::create([
        'role_id' => $this->bodegaRole->id,
        'resource_type' => 'bodega',
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);

    $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);
    $this->bodegaUser = User::factory()->create(['role_id' => $this->bodegaRole->id]);

    $this->material = Material::create([
        'name' => 'Material Test',
        'stock_quantity' => 10,
        'bodega_quantity' => 20,
    ]);
});

it('allows admin to access bodega index', function () {
    $this->actingAs($this->admin);
    $this->get(route('bodega.index'))->assertSuccessful();
});

it('allows bodega user to access bodega index', function () {
    $this->actingAs($this->bodegaUser);
    $this->get(route('bodega.index'))->assertSuccessful();
});

it('allows admin to transfer from bodega to stock', function () {
    $this->actingAs($this->admin);

    $this->post(route('bodega.transfer', $this->material), ['quantity' => 5])
        ->assertStatus(302);

    $this->material->refresh();
    expect((float) $this->material->bodega_quantity)->toBe(15.0);
    expect((float) $this->material->stock_quantity)->toBe(15.0);

    $log = \App\Models\InventoryLog::where('action', 'transfer')->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->material_id)->toBe($this->material->id);
    expect($log->user_id)->toBe($this->admin->id);
    expect((float) $log->previous_stock_quantity)->toBe(10.0);
    expect((float) $log->new_stock_quantity)->toBe(15.0);
});

it('allows bodega user to transfer from bodega to stock', function () {
    $this->actingAs($this->bodegaUser);

    $this->post(route('bodega.transfer', $this->material), ['quantity' => 5])
        ->assertStatus(302);

    $this->material->refresh();
    expect((float) $this->material->bodega_quantity)->toBe(15.0);
    expect((float) $this->material->stock_quantity)->toBe(15.0);

    $log = \App\Models\InventoryLog::where('action', 'transfer')->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->material_id)->toBe($this->material->id);
    expect($log->user_id)->toBe($this->bodegaUser->id);
    expect((float) $log->previous_stock_quantity)->toBe(10.0);
    expect((float) $log->new_stock_quantity)->toBe(15.0);
});

it('allows bodega user to update bodega_quantity via bodega edit route and creates log', function () {
    $this->actingAs($this->bodegaUser);

    $this->put(route('bodega.update', $this->material), [
        'bodega_quantity' => 50,
        'notes' => 'Ajuste de prueba',
    ])->assertStatus(302);

    $this->material->refresh();
    expect((float) $this->material->bodega_quantity)->toBe(50.0);
    expect($this->material->name)->toBe('Material Test');
    expect((float) $this->material->stock_quantity)->toBe(10.0);

    $log = \App\Models\InventoryLog::where('action', 'bodega_adjustment')->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->notes)->toBe('Ajuste de prueba');
    expect((float) $log->previous_stock_quantity)->toBe(20.0);
    expect((float) $log->new_stock_quantity)->toBe(50.0);
});

it('allows bodega user to register a new entry and creates log', function () {
    $this->actingAs($this->bodegaUser);

    $this->post(route('bodega.store-entry', $this->material), [
        'quantity' => 10,
        'notes' => 'Factura #123',
    ])->assertStatus(302);

    $this->material->refresh();
    expect((float) $this->material->bodega_quantity)->toBe(30.0); // 20 + 10

    $log = \App\Models\InventoryLog::where('action', 'bodega_entry')->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->notes)->toBe('Factura #123');
    expect((float) $log->previous_stock_quantity)->toBe(20.0);
    expect((float) $log->new_stock_quantity)->toBe(30.0);
});

it('shows the bodega entry form', function () {
    $this->actingAs($this->bodegaUser);
    $this->get(route('bodega.entry', $this->material))
        ->assertSuccessful()
        ->assertSee('Registrar Ingreso')
        ->assertSee('Material Test');
});

it('shows the general bodega logs history', function () {
    $this->actingAs($this->bodegaUser);
    $this->get(route('bodega.logs.all'))
        ->assertSuccessful()
        ->assertSee('Historial de Movimientos')
        ->assertSee('(General Bodega)');
});

it('shows the specific material logs history', function () {
    $this->actingAs($this->bodegaUser);
    $this->get(route('bodega.logs', $this->material))
        ->assertSuccessful()
        ->assertSee('Historial de Movimientos')
        ->assertSee('Material Test');
});

it('forbids bodega user from accessing material edit form', function () {
    $this->actingAs($this->bodegaUser);
    $this->get(route('materials.edit', $this->material))->assertForbidden();
});

it('forbids bodega user from updating materials via materials update route', function () {
    $this->actingAs($this->bodegaUser);
    $this->put(route('materials.update', $this->material), [
        'name' => 'Attempted Hack',
        'stock_quantity' => 9999,
    ])->assertForbidden();
});

it('forbids bodega user from accessing material create form', function () {
    $this->actingAs($this->bodegaUser);
    $this->get(route('materials.create'))->assertForbidden();
});

it('forbids bodega user from creating materials via POST', function () {
    $this->actingAs($this->bodegaUser);
    $this->post(route('materials.store'), [
        'name' => 'Hacked Material',
        'stock_quantity' => 999,
    ])->assertForbidden();
});

it('validates quantity cannot exceed bodega quantity during transfer', function () {
    $this->actingAs($this->admin);
    $this->post(route('bodega.transfer', $this->material), [
        'quantity' => 100, // exceeds 20
    ])->assertSessionHasErrors('quantity');
});

it('validates transfer quantity must be positive', function () {
    $this->actingAs($this->admin);
    $this->post(route('bodega.transfer', $this->material), [
        'quantity' => -5,
    ])->assertSessionHasErrors('quantity');
});
