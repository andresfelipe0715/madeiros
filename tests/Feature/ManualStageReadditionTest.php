<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualStageReadditionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $adminRole = Role::create(['name' => 'Admin']);
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        \App\Models\RolePermission::create([
            'role_id' => $adminRole->id,
            'resource_type' => 'orders',
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
        ]);

        $this->client = Client::factory()->create();
    }

    public function test_stages_are_appended_to_the_end_when_no_delivery_stage_exists(): void
    {
        $stage1 = Stage::create(['name' => 'Stage 1', 'default_sequence' => 10]);
        $stage2 = Stage::create(['name' => 'Stage 2', 'default_sequence' => 20]);

        $order = Order::create([
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-APPEND',
            'created_by' => $this->admin->id,
        ]);

        $order->orderStages()->create(['stage_id' => $stage1->id, 'sequence' => 1]);

        $this->actingAs($this->admin)->post(route('orders.add-stage', $order), ['stage_id' => $stage2->id]);

        $orderStages = $order->orderStages()->orderBy('sequence')->get();
        $this->assertCount(2, $orderStages);
        $this->assertEquals($stage1->id, $orderStages[0]->stage_id);
        $this->assertEquals($stage2->id, $orderStages[1]->stage_id);
    }

    public function test_stages_are_inserted_before_delivery_stage(): void
    {
        // 1. Create a normal stage and a delivery stage
        $corteStage = Stage::create(['name' => 'Corte', 'default_sequence' => 10, 'is_delivery_stage' => false]);
        $deliveryStage = Stage::create(['name' => 'Entrega', 'default_sequence' => 60, 'is_delivery_stage' => true]);
        $enchapeStage = Stage::create(['name' => 'Enchape', 'default_sequence' => 20, 'is_delivery_stage' => false]);

        // 2. Create an order with existing workflow: [Corte, Entrega]
        $order = Order::create([
            'client_id' => $this->client->id,
            'invoice_number' => 'INV-WITH-DELIVERY',
            'created_by' => $this->admin->id,
        ]);

        $order->orderStages()->create(['stage_id' => $corteStage->id, 'sequence' => 1]);
        $order->orderStages()->create(['stage_id' => $deliveryStage->id, 'sequence' => 2]);

        // 3. Add "Enchape". 
        // Desired behavior: It should go at sequence 2, and "Entrega" should move to 3.
        $this->actingAs($this->admin)->post(route('orders.add-stage', $order), [
            'stage_id' => $enchapeStage->id,
        ]);

        $orderStages = $order->orderStages()->orderBy('sequence')->get();
        
        $this->assertCount(3, $orderStages);
        
        // Final sequence should be: Corte(1), Enchape(2), Entrega(3)
        $this->assertEquals($corteStage->id, $orderStages[0]->stage_id);
        $this->assertEquals(1, $orderStages[0]->sequence);

        $this->assertEquals($enchapeStage->id, $orderStages[1]->stage_id);
        $this->assertEquals(2, $orderStages[1]->sequence);

        $this->assertEquals($deliveryStage->id, $orderStages[2]->stage_id);
        $this->assertEquals(3, $orderStages[2]->sequence);
    }
}
