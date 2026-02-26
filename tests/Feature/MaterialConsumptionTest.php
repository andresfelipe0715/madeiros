<?php

use App\Models\Client;
use App\Models\Material;
use App\Models\Order;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::factory()->create();
    $this->user = User::factory()->create(['role_id' => $this->role->id]);
    $this->role->permissions()->create([
        'resource_type' => 'orders',
        'can_view' => true,
        'can_edit' => true,
    ]);

    $this->client = Client::factory()->create();
    $this->material = Material::create([
        'name' => 'Test Material',
        'stock_quantity' => 100,
        'reserved_quantity' => 0,
    ]);
});

it('auto-fills actual quantity and deducts stock upon delivery', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $s = Stage::factory()->create(['is_delivery_stage' => true]);
    $os = $order->orderStages()->create(['stage_id' => $s->id, 'sequence' => 1]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => null, // Test auto-fill
    ]);

    $this->material->increment('reserved_quantity', 10);

    $this->actingAs($this->user)
        ->post(route('order-stages.finish', $os))
        ->assertRedirect();

    $om->refresh();
    $this->material->refresh();

    expect((float) $om->actual_quantity)->toBe(10.0);
    expect((float) $this->material->stock_quantity)->toBe(90.0);
    expect((float) $this->material->reserved_quantity)->toBe(0.0);
});

it('allows correcting actual quantity after delivery and adjusts stock correctly', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $s = Stage::factory()->create(['is_delivery_stage' => true]);
    $os = $order->orderStages()->create(['stage_id' => $s->id, 'sequence' => 1]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
    ]);

    // Manually set status as delivered
    $order->update(['delivered_at' => now(), 'delivered_by' => $this->user->id]);
    $this->material->decrement('stock_quantity', 10);

    // Increase actual consumption: 10 -> 15 (should deduct 5 more)
    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'materials' => [
                ['id' => $om->id, 'actual_quantity' => 15],
            ],
        ])->assertSessionDoesntHaveErrors();

    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(85.0);
    expect((float) $om->fresh()->actual_quantity)->toBe(15.0);

    // Decrease actual consumption: 15 -> 8 (should return 7 to stock)
    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'materials' => [
                ['id' => $om->id, 'actual_quantity' => 8],
            ],
        ])->assertSessionDoesntHaveErrors();

    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(92.0);
});

it('prevents editing other fields after delivery', function () {
    $order = Order::factory()->create([
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-OLD',
        'delivered_at' => now(),
    ]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
    ]);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'INV-NEW',
            'materials' => [
                ['id' => $om->id, 'actual_quantity' => 12],
            ],
        ]);

    $order->refresh();
    expect($order->invoice_number)->toBe('INV-OLD');
});

it('prevents negative stock during delivery corrections', function () {
    $order = Order::factory()->create(['delivered_at' => now()]);
    $this->material->update(['stock_quantity' => 5]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
    ]);

    // Try to increase consumption by 10 (needs 10 more, but only has 5)
    $response = $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'materials' => [
                ['id' => $om->id, 'actual_quantity' => 20],
            ],
        ]);

    $response->assertSessionHas('error');
    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(5.0);
});

it('reverses consumption and clears all delivery fields upon remit', function () {
    $order = Order::factory()->create([
        'client_id' => $this->client->id,
        'delivered_at' => now(),
        'delivered_by' => $this->user->id,
    ]);

    $s1 = Stage::factory()->create(['default_sequence' => 1]);
    $s2 = Stage::factory()->create(['default_sequence' => 2, 'is_delivery_stage' => true]);

    $os1 = $order->orderStages()->create(['stage_id' => $s1->id, 'sequence' => 1, 'completed_at' => now()]);
    $os2 = $order->orderStages()->create(['stage_id' => $s2->id, 'sequence' => 2, 'completed_at' => now()]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
    ]);

    $this->material->update(['stock_quantity' => 90, 'reserved_quantity' => 0]);

    $this->actingAs($this->user)
        ->post(route('order-stages.remit', $os2), [
            'target_stage_id' => $s1->id,
            'notes' => 'Mistake in delivery',
        ])->assertRedirect();

    $order->refresh();
    $this->material->refresh();

    expect($order->delivered_at)->toBeNull();
    expect($order->delivered_by)->toBeNull();
    expect((float) $this->material->stock_quantity)->toBe(100.0);
    expect((float) $this->material->reserved_quantity)->toBe(10.0);
    expect($om->fresh()->actual_quantity)->toBeNull();
});

it('prevents delivery completion if stock is insufficient', function () {
    $order = Order::factory()->create();
    $s = Stage::factory()->create(['is_delivery_stage' => true]);
    $os = $order->orderStages()->create(['stage_id' => $s->id, 'sequence' => 1]);

    $this->material->update(['stock_quantity' => 5]);
    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('order-stages.finish', $os));

    $response->assertSessionHas('error');
    $order->refresh();
    expect($order->delivered_at)->toBeNull();
});
