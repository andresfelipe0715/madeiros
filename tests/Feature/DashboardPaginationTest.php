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
    $this->stage = Stage::create(['name' => 'Corte', 'default_sequence' => 1]);
    $this->role->stages()->attach($this->stage);
});

it('paginates orders in the dashboard stage view', function () {
    // Create 20 orders for the same stage
    for ($i = 1; $i <= 20; $i++) {
        $order = Order::create([
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'material' => 'MDF',
            'invoice_number' => "FC-{$i}",
        ]);

        OrderStage::create([
            'order_id' => $order->id,
            'stage_id' => $this->stage->id,
            'sequence' => 1,
        ]);
    }

    actingAs($this->user)
        ->get(route('dashboard.stage', $this->stage->id))
        ->assertSuccessful()
        ->assertViewHas('orders', function ($orders) {
            return $orders->count() === 15 && $orders->total() === 20;
        });
});
