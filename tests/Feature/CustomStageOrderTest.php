<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Material;
use App\Models\Order;
use App\Models\Stage;
use App\Models\StageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomStageOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Client $client;

    protected Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed');

        $this->admin = User::where('name', 'Admin Test')->first();
        $this->client = Client::first();

        // DefaultDataSeeder doesn't create materials, so we create one using the factory
        $this->material = Material::factory()->create([
            'stock_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        // Ensure there is at least one active order file type for order creation if needed
        \Illuminate\Support\Facades\DB::table('file_types')->updateOrInsert(['name' => 'archivo_orden']);
    }

    public function test_order_creation_with_custom_substage_ordering(): void
    {
        $enchapeGroup = StageGroup::where('name', 'Enchape')->first();
        $enchapeStages = $enchapeGroup->stages()->orderBy('default_sequence')->get();
        $enchapeStage1 = $enchapeStages[0];
        $enchapeStage2 = $enchapeStages[1];

        $corteStage = StageGroup::where('name', 'Corte')->first()->stages()->first();

        // Normally sequence is Corte(10) -> Enchape 1(20) -> Enchape 2(30)
        // We will send payload as Corte -> Enchape 2 -> Enchape 1 (swapping enchapes)

        $payload = [
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-CUSTOM-'.rand(100, 999),
            'lleva_herrajeria' => false,
            'lleva_manual_armado' => false,
            'materials' => [
                [
                    'material_id' => $this->material->id,
                    'estimated_quantity' => 10,
                ],
            ],
            'stages' => [
                ['stage_id' => $corteStage->id, 'sequence' => 1],
                ['stage_id' => $enchapeStage2->id, 'sequence' => 2], // Custom order: Enchape 2 before Enchape 1!
                ['stage_id' => $enchapeStage1->id, 'sequence' => 3], // Custom order!
            ],
        ];
        $response = $this->actingAs($this->admin)->post(route('orders.store'), $payload);

        $response->assertRedirect(route('orders.index'));

        $order = Order::where('invoice_number', $payload['invoice_number'])->first();
        $this->assertNotNull($order);

        $orderStages = $order->orderStages()->orderBy('sequence')->get();
        $this->assertCount(3, $orderStages);

        $this->assertEquals($corteStage->id, $orderStages[0]->stage_id);
        $this->assertEquals($enchapeStage2->id, $orderStages[1]->stage_id);
        $this->assertEquals($enchapeStage1->id, $orderStages[2]->stage_id);

        $this->assertEquals(1, $orderStages[0]->sequence);
        $this->assertEquals(2, $orderStages[1]->sequence);
        $this->assertEquals(3, $orderStages[2]->sequence);
    }

    public function test_rejects_non_contiguous_sequences(): void
    {
        $corteStage = StageGroup::where('name', 'Corte')->first()->stages()->first();
        $enchapeStage = StageGroup::where('name', 'Enchape')->first()->stages()->first();

        $payload = [
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-TEST-GAP-'.rand(100, 999),
            'materials' => [
                ['material_id' => $this->material->id, 'estimated_quantity' => 10],
            ],
            'stages' => [
                ['stage_id' => $corteStage->id, 'sequence' => 1],
                ['stage_id' => $enchapeStage->id, 'sequence' => 3], // Gap! Missing 2
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('orders.store'), $payload);

        $response->assertSessionHasErrors('stages');
    }
}
