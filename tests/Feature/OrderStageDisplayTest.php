<?php

use App\Models\Order;
use App\Models\OrderStage;
use App\Models\Stage;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->client = Client::factory()->create();
    $this->user = User::factory()->create();
});

it('returns "Sin etapa" when order has no stages', function () {
    $order = new Order();
    $order->client_id = $this->client->id;
    $order->created_by = $this->user->id;
    $order->material = 'MDF';
    $order->invoice_number = 'FC-123';
    $order->save();

    expect($order->currentStageName())->toBe('Sin etapa');
});

it('returns "Sin etapa" when order is marked as delivered but has no stages', function () {
    $order = new Order();
    $order->client_id = $this->client->id;
    $order->created_by = $this->user->id;
    $order->material = 'MDF';
    $order->invoice_number = 'FC-124';
    $order->delivered_at = now();
    $order->save();

    expect($order->currentStageName())->toBe('Sin etapa');
});

it('returns the name of the first incomplete stage', function () {
    $order = new Order();
    $order->client_id = $this->client->id;
    $order->created_by = $this->user->id;
    $order->material = 'MDF';
    $order->invoice_number = 'FC-125';
    $order->save();

    $stage1 = new Stage();
    $stage1->name = 'Corte';
    $stage1->save();

    $stage2 = new Stage();
    $stage2->name = 'Enchape';
    $stage2->save();

    $os1 = new OrderStage();
    $os1->order_id = $order->id;
    $os1->stage_id = $stage1->id;
    $os1->sequence = 1;
    $os1->completed_at = now();
    $os1->save();

    $os2 = new OrderStage();
    $os2->order_id = $order->id;
    $os2->stage_id = $stage2->id;
    $os2->sequence = 2;
    $os2->completed_at = null;
    $os2->save();

    // Refresh to load relations
    $order->load('orderStages.stage');

    expect($order->currentStageName())->toBe('Enchape');
});

it('returns "Entregada" when all stages are completed', function () {
    $order = new Order();
    $order->client_id = $this->client->id;
    $order->created_by = $this->user->id;
    $order->material = 'MDF';
    $order->invoice_number = 'FC-126';
    $order->save();

    $stage1 = new Stage();
    $stage1->name = 'Corte';
    $stage1->save();

    $os1 = new OrderStage();
    $os1->order_id = $order->id;
    $os1->stage_id = $stage1->id;
    $os1->sequence = 1;
    $os1->completed_at = now();
    $os1->save();

    $order->load('orderStages.stage');

    expect($order->currentStageName())->toBe('Entregada');
});
