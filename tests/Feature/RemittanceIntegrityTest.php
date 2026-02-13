<?php

use App\Models\Order;
use App\Models\OrderStage;
use App\Models\Stage;
use App\Models\Client;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::create(['name' => 'Admin']);
    $this->user = User::factory()->create(['role_id' => $this->role->id]);
    $this->client = Client::factory()->create();

    $this->stage1 = Stage::create(['name' => 'Corte']);
    $this->stage2 = Stage::create(['name' => 'Enchape']);
    $this->stage3 = Stage::create(['name' => 'Entrega']);
});

it('allows remittance to a valid previous stage', function () {
    $order = Order::create([
        'client_id' => $this->client->id,
        'created_by' => $this->user->id,
        'material' => 'MDF',
        'invoice_number' => 'FC-1',
    ]);

    $os1 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->stage1->id, 'sequence' => 1]);
    $os2 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->stage2->id, 'sequence' => 2]);
    $os3 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->stage3->id, 'sequence' => 3]);

    actingAs($this->user)
        ->post(route('order-stages.remit', $os2->id), [
            'target_stage_id' => $this->stage1->id,
            'notes' => 'Back to cutting',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Pedido remitido.');
});

it('aborts with 400 if target stage does not belong to the order', function () {
    $order = Order::create([
        'client_id' => $this->client->id,
        'created_by' => $this->user->id,
        'material' => 'MDF',
        'invoice_number' => 'FC-2',
    ]);

    $os2 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->stage2->id, 'sequence' => 2]);

    // Another stage that exists but NOT assigned to this order
    $anotherStage = Stage::create(['name' => 'Special']);

    actingAs($this->user)
        ->post(route('order-stages.remit', $os2->id), [
            'target_stage_id' => $anotherStage->id,
            'notes' => 'Attempting illegal remit',
        ])
        ->assertStatus(400);
});

it('aborts with 400 if target stage sequence is >= current stage sequence', function () {
    $order = Order::create([
        'client_id' => $this->client->id,
        'created_by' => $this->user->id,
        'material' => 'MDF',
        'invoice_number' => 'FC-3',
    ]);

    $os1 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->stage1->id, 'sequence' => 1]);
    $os2 = OrderStage::create(['order_id' => $order->id, 'stage_id' => $this->stage2->id, 'sequence' => 2]);

    actingAs($this->user)
        ->post(route('order-stages.remit', $os1->id), [
            'target_stage_id' => $this->stage2->id,
            'notes' => 'Attempting forward remit',
        ])
        ->assertStatus(400);

    actingAs($this->user)
        ->post(route('order-stages.remit', $os1->id), [
            'target_stage_id' => $this->stage1->id,
            'notes' => 'Attempting same stage remit',
        ])
        ->assertStatus(400);
});
