<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup Admin user
    $this->role = Role::firstOrCreate(['name' => 'Admin']);
    $this->user = User::factory()->create(['role_id' => $this->role->id]);

    // Bypass all gates for the test
    Gate::before(fn() => true);

    // Setup Stages
    $this->stage1 = Stage::firstOrCreate(['name' => 'Corte', 'default_sequence' => 1]);
    $this->role->stages()->syncWithoutDetaching([$this->stage1->id]);

    // Setup Clients
    $this->clientA = Client::factory()->create(['name' => 'Acme Corp', 'document' => '11111']);
    $this->clientB = Client::factory()->create(['name' => 'Globex', 'document' => '22222']);

    // Setup Orders
    $this->order1 = Order::factory()->create([
        'client_id' => $this->clientA->id,
        'invoice_number' => 'FAC-001',
        'created_by' => $this->user->id
    ]);
    $this->order2 = Order::factory()->create([
        'client_id' => $this->clientB->id,
        'invoice_number' => 'FAC-002',
        'created_by' => $this->user->id
    ]);

    // Add stages to orders
    $this->order1->orderStages()->create(['stage_id' => $this->stage1->id, 'sequence' => 1]);
    $this->order2->orderStages()->create(['stage_id' => $this->stage1->id, 'sequence' => 1]);
});

it('filters orders by invoice number in admin list', function () {
    actingAs($this->user);

    $response = get(route('orders.index', ['search' => 'FAC-001']));

    $response->assertStatus(200);
    $response->assertSee('FAC-001');
    $response->assertDontSee('FAC-002');
});

it('filters orders by client name in admin list', function () {
    actingAs($this->user);

    $response = get(route('orders.index', ['search' => 'Acme']));

    $response->assertStatus(200);
    $response->assertSee('Acme Corp');
    $response->assertDontSee('Globex');
});

it('filters orders in stage view', function () {
    actingAs($this->user);

    $response = get(route('dashboard.stage', ['stage' => $this->stage1->id, 'search' => 'FAC-001']));

    $response->assertStatus(200);
    $response->assertSee('FAC-001');
    $response->assertDontSee('FAC-002');
});

it('filters clients by name or document', function () {
    actingAs($this->user);

    // Filter by name
    $response = get(route('clients.index', ['search' => 'Acme']));
    $response->assertStatus(200);
    $response->assertSee('Acme Corp');
    $response->assertDontSee('Globex');

    // Filter by document
    $response = get(route('clients.index', ['search' => '22222']));
    $response->assertStatus(200);
    $response->assertSee('Globex');
    $response->assertDontSee('Acme Corp');
});

it('preserves search query in pagination', function () {
    actingAs($this->user);

    // Create many orders to trigger pagination
    for ($i = 0; $i < 20; $i++) {
        Order::factory()->create([
            'client_id' => $this->clientA->id,
            'invoice_number' => 'EXTRA-FAC-' . $i,
            'created_by' => $this->user->id
        ]);
    }

    $response = get(route('orders.index', ['search' => 'EXTRA-FAC']));

    $response->assertStatus(200);
    $response->assertSee('search=EXTRA-FAC');
});
