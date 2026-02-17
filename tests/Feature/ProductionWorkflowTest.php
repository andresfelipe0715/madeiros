<?php

use App\Models\Order;
use App\Models\OrderStage;
use App\Models\Role;
use App\Models\RoleOrderPermission;
use App\Models\Stage;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 1. Setup Stages
    $this->corte = Stage::create(['name' => 'Corte', 'default_sequence' => 10]);
    $this->enchape = Stage::create(['name' => 'Enchape', 'default_sequence' => 20]);

    // 2. Setup Client
    $this->client = Client::create(['name' => 'Test Client', 'document' => '12345678', 'phone' => '123456789']);

    // 3. Setup Roles
    $this->corteRole = Role::create(['name' => 'Corte User']);
    $this->corteRole->stages()->attach($this->corte->id);
    RoleOrderPermission::create(['role_id' => $this->corteRole->id, 'can_edit' => false]);

    $this->adminRole = Role::create(['name' => 'Admin Role']);
    $this->adminRole->stages()->attach([$this->corte->id, $this->enchape->id]);
    RoleOrderPermission::create(['role_id' => $this->adminRole->id, 'can_edit' => true]);

    // 4. Setup Users
    $this->corteUser = User::create([
        'name' => 'Corte User',
        'document' => 'CORT123',
        'password' => bcrypt('password'),
        'role_id' => $this->corteRole->id,
        'active' => true,
    ]);

    $this->adminUser = User::create([
        'name' => 'Admin User',
        'document' => 'ADM123',
        'password' => bcrypt('password'),
        'role_id' => $this->adminRole->id,
        'active' => true,
    ]);
});

it('blocks a regular user from starting an order if it is not next in queue', function () {
    // Create Order A
    $orderA = Order::create([
        'client_id' => $this->client->id,
        'material' => 'Wood',
        'invoice_number' => 'INV-A',
        'created_by' => $this->adminUser->id
    ]);
    OrderStage::create(['order_id' => $orderA->id, 'stage_id' => $this->corte->id, 'sequence' => 1]);

    // Create Order B
    $orderB = Order::create([
        'client_id' => $this->client->id,
        'material' => 'Metal',
        'invoice_number' => 'INV-B',
        'created_by' => $this->adminUser->id
    ]);
    $orderStageB = OrderStage::create(['order_id' => $orderB->id, 'stage_id' => $this->corte->id, 'sequence' => 1]);

    // Acting as Corte User
    Auth::login($this->corteUser);

    // Try to start Order B
    $response = $this->post(route('order-stages.start', $orderStageB->id));

    if ($response->getSession()->has('errors')) {
        // dump($response->getSession()->get('errors')->getMessages());
    }

    $response->assertSessionHasErrors(['auth' => 'Este pedido no es el siguiente en la fila.']);
    expect($orderStageB->refresh()->started_at)->toBeNull();
});

it('allows a regular user to start the next order in queue', function () {
    // Create Order A
    $orderA = Order::create([
        'client_id' => $this->client->id,
        'material' => 'Wood',
        'invoice_number' => 'INV-A',
        'created_by' => $this->adminUser->id
    ]);
    $orderStageA = OrderStage::create(['order_id' => $orderA->id, 'stage_id' => $this->corte->id, 'sequence' => 1]);

    // Acting as Corte User
    Auth::login($this->corteUser);

    // Start Order A
    $response = $this->post(route('order-stages.start', $orderStageA->id));

    $response->assertSessionHas('status', 'Etapa iniciada.');
    expect($orderStageA->refresh()->started_at)->not->toBeNull();
});

it('allows an admin to override the queue', function () {
    // Create Order A
    $orderA = Order::create([
        'client_id' => $this->client->id,
        'material' => 'Wood',
        'invoice_number' => 'INV-A',
        'created_by' => $this->adminUser->id
    ]);
    OrderStage::create(['order_id' => $orderA->id, 'stage_id' => $this->corte->id, 'sequence' => 1]);

    // Create Order B
    $orderB = Order::create([
        'client_id' => $this->client->id,
        'material' => 'Metal',
        'invoice_number' => 'INV-B',
        'created_by' => $this->adminUser->id
    ]);
    $orderStageB = OrderStage::create(['order_id' => $orderB->id, 'stage_id' => $this->corte->id, 'sequence' => 1]);

    // Acting as Admin User
    Auth::login($this->adminUser);

    // Try to start Order B (Override)
    $response = $this->post(route('order-stages.start', $orderStageB->id));

    $response->assertSessionHas('status', 'Etapa iniciada.');
    expect($orderStageB->refresh()->started_at)->not->toBeNull();
});

it('blocks finishing an order if it is not next in queue', function () {
    // Create Order A
    $orderA = Order::create([
        'client_id' => $this->client->id,
        'material' => 'Wood',
        'invoice_number' => 'INV-A',
        'created_by' => $this->adminUser->id
    ]);
    OrderStage::create(['order_id' => $orderA->id, 'stage_id' => $this->corte->id, 'sequence' => 1]);

    // Create Order B
    $orderB = Order::create([
        'client_id' => $this->client->id,
        'material' => 'Metal',
        'invoice_number' => 'INV-B',
        'created_by' => $this->adminUser->id
    ]);
    $orderStageB = OrderStage::create([
        'order_id' => $orderB->id,
        'stage_id' => $this->corte->id,
        'sequence' => 1,
        'started_at' => now(),
        'started_by' => $this->adminUser->id
    ]);

    // Acting as Corte User
    Auth::login($this->corteUser);

    // Try to finish Order B
    $response = $this->from(route('dashboard'))->post(route('order-stages.finish', $orderStageB->id));

    $response->assertStatus(302);
    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHasErrors(['auth' => 'Este pedido no es el siguiente en la fila.']);
    expect($orderStageB->refresh()->completed_at)->toBeNull();
});
