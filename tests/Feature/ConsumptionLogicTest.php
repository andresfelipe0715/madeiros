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

it('auto-fills actual quantity and deducts stock upon Corte stage finish', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $s = Stage::factory()->create(['name' => 'Corte']);
    $os = $order->orderStages()->create(['stage_id' => $s->id, 'sequence' => 1, 'started_at' => now(), 'started_by' => $this->user->id]);

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
    expect($om->consumed_at)->not->toBeNull();
});

it('allows correcting actual quantity after consumption and adjusts stock correctly', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
    ]);

    // Manually set stock reflected in DB (100 - 10 consumed)
    $this->material->update(['stock_quantity' => 90]);

    // Increase actual consumption: 10 -> 15 (should deduct 5 more)
    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'materials' => [
                ['id' => $om->id, 'actual_quantity' => 15],
            ],
            'invoice_number' => $order->invoice_number ?? 'INV-123',
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
            'invoice_number' => $order->invoice_number ?? 'INV-123',
        ])->assertSessionDoesntHaveErrors();

    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(92.0);
});

it('prevents editing other fields after delivery but allows material correction', function () {
    $order = Order::factory()->create([
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-OLD',
        'delivered_at' => now(),
    ]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
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
    expect((float) $om->fresh()->actual_quantity)->toBe(12.0);
});

it('prevents negative stock during consumption corrections', function () {
    $order = Order::factory()->create();
    $this->material->update(['stock_quantity' => 5]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
    ]);

    // Try to increase consumption by 10 (needs 10 more, but only has 5)
    $response = $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'materials' => [
                ['id' => $om->id, 'actual_quantity' => 20],
            ],
            'invoice_number' => $order->invoice_number ?? 'INV-123',
        ]);

    $response->assertSessionHas('error');
    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(5.0);
});

it('reverses consumption and restores reservations upon remit', function () {
    $order = Order::factory()->create([
        'client_id' => $this->client->id,
    ]);

    $s1 = Stage::factory()->create(['default_sequence' => 1]);
    $s2 = Stage::factory()->create(['name' => 'Corte', 'default_sequence' => 2]);

    $os1 = $order->orderStages()->create(['stage_id' => $s1->id, 'sequence' => 1, 'completed_at' => now()]);
    $os2 = $order->orderStages()->create(['stage_id' => $s2->id, 'sequence' => 2, 'started_at' => now(), 'started_by' => $this->user->id]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
    ]);

    $this->material->update(['stock_quantity' => 90, 'reserved_quantity' => 0]);

    // Remit from a post-Corte state back to s1
    $this->actingAs($this->user)
        ->post(route('order-stages.remit', $os2), [
            'target_stage_id' => $s1->id,
            'notes' => 'Mistake in processing',
        ])->assertRedirect();

    $order->refresh();
    $this->material->refresh();

    expect((float) $this->material->stock_quantity)->toBe(100.0);
    expect((float) $this->material->reserved_quantity)->toBe(10.0);
    expect($om->fresh()->actual_quantity)->toBeNull();
    expect($om->fresh()->consumed_at)->toBeNull();
});

it('prevents Corte completion if stock is insufficient', function () {
    $order = Order::factory()->create();
    $s = Stage::factory()->create(['name' => 'Corte']);
    $os = $order->orderStages()->create(['stage_id' => $s->id, 'sequence' => 1, 'started_at' => now(), 'started_by' => $this->user->id]);

    $this->material->update(['stock_quantity' => 5]);
    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('order-stages.finish', $os));

    $response->assertSessionHas('errors');
    $order->refresh();
    expect($om->fresh()->consumed_at)->toBeNull();
});

it('prevents remitting if the order has already been delivered', function () {
    $order = Order::factory()->create([
        'client_id' => $this->client->id,
        'delivered_at' => now(),
    ]);

    $s1 = Stage::factory()->create(['default_sequence' => 1]);
    $s2 = Stage::factory()->create(['name' => 'Corte', 'default_sequence' => 2]);

    $os1 = $order->orderStages()->create(['stage_id' => $s1->id, 'sequence' => 1, 'completed_at' => now()]);
    $os2 = $order->orderStages()->create(['stage_id' => $s2->id, 'sequence' => 2, 'started_at' => now(), 'started_by' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->post(route('order-stages.remit', $os2), [
            'target_stage_id' => $s1->id,
            'notes' => 'Attempting to remit after delivery',
        ]);

    $response->assertSessionHasErrors('auth');
    $this->assertEquals('No se puede remitir un pedido que ya ha sido entregado.', session('errors')->get('auth')[0]);
});

it('instantly consumes new materials added after Corte stage is finished', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $s = Stage::factory()->create(['name' => 'Corte']);
    $os = $order->orderStages()->create(['stage_id' => $s->id, 'sequence' => 1, 'started_at' => now(), 'completed_at' => now()]);

    $newMaterial = Material::create([
        'name' => 'New Material',
        'stock_quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => $order->invoice_number ?? 'INV-123',
            'materials' => [
                [
                    'material_id' => $newMaterial->id,
                    'estimated_quantity' => 5,
                ],
            ],
        ])->assertSessionDoesntHaveErrors();

    $newMaterial->refresh();
    $om = $order->orderMaterials()->where('material_id', $newMaterial->id)->first();

    expect($om->consumed_at)->not->toBeNull();
    expect((float) $om->actual_quantity)->toBe(5.0);
    expect((float) $newMaterial->stock_quantity)->toBe(95.0);
    expect((float) $newMaterial->reserved_quantity)->toBe(0.0);
});

it('instantly consumes restored materials after Corte stage is finished', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $s = Stage::factory()->create(['name' => 'Corte']);
    $os = $order->orderStages()->create(['stage_id' => $s->id, 'sequence' => 1, 'started_at' => now(), 'completed_at' => now()]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'cancelled_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => $order->invoice_number ?? 'INV-123',
            'materials' => [
                [
                    'id' => $om->id,
                    'material_id' => $this->material->id, // Required by UpdateOrderRequest logic for non-consumed items
                    'estimated_quantity' => 10,
                    'cancelled' => false,
                ],
            ],
        ])->assertSessionDoesntHaveErrors();

    $this->material->refresh();
    $om->refresh();

    expect($om->cancelled_at)->toBeNull();
    expect($om->consumed_at)->not->toBeNull();
    expect((float) $om->actual_quantity)->toBe(10.0);
    expect((float) $this->material->stock_quantity)->toBe(90.0);
    expect((float) $this->material->reserved_quantity)->toBe(0.0);
});

it('cancelling a consumed material correctly restores stock, not reserved quantity', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
    ]);

    $this->material->update(['stock_quantity' => 90, 'reserved_quantity' => 0]);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => $order->invoice_number ?? 'INV-123',
            'materials' => [
                [
                    'id' => $om->id,
                    'material_id' => $this->material->id,
                    'estimated_quantity' => 10,
                    'cancelled' => true,
                ],
            ],
        ])->assertSessionDoesntHaveErrors();

    $this->material->refresh();
    $om->refresh();

    expect($om->cancelled_at)->not->toBeNull();
    expect((float) $this->material->stock_quantity)->toBe(100.0);
    expect((float) $this->material->reserved_quantity)->toBe(0.0);
});

it('prevents swapping the material type of an already consumed material', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
    ]);

    $newMaterial = Material::create([
        'name' => 'Other Material',
        'stock_quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    $response = $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => $order->invoice_number ?? 'INV-123',
            'materials' => [
                [
                    'id' => $om->id,
                    'material_id' => $newMaterial->id,
                    'estimated_quantity' => 10,
                ],
            ],
        ]);

    $response->assertSessionHas('error', 'No se puede cambiar el tipo de material porque ya fue consumido. Debe cancelarlo y agregar uno nuevo.');
});

it('only reverses consumption if remitting to Corte or an earlier stage', function () {
    $order = Order::factory()->create([
        'client_id' => $this->client->id,
    ]);

    $s1 = Stage::factory()->create(['default_sequence' => 1]);
    $s2 = Stage::factory()->create(['name' => 'Corte', 'default_sequence' => 2]);
    $s3 = Stage::factory()->create(['default_sequence' => 3]);
    $s4 = Stage::factory()->create(['default_sequence' => 4]);

    $os1 = $order->orderStages()->create(['stage_id' => $s1->id, 'sequence' => 1, 'completed_at' => now()]);
    $os2 = $order->orderStages()->create(['stage_id' => $s2->id, 'sequence' => 2, 'completed_at' => now()]);
    $os3 = $order->orderStages()->create(['stage_id' => $s3->id, 'sequence' => 3, 'completed_at' => now()]);
    $os4 = $order->orderStages()->create(['stage_id' => $s4->id, 'sequence' => 4, 'started_at' => now(), 'started_by' => $this->user->id]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
    ]);

    $this->material->update(['stock_quantity' => 90, 'reserved_quantity' => 0]);

    // Remitting to sequence 3 (AFTER Corte) should NOT reverse consumption
    $this->actingAs($this->user)
        ->post(route('order-stages.remit', $os4), [
            'target_stage_id' => $s3->id,
            'notes' => 'Mistake in processing',
        ])->assertRedirect();

    $om->refresh();
    expect($om->consumed_at)->not->toBeNull(); // Consumption Should remain intact

    // Remitting to sequence 1 (BEFORE Corte) SHOULD reverse consumption
    $os3->refresh(); // Now started but not completed
    $this->actingAs($this->user)
        ->post(route('order-stages.remit', $os3), [
            'target_stage_id' => $s1->id,
            'notes' => 'Big mistake',
        ])->assertRedirect();

    $om->refresh();
    expect($om->consumed_at)->toBeNull(); // Consumption should now be reversed
});

it('properly restores reserved_quantity for materials added after Corte when reversing', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    $s1 = Stage::factory()->create(['default_sequence' => 1]);
    $s2 = Stage::factory()->create(['name' => 'Corte', 'default_sequence' => 2]);
    $s3 = Stage::factory()->create(['default_sequence' => 3]);

    $os1 = $order->orderStages()->create(['stage_id' => $s1->id, 'sequence' => 1, 'completed_at' => now()->subMinute()]);
    $os2 = $order->orderStages()->create(['stage_id' => $s2->id, 'sequence' => 2, 'completed_at' => now()->subMinute()]);
    $os3 = $order->orderStages()->create(['stage_id' => $s3->id, 'sequence' => 3, 'started_at' => now(), 'started_by' => $this->user->id]);

    // Simulate a material added AFTER Corte — consumed immediately, never reserved
    $addedAfterCorte = Material::create(['name' => 'Added Late', 'stock_quantity' => 50, 'reserved_quantity' => 0]);
    $om = $order->orderMaterials()->create([
        'material_id' => $addedAfterCorte->id,
        'estimated_quantity' => 7,
        'actual_quantity' => 7,
        'consumed_at' => now(),
    ]);

    $addedAfterCorte->decrement('stock_quantity', 7); // simulate what the service does

    // Remit back to Corte (which triggers reversal)
    $this->actingAs($this->user)
        ->post(route('order-stages.remit', $os3), [
            'target_stage_id' => $s2->id,
            'notes' => 'Redo Corte',
        ])->assertRedirect();

    $addedAfterCorte->refresh();
    $om->refresh();

    expect($om->consumed_at)->toBeNull();
    expect($om->actual_quantity)->toBeNull();
    expect((float) $addedAfterCorte->stock_quantity)->toBe(50.0); // Stock restored
    expect((float) $addedAfterCorte->reserved_quantity)->toBe(7.0); // Now re-reserved correctly
});

it('cancelling a non-consumed material releases its reservation from stock', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 15,
    ]);

    // Simulate the reservation applied when the order was created
    $this->material->increment('reserved_quantity', 15);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => $order->invoice_number ?? 'INV-001',
            'materials' => [
                [
                    'id' => $om->id,
                    'material_id' => $this->material->id,
                    'estimated_quantity' => 15,
                    'cancelled' => true,
                ],
            ],
        ])->assertSessionDoesntHaveErrors();

    $this->material->refresh();
    $om->refresh();

    expect($om->cancelled_at)->not->toBeNull();
    // Reservation released, stock unchanged
    expect((float) $this->material->reserved_quantity)->toBe(0.0);
    expect((float) $this->material->stock_quantity)->toBe(100.0);
});

it('cancelling a consumed material correctly returns actual quantity to stock', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 8, // Consumed slightly less than estimated
        'consumed_at' => now(),
    ]);

    // Simulate what the service does at Corte: stock was deducted by actual
    $this->material->update(['stock_quantity' => 92, 'reserved_quantity' => 0]);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => $order->invoice_number ?? 'INV-001',
            'materials' => [
                [
                    'id' => $om->id,
                    'material_id' => $this->material->id,
                    'estimated_quantity' => 10,
                    'cancelled' => true,
                ],
            ],
        ])->assertSessionDoesntHaveErrors();

    $this->material->refresh();
    $om->refresh();

    expect($om->cancelled_at)->not->toBeNull();
    // Stock restored by actual_quantity (8), reservation stays 0
    expect((float) $this->material->stock_quantity)->toBe(100.0);
    expect((float) $this->material->reserved_quantity)->toBe(0.0);
});
