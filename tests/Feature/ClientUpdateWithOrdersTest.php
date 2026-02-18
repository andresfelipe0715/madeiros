<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Setup Admin user with permissions
    $role = Role::create(['name' => 'Admin']);

    // Application uses RoleClientPermission
    \App\Models\RoleClientPermission::create([
        'role_id' => $role->id,
        'can_view' => true,
        'can_create' => true,
        'can_edit' => true,
    ]);

    $this->user = User::factory()->create(['role_id' => $role->id]);
});

it('allows updating client name and phone even if orders exist', function () {
    $client = Client::factory()->create([
        'name' => 'Original Name',
        'document' => '123456',
        'phone' => '111111'
    ]);

    Order::factory()->create(['client_id' => $client->id]);

    actingAs($this->user)
        ->put(route('clients.update', $client), [
            'name' => 'Updated Name',
            'phone' => '222222',
            // document is OMITTED (disabled in UI)
        ])
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $client->refresh();
    expect($client->name)->toBe('Updated Name');
    expect($client->phone)->toBe('222222');
    expect($client->document)->toBe('123456');
});

it('prevents updating document if orders exist', function () {
    $client = Client::factory()->create([
        'name' => 'Original Name',
        'document' => '123456'
    ]);

    Order::factory()->create(['client_id' => $client->id]);

    actingAs($this->user)
        ->put(route('clients.update', $client), [
            'name' => 'Updated Name',
            'document' => '999999', // document is CHANGED
        ])
        ->assertSessionHasErrors(['document']);

    $client->refresh();
    expect($client->document)->toBe('123456');
});

it('allows updating document if NO orders exist', function () {
    $client = Client::factory()->create([
        'name' => 'Original Name',
        'document' => '123456'
    ]);

    // No orders created

    actingAs($this->user)
        ->put(route('clients.update', $client), [
            'name' => 'Updated Name',
            'document' => '999999',
        ])
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success');

    $client->refresh();
    expect($client->document)->toBe('999999');
});
