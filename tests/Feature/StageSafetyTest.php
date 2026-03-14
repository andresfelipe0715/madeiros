<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\StageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup Admin Role & User
    $this->adminRole = Role::create(['name' => 'Admin Role']);
    RolePermission::create(['role_id' => $this->adminRole->id, 'resource_type' => 'orders', 'can_create' => true, 'can_edit' => true]);

    $this->adminUser = User::create([
        'name' => 'Admin User',
        'document' => 'ADM999',
        'password' => bcrypt('password'),
        'role_id' => $this->adminRole->id,
        'active' => true,
    ]);

    // Setup Groups
    $this->activeGroup = StageGroup::create(['name' => 'Active Group', 'active' => true]);
    $this->inactiveGroup = StageGroup::create(['name' => 'Inactive Group', 'active' => false]);
    $this->corteGroup = StageGroup::create(['name' => 'Corte', 'active' => true]);

    // Setup Stages
    $this->activeStage = Stage::create(['name' => 'Active Stage', 'stage_group_id' => $this->activeGroup->id, 'active' => true, 'default_sequence' => 20]);
    $this->inactiveStage = Stage::create(['name' => 'Inactive Stage', 'stage_group_id' => $this->activeGroup->id, 'active' => false, 'default_sequence' => 30]);
    $this->stageInInactiveGroup = Stage::create(['name' => 'Group Inactive Stage', 'stage_group_id' => $this->inactiveGroup->id, 'active' => true, 'default_sequence' => 40]);
    $this->corteStage = Stage::create(['name' => 'Corte', 'stage_group_id' => $this->corteGroup->id, 'active' => true, 'default_sequence' => 10]);

    Auth::login($this->adminUser);
});

it('hides inactive stages from order creation view', function () {
    $response = $this->get(route('orders.create'));

    $response->assertStatus(200);
    $response->assertSee($this->activeStage->name);
    $response->assertDontSee($this->inactiveStage->name);
});

it('hides stages belonging to inactive groups from order creation view', function () {
    $response = $this->get(route('orders.create'));

    $response->assertStatus(200);
    $response->assertDontSee($this->stageInInactiveGroup->name);
    $response->assertDontSee($this->inactiveGroup->name);
});

it('prevents selecting an inactive stage during order creation', function () {
    $response = $this->post(route('orders.store'), [
        'client_id' => Client::create(['name' => 'Test', 'document' => '1'])->id,
        'invoice_number' => 'INV-INACTIVE-STAGE',
        'stages' => [
            ['stage_id' => $this->corteStage->id, 'sequence' => 1],
            ['stage_id' => $this->inactiveStage->id, 'sequence' => 2],
        ],
        'materials' => [
            ['material_id' => \App\Models\Material::create(['name' => 'M1'])->id, 'estimated_quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors(['stages']);
});

it('prevents selecting a stage from an inactive group during order creation', function () {
    $response = $this->post(route('orders.store'), [
        'client_id' => Client::create(['name' => 'Test', 'document' => '2'])->id,
        'invoice_number' => 'INV-INACTIVE-GROUP',
        'stages' => [
            ['stage_id' => $this->corteStage->id, 'sequence' => 1],
            ['stage_id' => $this->stageInInactiveGroup->id, 'sequence' => 2],
        ],
        'materials' => [
            ['material_id' => \App\Models\Material::create(['name' => 'M2'])->id, 'estimated_quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors(['stages']);
});
