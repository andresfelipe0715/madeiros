<?php

use App\Models\Client;
use App\Models\Material;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::factory()->create();
    $this->user = User::factory()->create(['role_id' => $this->role->id]);
    $this->role->permissions()->create([
        'resource_type' => 'materials',
        'can_view' => true,
    ]);

    $this->client = Client::factory()->create();
    $this->material = Material::create([
        'name' => 'Hierro',
        'stock_quantity' => 100,
        'reserved_quantity' => 0,
    ]);
});

it('allows authorized users to access the consumption calendar', function () {
    $this->actingAs($this->user)
        ->get(route('materials.consumption'))
        ->assertOk()
        ->assertViewIs('materials-consumption.index')
        ->assertSee('Consumo de Materiales');
});

it('restricts unauthorized users from accessing the consumption calendar', function () {
    $otherRole = Role::factory()->create();
    $otherUser = User::factory()->create(['role_id' => $otherRole->id]);

    $this->actingAs($otherUser)
        ->get(route('materials.consumption'))
        ->assertForbidden();
});

it('displays grouped consumption data correctly', function () {
    $order = Order::factory()->create([
        'client_id' => $this->client->id,
        'invoice_number' => 'INV-001',
        'created_by' => $this->user->id,
    ]);

    $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 12,
        'consumed_at' => now(), // Today
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('materials.consumption'));

    $response->assertSee('Hierro');
    $response->assertSee('<span>12</span>', false);
    $response->assertSee('#INV-001');
});

it('filters by material correctly', function () {
    $otherMaterial = Material::create(['name' => 'Madera', 'stock_quantity' => 100]);

    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 10,
        'consumed_at' => now(),
    ]);

    $order->orderMaterials()->create([
        'material_id' => $otherMaterial->id,
        'estimated_quantity' => 5,
        'actual_quantity' => 5,
        'consumed_at' => now(),
    ]);

    // Filter by 'Hierro' only
    $response = $this->actingAs($this->user)
        ->get(route('materials.consumption', ['material_id' => $this->material->id]));

    $response->assertSee('<span>10</span>', false);
    $response->assertDontSee('<span>5</span>', false);
});

it('filters by month and year correctly', function () {
    $order = Order::factory()->create(['client_id' => $this->client->id, 'created_by' => $this->user->id]);

    $lastMonth = now()->subMonth();

    $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 10,
        'actual_quantity' => 25,
        'consumed_at' => $lastMonth,
    ]);

    // Check current month (should be empty)
    $responseCurrent = $this->actingAs($this->user)
        ->get(route('materials.consumption', [
            'month' => now()->month,
            'year' => now()->year,
        ]));

    $responseCurrent->assertDontSee('<span>25</span>', false);

    // Check last month
    $responseLast = $this->actingAs($this->user)
        ->get(route('materials.consumption', [
            'month' => $lastMonth->month,
            'year' => $lastMonth->year,
        ]));

    $responseLast->assertSee('<span>25</span>', false);
});
