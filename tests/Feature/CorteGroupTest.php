<?php

use App\Models\Client;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderMaterial;
use App\Models\OrderStage;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\StageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->corteGroup = StageGroup::create(['name' => 'Corte']);
    $this->enchapeGroup = StageGroup::create(['name' => 'Enchape']);

    $this->corte1 = Stage::create(['name' => 'Corte 1', 'default_sequence' => 10, 'stage_group_id' => $this->corteGroup->id]);
    $this->corte2 = Stage::create(['name' => 'Corte 2', 'default_sequence' => 11, 'stage_group_id' => $this->corteGroup->id]);
    $this->enchape = Stage::create(['name' => 'Enchape', 'default_sequence' => 20, 'stage_group_id' => $this->enchapeGroup->id]);

    $this->client = Client::create(['name' => 'Test Client', 'document' => '12345678', 'phone' => '123456789']);

    $this->adminRole = Role::create(['name' => 'Admin Role']);
    $this->adminRole->stages()->attach([$this->corte1->id, $this->corte2->id, $this->enchape->id]);
    RolePermission::create(['role_id' => $this->adminRole->id, 'resource_type' => 'orders', 'can_edit' => true]);

    $this->adminUser = User::create([
        'name' => 'Admin User',
        'document' => 'ADM123',
        'password' => bcrypt('password'),
        'role_id' => $this->adminRole->id,
        'active' => true,
    ]);

    $this->material = Material::create([
        'name' => 'Test Material',
        'stock_quantity' => 100,
        'reserved_quantity' => 0,
    ]);
});

it('triggers consumption when any stage in the Corte group is finished', function () {
    Auth::login($this->adminUser);

    $order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-TEST-1',
        'created_by' => $this->adminUser->id,
    ]);

    $os1 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->corte1->id, 'sequence' => 1, 'started_at' => now(), 'started_by' => $this->adminUser->id]);
    $os2 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->corte2->id, 'sequence' => 2]);
    $os3 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->enchape->id, 'sequence' => 3]);

    $om = OrderMaterial::create([
        'order_id' => $order->id,
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
    ]);
    
    // Simulate reservation
    $this->material->increment('reserved_quantity', 10);

    // Finish Corte 1
    $response = $this->post(route('order-stages.finish', $os1->id));
    $response->assertSessionHas('status', 'Etapa finalizada.');

    $om->refresh();
    expect($om->consumed_at)->not->toBeNull();
    expect((float) $om->actual_quantity)->toBe(10.0);

    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(90.0);
    expect((float) $this->material->reserved_quantity)->toBe(0.0);
});

it('correctly handles reverse consumption when remitting past a multi-stage Corte group', function () {
    Auth::login($this->adminUser);

    $order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-TEST-2',
        'created_by' => $this->adminUser->id,
    ]);

    $os1 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->corte1->id, 'sequence' => 1, 'completed_at' => now(), 'completed_by' => $this->adminUser->id]);
    $os2 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->corte2->id, 'sequence' => 2, 'completed_at' => now(), 'completed_by' => $this->adminUser->id]);
    $os3 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->enchape->id, 'sequence' => 3, 'started_at' => now(), 'started_by' => $this->adminUser->id]);

    $om = OrderMaterial::create([
        'order_id' => $order->id,
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
    ]);
    
    // Simulate stock deduction
    $this->material->decrement('stock_quantity', 10);

    // Remit from Enchape back to Corte 1
    $response = $this->post(route('order-stages.remit', $os3->id), [
        'target_stage_id' => $this->corte1->id,
        'notes' => 'Rework required',
    ]);

    $response->assertSessionHas('status', 'Pedido remitido.');

    $om->refresh();
    expect($om->consumed_at)->toBeNull();
    expect($om->actual_quantity)->toBeNull();

    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(100.0);
    expect((float) $this->material->reserved_quantity)->toBe(10.0);
});
