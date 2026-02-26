<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderStage;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup Stages
    $this->corte = Stage::create(['name' => 'Corte', 'default_sequence' => 10]);
    $this->enchape = Stage::create(['name' => 'Enchape', 'default_sequence' => 20]);
    $this->entrega = Stage::create(['name' => 'Entrega', 'default_sequence' => 50]);

    // Setup Client
    $this->client = Client::create(['name' => 'Test Client', 'document' => '12345678', 'phone' => '123456789']);

    // Setup Admin Role & User
    $this->adminRole = Role::create(['name' => 'Admin Role']);
    RolePermission::create(['role_id' => $this->adminRole->id, 'resource_type' => 'orders', 'can_edit' => true]);

    $this->adminUser = User::create([
        'name' => 'Admin User',
        'document' => 'ADM123',
        'password' => bcrypt('password'),
        'role_id' => $this->adminRole->id,
        'active' => true,
    ]);

    // Create a standard order with all three stages
    $this->order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-TEST',
        'created_by' => $this->adminUser->id,
    ]);

    OrderStage::create(['order_id' => $this->order->id, 'stage_id' => $this->corte->id, 'sequence' => 1]);
    OrderStage::create(['order_id' => $this->order->id, 'stage_id' => $this->enchape->id, 'sequence' => 2]);
    OrderStage::create(['order_id' => $this->order->id, 'stage_id' => $this->entrega->id, 'sequence' => 3]);

    Auth::login($this->adminUser);
});

it('prevents removing the first stage (lowest sequence)', function () {
    $response = $this->delete(route('orders.remove-stage', [$this->order, $this->corte]));

    $response->assertSessionHas('error', 'No se puede eliminar una etapa obligatoria (primera o última).');
    expect($this->order->orderStages()->where('stage_id', $this->corte->id)->exists())->toBeTrue();
});

it('prevents removing the last stage (highest sequence)', function () {
    $response = $this->delete(route('orders.remove-stage', [$this->order, $this->entrega]));

    $response->assertSessionHas('error', 'No se puede eliminar una etapa obligatoria (primera o última).');
    expect($this->order->orderStages()->where('stage_id', $this->entrega->id)->exists())->toBeTrue();
});

it('allows removing an intermediate stage', function () {
    $response = $this->delete(route('orders.remove-stage', [$this->order, $this->enchape]));

    $response->assertSessionHas('success', 'Etapa eliminada exitosamente.');
    expect($this->order->orderStages()->where('stage_id', $this->enchape->id)->exists())->toBeFalse();
});
