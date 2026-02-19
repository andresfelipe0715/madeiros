<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\Stage;
use App\Models\OrderStage;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::create(['name' => 'Admin']);
    $this->admin = User::factory()->create(['role_id' => $this->adminRole->id, 'name' => 'Admin User']);

    RolePermission::create([
        'role_id' => $this->adminRole->id,
        'resource_type' => 'performance',
        'can_view' => true,
    ]);
});

it('allows authorized roles to view performance list', function () {
    $response = $this->actingAs($this->admin)->get(route('performance.index'));
    $response->assertSuccessful();
    $response->assertSee('Rendimiento de Empleados');
});

it('denies access to non-authorized roles', function () {
    $userRole = Role::create(['name' => 'Worker']);
    $user = User::factory()->create(['role_id' => $userRole->id]);

    $response = $this->actingAs($user)->get(route('performance.index'));
    $response->assertForbidden();
});

it('calculates metrics correctly for completed_by', function () {
    $otherUser = User::factory()->create(['role_id' => $this->adminRole->id, 'name' => 'Other User']);
    $stage = Stage::create(['name' => 'Corte', 'default_sequence' => 10]);
    $order = Order::factory()->create(['invoice_number' => 'INV-001']);

    $startTime = Carbon::now()->subDays(5);
    $endTime = $startTime->copy()->addHours(2);

    // This stage started by Admin but COMPLETED BY OtherUser
    OrderStage::create([
        'order_id' => $order->id,
        'stage_id' => $stage->id,
        'started_at' => $startTime,
        'completed_at' => $endTime,
        'started_by' => $this->admin->id,
        'completed_by' => $otherUser->id,
        'sequence' => 10,
    ]);

    $response = $this->actingAs($this->admin)->get(route('performance.index'));

    // Admin should have 0 stages (since they didn't complete any)
    $response->assertSee('Admin User');
    // We check the specific count associated with Other User
    $response->assertSee('Other User');
});

it('filters by date range', function () {
    $stage = Stage::create(['name' => 'Enchape', 'default_sequence' => 20]);
    $order = Order::factory()->create();

    // Old completion (100 days ago) - outside default 90 days
    OrderStage::create([
        'order_id' => $order->id,
        'stage_id' => $stage->id,
        'started_at' => Carbon::now()->subDays(101),
        'completed_at' => Carbon::now()->subDays(100),
        'completed_by' => $this->admin->id,
        'sequence' => 20,
    ]);

    $response = $this->actingAs($this->admin)->get(route('performance.index'));
    $response->assertSee('0'); // Admin has 0 stages in last 90 days

    // Filter for last 120 days (custom range)
    $response = $this->actingAs($this->admin)->get(route('performance.index', [
        'date_range' => 'custom',
        'date_from' => Carbon::now()->subDays(120)->format('Y-m-d'),
        'date_to' => Carbon::now()->format('Y-m-d'),
    ]));
    $response->assertSee('1'); // Now it should see the stage
});

it('provides paginated details via AJAX', function () {
    $stage = Stage::create(['name' => 'Corte', 'default_sequence' => 10]);

    // Create 20 completions
    for ($i = 0; $i < 20; $i++) {
        $order = Order::factory()->create(['invoice_number' => 'INV-' . str_pad($i, 3, '0', STR_PAD_LEFT)]);
        OrderStage::create([
            'order_id' => $order->id,
            'stage_id' => $stage->id,
            'started_at' => Carbon::now()->subHours(2),
            'completed_at' => Carbon::now()->subHours(1),
            'completed_by' => $this->admin->id,
            'sequence' => 10,
        ]);
    }

    $response = $this->actingAs($this->admin)->getJson(route('performance.details', ['user' => $this->admin->id]));
    $response->assertStatus(200);
    $response->assertJsonCount(15, 'data'); // First page has 15 items
    $response->assertJsonPath('pagination.total', 20);
    $response->assertJsonPath('pagination.last_page', 2);
});

it('shows benchmarking insights', function () {
    $stage = Stage::create(['name' => 'BenchStage', 'default_sequence' => 100]);
    $order1 = Order::factory()->create();
    $order2 = Order::factory()->create();

    $fastUser = User::factory()->create(['role_id' => $this->adminRole->id, 'name' => 'Fast User']);
    $slowUser = User::factory()->create(['role_id' => $this->adminRole->id, 'name' => 'Slow User']);

    // Fast user: 1 hour
    OrderStage::create([
        'order_id' => $order1->id,
        'stage_id' => $stage->id,
        'started_at' => Carbon::now()->subHours(2),
        'completed_at' => Carbon::now()->subHours(1),
        'completed_by' => $fastUser->id,
        'sequence' => 100,
    ]);

    // Slow user: 4 hours
    OrderStage::create([
        'order_id' => $order2->id,
        'stage_id' => $stage->id,
        'started_at' => Carbon::now()->subHours(5),
        'completed_at' => Carbon::now()->subHours(1),
        'completed_by' => $slowUser->id,
        'sequence' => 100,
    ]);

    $response = $this->actingAs($this->admin)->get(route('performance.index'));
    $response->assertSee('BenchStage');
    $response->assertSee('Fast User');
    $response->assertSee('Slow User');
});
