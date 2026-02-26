<?php

use App\Models\Client;
use App\Models\Material;
use App\Models\Order;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
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

    $this->client = Client::factory()->create();
    $this->stage = Stage::create(['name' => 'Corte', 'default_sequence' => 1]);
    $this->material = Material::create(['name' => 'Wood', 'stock_quantity' => 100]);
});

it('can create an order with hardware and manual fields', function () {
    actingAs($this->user);

    $response = post(route('orders.store'), [
        'client_id' => $this->client->id,
        'invoice_number' => 'TEST-123',
        'lleva_herrajeria' => '1',
        'lleva_manual_armado' => '1',
        'stages' => [
            ['stage_id' => $this->stage->id, 'sequence' => 1]
        ],
        'materials' => [
            ['material_id' => $this->material->id, 'estimated_quantity' => 1],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));

    $order = Order::first();
    expect($order->lleva_herrajeria)->toBeTrue();
    expect($order->lleva_manual_armado)->toBeTrue();
});

it('can update an order and uncheck hardware and manual fields', function () {
    actingAs($this->user);

    $order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'TEST-123',
        'lleva_herrajeria' => true,
        'lleva_manual_armado' => true,
        'created_by' => $this->user->id,
    ]);

    // Update with checkboxes missing (which happens when unchecked in HTML)
    $response = put(route('orders.update', $order), [
        'invoice_number' => 'TEST-123-MOD',
        // 'lleva_herrajeria' is missing
        // 'lleva_manual_armado' is missing
        'materials' => [
            ['material_id' => $this->material->id, 'estimated_quantity' => 1],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));

    $order->refresh();
    expect($order->lleva_herrajeria)->toBeFalse();
    expect($order->lleva_manual_armado)->toBeFalse();
    expect($order->invoice_number)->toBe('TEST-123-MOD');
});

it('shows the correct values in the orders list', function () {
    actingAs($this->user);

    $order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'TEST-123',
        'lleva_herrajeria' => true,
        'lleva_manual_armado' => false,
        'created_by' => $this->user->id,
    ]);

    $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 1,
    ]);

    $response = $this->get(route('orders.index'));
    $response->assertSuccessful();
    $response->assertSee('Pendiente');
    $response->assertSee('N/A');
});
