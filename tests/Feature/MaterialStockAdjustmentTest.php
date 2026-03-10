<?php

use App\Models\Material;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup Admin with edit-materials permission
    $this->adminRole = Role::create(['name' => 'Admin']);
    $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);

    RolePermission::create([
        'role_id' => $this->adminRole->id,
        'resource_type' => 'materials',
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);

    // Setup basic user without edit permission
    $this->basicRole = Role::create(['name' => 'Basic']);
    $this->basicUser = User::factory()->create(['role_id' => $this->basicRole->id]);

    $this->material = Material::factory()->create([
        'name' => 'Test Material',
        'stock_quantity' => 50,
        'reserved_quantity' => 10,
    ]);
});

it('allows authorized users to adjust material stock', function () {
    $this->actingAs($this->admin)
        ->post(route('materials.adjust', $this->material), [
            'adjusted_stock' => 120.50,
            'reason' => 'Inventory count correction',
        ])
        ->assertRedirect(route('materials.index'))
        ->assertSessionHas('success');

    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(120.50);

    $log = \App\Models\InventoryLog::first();
    expect($log)->not->toBeNull();
    expect($log->material_id)->toBe($this->material->id);
    expect($log->user_id)->toBe($this->admin->id);
    expect($log->action)->toBe('adjustment');
    expect((float) $log->previous_stock_quantity)->toBe(50.0);
    expect((float) $log->new_stock_quantity)->toBe(120.50);
    expect($log->notes)->toBe('Inventory count correction');
});

it('prevents unauthorized users from adjusting material stock', function () {
    $this->actingAs($this->basicUser)
        ->post(route('materials.adjust', $this->material), [
            'adjusted_stock' => 120,
            'reason' => 'Unauthorized attempt',
        ])
        ->assertForbidden();

    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(50.0);
});

it('validates adjusted_stock input', function ($invalidValue) {
    $this->actingAs($this->admin)
        ->post(route('materials.adjust', $this->material), [
            'adjusted_stock' => $invalidValue,
            'reason' => 'Valid reason',
        ])
        ->assertSessionHasErrors('adjusted_stock');

    $this->material->refresh();
    expect((float) $this->material->stock_quantity)->toBe(50.0);
})->with([
            'null value' => null,
            'negative value' => -10,
            'string value' => 'abc',
        ]);

it('validates reason input', function ($invalidReason) {
    $this->actingAs($this->admin)
        ->post(route('materials.adjust', $this->material), [
            'adjusted_stock' => 60,
            'reason' => $invalidReason,
        ])
        ->assertSessionHasErrors('reason');
})->with([
            'null value' => null,
            'empty string' => '',
            'too long' => str_repeat('a', 301),
        ]);
