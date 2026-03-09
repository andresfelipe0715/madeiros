<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\User;

it('can create a user', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    expect($user)->toBeInstanceOf(User::class);
});

it('can create a client', function () {
    $client = Client::factory()->create();
    expect($client)->toBeInstanceOf(Client::class);
});
