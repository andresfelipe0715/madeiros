<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\User;
use App\Models\Material;
use App\Models\SpecialService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::create(['name' => 'Admin']);
    $this->user = User::factory()->create(['role_id' => $this->role->id, 'active' => true]);

    // Grant permissions
    RolePermission::create([
        'role_id' => $this->role->id,
        'resource_type' => 'orders',
        'can_view' => true,
        'can_edit' => true,
        'can_create' => true,
    ]);

    $this->client = Client::factory()->create();
    $this->stage = Stage::create(['name' => 'Corte', 'default_sequence' => 1]);
    $this->material = Material::create(['name' => 'Wood', 'stock_quantity' => 100]);
    $this->specialService = SpecialService::create(['name' => 'Canto Delgado']);
});

it('can create an order with special services', function () {
    $this->actingAs($this->user)
        ->post(route('orders.store'), [
            'client_id' => $this->client->id,
            'invoice_number' => 'SS-123',
            'stages' => [
                ['stage_id' => $this->stage->id, 'sequence' => 1]
            ],
            'materials' => [
                ['material_id' => $this->material->id, 'estimated_quantity' => 1]
            ],
            'special_services' => [
                ['service_id' => $this->specialService->id, 'notes' => 'Test Note'],
            ],
        ])
        ->assertRedirect(route('orders.index'));

    $order = Order::first();
    expect($order->orderSpecialServices)->toHaveCount(1);
    expect($order->orderSpecialServices->first()->service_id)->toBe($this->specialService->id);
    expect($order->orderSpecialServices->first()->notes)->toBe('Test Note');
});

it('can update special services on an order', function () {
    $order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'SS-124',
        'created_by' => $this->user->id,
    ]);

    $oss = $order->orderSpecialServices()->create([
        'service_id' => $this->specialService->id,
        'notes' => 'Old Note'
    ]);

    // Add a new service and update existing one
    $newService = SpecialService::create(['name' => 'Ranurado']);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'SS-124',
            'materials' => [
                ['material_id' => $this->material->id, 'estimated_quantity' => 1]
            ],
            'special_services' => [
                [
                    'id' => $oss->id,
                    'service_id' => $this->specialService->id,
                    'notes' => 'Updated Note',
                    'cancelled' => 0
                ],
                [
                    'service_id' => $newService->id,
                    'notes' => 'New Service Note',
                ]
            ],
        ])
        ->assertRedirect(route('orders.index'));

    $order->refresh();
    expect($order->orderSpecialServices)->toHaveCount(2);
    expect($oss->refresh()->notes)->toBe('Updated Note');
    expect($order->orderSpecialServices->where('service_id', $newService->id)->first()->notes)->toBe('New Service Note');
});

it('can cancel a special service during update', function () {
    $order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'SS-125',
        'created_by' => $this->user->id,
    ]);

    $oss = $order->orderSpecialServices()->create([
        'service_id' => $this->specialService->id,
        'notes' => 'To be cancelled'
    ]);

    $this->actingAs($this->user)
        ->put(route('orders.update', $order), [
            'invoice_number' => 'SS-125',
            'materials' => [
                ['material_id' => $this->material->id, 'estimated_quantity' => 1]
            ],
            'special_services' => [
                [
                    'id' => $oss->id,
                    'service_id' => $this->specialService->id,
                    'notes' => 'To be cancelled',
                    'cancelled' => 1
                ]
            ],
        ])
        ->assertRedirect(route('orders.index'));

    $oss->refresh();
    expect($oss->cancelled_at)->not->toBeNull();
});
