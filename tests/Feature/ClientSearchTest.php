<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $role = Role::firstOrCreate(['name' => 'Admin']);
    $this->user = User::factory()->create(['role_id' => $role->id]);

    Client::factory()->create(['name' => 'Alice Smith', 'document' => '12345']);
    Client::factory()->create(['name' => 'Bob Johnson', 'document' => '67890']);
});

it('can search clients by name', function () {
    actingAs($this->user);
    $response = $this->getJson(route('clients.search', ['q' => 'Alice']));

    $response->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonPath('0.name', 'Alice Smith')
        ->assertJsonPath('0.document', '12345');
});

it('can search clients by document', function () {
    actingAs($this->user);
    $response = $this->getJson(route('clients.search', ['q' => '6789']));

    $response->assertStatus(200)
        ->assertJsonCount(1)
        ->assertJsonPath('0.name', 'Bob Johnson')
        ->assertJsonPath('0.document', '67890');
});

it('returns multiple results if matches', function () {
    Client::factory()->create(['name' => 'Charlie Smith', 'document' => '11111']);

    actingAs($this->user);
    $response = $this->getJson(route('clients.search', ['q' => 'Smith']));

    $response->assertStatus(200)
        ->assertJsonCount(2);
});
