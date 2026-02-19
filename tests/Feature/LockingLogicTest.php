<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::create(['name' => 'Admin']);
    $this->user = User::factory()->create(['role_id' => $this->role->id]);

    // Grant permissions
    RolePermission::create([
        'role_id' => $this->role->id,
        'resource_type' => 'orders',
        'can_view' => true,
        'can_edit' => true,
        'can_create' => true,
    ]);

    RolePermission::create([
        'role_id' => $this->role->id,
        'resource_type' => 'clients',
        'can_view' => true,
        'can_edit' => true,
        'can_create' => true,
    ]);

    $this->client = Client::factory()->create([
        'name' => 'John Doe',
        'document' => '123456789',
    ]);
});

it('prevents editing an order once it has a delivery date', function () {
    actingAs($this->user);

    $order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-001',
        'material' => 'Material A',
        'delivered_at' => now(),
        'created_by' => $this->user->id,
    ]);

    // 1. Verify UI shows the locked message
    $response = get(route('orders.edit', $order));
    $response->assertSee('Esta orden no se puede editar porque ya tiene una fecha de entrega.');
    $response->assertSee('disabled');

    // 2. Verify backend prevents update
    $response = put(route('orders.update', $order), [
        'invoice_number' => 'INV-001-MOD',
        'material' => 'Material MOD',
    ]);

    $response->assertForbidden();

    $order->refresh();
    expect($order->invoice_number)->toBe('INV-001');
});

it('prevents modifying client document if they have orders', function () {
    actingAs($this->user);

    // 1. Verify document CAN be modified when no orders exist
    $response = put(route('clients.update', $this->client), [
        'name' => 'John Doe',
        'document' => '999999999',
    ]);
    $response->assertRedirect(route('clients.index'));
    $this->client->refresh();
    expect($this->client->document)->toBe('999999999');

    // 2. Create an order for the client
    Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-002',
        'material' => 'Material B',
        'created_by' => $this->user->id,
    ]);

    // 3. Verify UI disables document field
    $this->client->refresh(); // Ensure relationships are fresh
    $response = get(route('clients.edit', $this->client));
    $response->assertSee('El documento no se puede modificar');
    $response->assertSee('disabled');

    // 4. Verify backend prevents document update but allows other fields
    $response = put(route('clients.update', $this->client), [
        'name' => 'John Doe Updated',
        'document' => '888888888', // Trying to change it
    ]);

    $response->assertSessionHasErrors(['document' => 'El documento no se puede modificar porque este cliente ya tiene órdenes asociadas.']);

    $this->client->refresh();
    expect($this->client->document)->toBe('999999999'); // Remains unchanged
    expect($this->client->name)->toBe('John Doe'); // Name also not updated because validation failed for the whole request

    // 5. Verify backend allows updating other fields if document is kept same
    $response = put(route('clients.update', $this->client), [
        'name' => 'John Doe Updated',
        'document' => '999999999', // Kept same
    ]);

    $response->assertRedirect(route('clients.index'));
    $this->client->refresh();
    expect($this->client->name)->toBe('John Doe Updated');
});
