<?php

use App\Models\Client;
use App\Models\Material;
use App\Models\Order;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use App\Models\StageGroup;
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

it('reverses consumption when remitting to ANY stage within the Corte group', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);
    
    $group = StageGroup::create(['name' => 'Corte']);
    $s1 = Stage::factory()->create(['name' => 'Stage 1', 'default_sequence' => 1]);
    $c1 = Stage::factory()->create(['name' => 'Corte 1', 'default_sequence' => 10, 'stage_group_id' => $group->id]);
    $c2 = Stage::factory()->create(['name' => 'Corte 2', 'default_sequence' => 20, 'stage_group_id' => $group->id]);
    $s2 = Stage::factory()->create(['name' => 'Stage 2', 'default_sequence' => 30]);

    $os1 = $order->orderStages()->create(['stage_id' => $s1->id, 'sequence' => 1, 'completed_at' => now()]);
    $oc1 = $order->orderStages()->create(['stage_id' => $c1->id, 'sequence' => 2, 'completed_at' => now()]);
    $oc2 = $order->orderStages()->create(['stage_id' => $c2->id, 'sequence' => 3, 'completed_at' => now()]);
    $os2 = $order->orderStages()->create(['stage_id' => $s2->id, 'sequence' => 4, 'started_at' => now(), 'started_by' => $this->user->id]);

    $om = $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
    ]);

    $this->material->update(['stock_quantity' => 90, 'reserved_quantity' => 0]);

    // TEST 1: Remit to Corte 2 (the LAST stage in the group)
    // The bug was that it might only check the first Corte stage for sequence comparison.
    $this->actingAs($this->user)
        ->post(route('order-stages.remit', $os2), [
            'target_stage_id' => $c2->id,
            'notes' => 'Back to last cutting stage',
        ])->assertRedirect();

    $this->material->refresh();
    $om->refresh();

    expect((float) $this->material->stock_quantity)->toBe(100.0);
    expect((float) $this->material->reserved_quantity)->toBe(10.0);
    expect($om->consumed_at)->toBeNull();

    // Setup for TEST 2
    $oc2->refresh(); // Now started but not completed
    $om->update(['consumed_at' => now(), 'actual_quantity' => 10]);
    $this->material->update(['stock_quantity' => 90, 'reserved_quantity' => 0]);

    // TEST 2: Remit to Corte 1 (the FIRST stage in the group)
    $this->actingAs($this->user)
        ->post(route('order-stages.remit', $oc2), [
            'target_stage_id' => $c1->id,
            'notes' => 'Back to first cutting stage',
        ])->assertRedirect();

    $this->material->refresh();
    $om->refresh();

    expect((float) $this->material->stock_quantity)->toBe(100.0);
    expect((float) $this->material->reserved_quantity)->toBe(10.0);
    expect($om->consumed_at)->toBeNull();
});
