<?php

use App\Models\Client;
use App\Models\Material;
use App\Models\Order;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->role = Role::create(['name' => 'Admin']);
    $this->user = User::factory()->create(['role_id' => $this->role->id]);

    RolePermission::create([
        'role_id' => $this->role->id,
        'resource_type' => 'orders',
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);

    $this->stage1 = Stage::create(['name' => 'Corte', 'default_sequence' => 1]);
    $this->stage2 = Stage::create(['name' => 'Entrega', 'default_sequence' => 2, 'is_delivery_stage' => true]);

    $this->client = Client::create([
        'name' => 'Test Client',
        'document' => '123456789',
        'phone' => '555-1234',
    ]);

    $this->material = Material::create(['name' => 'Melamina Blanca', 'stock_quantity' => 100]);
});

it('can create an order with material notes', function () {
    actingAs($this->user);
    $note = 'Corte especial 45 grados';

    $response = post(route('orders.store'), [
        'client_id' => $this->client->id,
        'invoice_number' => 'FAC-999',
        'notes' => 'General order notes',
        'stages' => [
            ['stage_id' => $this->stage1->id, 'sequence' => 1],
            ['stage_id' => $this->stage2->id, 'sequence' => 2],
        ],
        'materials' => [
            [
                'material_id' => $this->material->id,
                'estimated_quantity' => 10,
                'notes' => $note,
            ],
        ],
    ]);

    $response->assertRedirect(route('orders.index'));

    $order = Order::latest()->first();
    $om = $order->orderMaterials()->where('material_id', $this->material->id)->first();

    expect($om->notes)->toBe($note);
    // Legacy field is not explicitly cleared in code, but we shouldn't be writing to it.
});

it('rejects materials notes longer than 50 characters', function () {
    actingAs($this->user);
    $longNote = str_repeat('a', 51);

    $response = post(route('orders.store'), [
        'client_id' => $this->client->id,
        'invoice_number' => 'FAC-ERR',
        'stages' => [
            ['stage_id' => $this->stage1->id, 'sequence' => 1],
        ],
        'materials' => [
            [
                'material_id' => $this->material->id,
                'estimated_quantity' => 1,
                'notes' => $longNote,
            ],
        ],
    ]);

    $response->assertSessionHasErrors(['materials.0.notes']);
});

it('displays material name and notes together in the index', function () {
    actingAs($this->user);

    $order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'FAC-DISPLAY',
        'created_by' => $this->user->id,
    ]);

    $order->orderMaterials()->create([
        'material_id' => $this->material->id,
        'estimated_quantity' => 5,
        'notes' => 'TEST NOTE',
    ]);

    $response = get(route('orders.index'));

    $response->assertSee('Melamina Blanca (5) - TEST NOTE');
});

it('can view the order edit page with materials', function () {
    actingAs($this->user);

    $order = Order::create([
        'client_id' => $this->client->id,
        'invoice_number' => 'FAC-EDIT-TEST',
        'created_by' => $this->user->id,
    ]);

    $response = get(route('orders.edit', $order));

    $response->assertSuccessful();
    $response->assertViewHas('materials');
    $response->assertSee('Melamina Blanca');
});
