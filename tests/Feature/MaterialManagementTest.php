<?php

use App\Models\Client;
use App\Models\Material;
use App\Models\Order;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::create(['name' => 'Admin']);
    $this->user = User::factory()->create(['role_id' => $this->role->id]);

    // Grant permissions
    RolePermission::create([
        'role_id' => $this->role->id,
        'resource_type' => 'orders',
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);

    $this->client = Client::factory()->create();
    $this->stage = Stage::factory()->create(['name' => 'Producción']);
    $this->materialA = Material::factory()->create(['name' => 'Material A', 'stock_quantity' => 100, 'reserved_quantity' => 0]);
    $this->materialB = Material::factory()->create(['name' => 'Material B', 'stock_quantity' => 100, 'reserved_quantity' => 0]);
});

it('releases stock when a material is cancelled', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    // Reserve Material A
    $order->orderMaterials()->create([
        'material_id' => $this->materialA->id,
        'estimated_quantity' => 10,
    ]);
    $this->materialA->increment('reserved_quantity', 10);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'INV-001',
            'materials' => [
                [
                    'id' => $order->orderMaterials->first()->id,
                    'material_id' => $this->materialA->id,
                    'estimated_quantity' => 10,
                    'cancelled' => true,
                    'notes' => 'Cancelling this',
                ],
            ],
        ]);

    expect((float) $this->materialA->fresh()->reserved_quantity)->toBe(0.0);
    expect($order->orderMaterials()->whereNotNull('cancelled_at')->count())->toBe(1);
});

it('transfers stock when switching materials', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    // Reserve Material A
    $order->orderMaterials()->create([
        'material_id' => $this->materialA->id,
        'estimated_quantity' => 10,
    ]);
    $this->materialA->increment('reserved_quantity', 10);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'INV-001',
            'materials' => [
                [
                    'id' => $order->orderMaterials->first()->id,
                    'material_id' => $this->materialB->id, // Switch to B
                    'estimated_quantity' => 15,
                    'notes' => 'Switched',
                ],
            ],
        ]);

    expect((float) $this->materialA->fresh()->reserved_quantity)->toBe(0.0);
    expect((float) $this->materialB->fresh()->reserved_quantity)->toBe(15.0);

    $om = $order->orderMaterials->first()->fresh();
    expect($om->material_id)->toBe($this->materialB->id);
    expect((float) $om->estimated_quantity)->toBe(15.0);
});

it('hides cancelled materials from production views', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $orderStage = $order->orderStages()->create(['stage_id' => $this->stage->id, 'sequence' => 1]);

    $order->orderMaterials()->create([
        'material_id' => $this->materialA->id,
        'estimated_quantity' => 10,
        'cancelled_at' => now(), // Already cancelled
    ]);

    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertDontSee('Material A');
});

it('prevents modifications once order is delivered', function () {
    $order = Order::factory()->create([
        'client_id' => $this->client->id,
        'created_by' => $this->user->id,
        'delivered_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'INV-001',
            'materials' => [
                [
                    'material_id' => $this->materialA->id,
                    'estimated_quantity' => 10,
                ],
            ],
        ]);

    $response->assertSessionHas('error', 'No se pueden modificar materiales de una orden ya entregada.');
});

it('restores a cancelled material instead of duplicating it', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    // Create a cancelled material
    $om = $order->orderMaterials()->create([
        'material_id' => $this->materialA->id,
        'estimated_quantity' => 10,
        'cancelled_at' => now(),
    ]);

    // Submit the same ID but with cancelled = false
    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'INV-001',
            'materials' => [
                [
                    'id' => $om->id,
                    'material_id' => $this->materialA->id,
                    'estimated_quantity' => 12, // Update quantity too
                    'cancelled' => false,
                    'notes' => 'Restored',
                ],
            ],
        ]);

    $om->refresh();
    expect($om->cancelled_at)->toBeNull();
    expect((float) $om->estimated_quantity)->toBe(12.0);
    expect($order->orderMaterials()->count())->toBe(1); // No duplicates
    expect((float) $this->materialA->fresh()->reserved_quantity)->toBe(12.0);
});

it('prevents mass-cancellation when saving unrelated changes', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    $order->orderMaterials()->create([
        'material_id' => $this->materialA->id,
        'estimated_quantity' => 10,
    ]);
    $this->materialA->increment('reserved_quantity', 10);

    // Simulate payload from problematic Alpine/Blade where cancelled might be "0" or false
    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'INV-UPDATED',
            'materials' => [
                [
                    'id' => $order->orderMaterials->first()->id,
                    'material_id' => $this->materialA->id,
                    'estimated_quantity' => 10,
                    'cancelled' => '0', // String "0" which previously caused issues
                ],
            ],
        ]);

    expect($order->orderMaterials->first()->fresh()->cancelled_at)->toBeNull();
});

it('handles mixed state: cancelling one and adding another independently', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    $om1 = $order->orderMaterials()->create([
        'material_id' => $this->materialA->id,
        'estimated_quantity' => 10,
    ]);
    $this->materialA->increment('reserved_quantity', 10);

    $om2 = $order->orderMaterials()->create([
        'material_id' => $this->materialB->id,
        'estimated_quantity' => 20,
    ]);
    $this->materialB->increment('reserved_quantity', 20);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'INV-MIXED',
            'materials' => [
                [
                    'id' => $om1->id,
                    'material_id' => $this->materialA->id,
                    'estimated_quantity' => 10,
                    'cancelled' => true, // Cancel A
                ],
                [
                    'id' => $om2->id,
                    'material_id' => $this->materialB->id,
                    'estimated_quantity' => 20,
                    'cancelled' => false, // Keep B active
                ],
                [ // Add C
                    'material_id' => $this->materialA->id,
                    'estimated_quantity' => 5,
                    'notes' => 'New add',
                ],
            ],
        ]);

    expect($om1->fresh()->cancelled_at)->not->toBeNull();
    expect($om2->fresh()->cancelled_at)->toBeNull();
    expect($order->orderMaterials()->active()->count())->toBe(2); // B and new C
    expect((float) $this->materialA->fresh()->reserved_quantity)->toBe(5.0); // 10 released, 5 reserved
    expect((float) $this->materialB->fresh()->reserved_quantity)->toBe(20.0); // Unchanged
});

it('allows adding new materials after all previous were cancelled', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    $om1 = $order->orderMaterials()->create([
        'material_id' => $this->materialA->id,
        'estimated_quantity' => 10,
    ]);
    $this->materialA->increment('reserved_quantity', 10);

    // Cancel existing AND add new
    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'INV-RECOVERY',
            'materials' => [
                [
                    'id' => $om1->id,
                    'material_id' => $this->materialA->id,
                    'estimated_quantity' => 10,
                    'cancelled' => true,
                ],
                [
                    'material_id' => $this->materialB->id,
                    'estimated_quantity' => 15,
                ],
            ],
        ]);

    expect($om1->fresh()->cancelled_at)->not->toBeNull();
    expect($order->orderMaterials()->active()->count())->toBe(1);
    expect($order->orderMaterials()->active()->first()->material_id)->toBe($this->materialB->id);
    expect((float) $this->materialA->fresh()->reserved_quantity)->toBe(0.0);
    expect((float) $this->materialB->fresh()->reserved_quantity)->toBe(15.0);
});
