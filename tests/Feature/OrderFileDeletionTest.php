<?php

use App\Models\FileType;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->adminRole = Role::firstOrCreate(['name' => 'Admin']);
    \App\Models\RolePermission::updateOrCreate(
        ['role_id' => $this->adminRole->id, 'resource_type' => 'orders'],
        ['can_edit' => true, 'can_view' => true]
    );

    $this->admin = User::factory()->create(['role_id' => $this->adminRole->id]);
    $this->user = User::factory()->create();

    $this->order = Order::factory()->create();
    $this->fileType = FileType::firstOrCreate(['name' => 'Evidencia']);

    $this->orderFile = OrderFile::create([
        'order_id' => $this->order->id,
        'file_type_id' => $this->fileType->id,
        'file_path' => 'evidence/test.jpg',
        'uploaded_by' => $this->user->id,
    ]);

    Storage::disk('public')->put('evidence/test.jpg', 'fake content');
});

it('allows an admin to delete an order file', function () {
    actingAs($this->admin);

    $response = $this->delete(route('order-files.destroy', $this->orderFile));

    $response->assertStatus(302);
    $response->assertSessionHas('status', 'Archivo eliminado correctamente.');

    $this->assertDatabaseMissing('order_files', ['id' => $this->orderFile->id]);
    Storage::disk('public')->assertMissing('evidence/test.jpg');
});

it('allows the uploader to delete their own file', function () {
    actingAs($this->user);

    $response = $this->delete(route('order-files.destroy', $this->orderFile));

    $response->assertStatus(302);
    $this->assertDatabaseMissing('order_files', ['id' => $this->orderFile->id]);
});

it('denies a non-admin from deleting another user\'s file', function () {
    $anotherUser = User::factory()->create();
    actingAs($anotherUser);

    $response = $this->delete(route('order-files.destroy', $this->orderFile));

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['auth']);
    $this->assertDatabaseHas('order_files', ['id' => $this->orderFile->id]);
});

it('allows deletion even if the order is already delivered', function () {
    $this->order->update(['delivered_at' => now()]);
    actingAs($this->admin);

    $response = $this->delete(route('order-files.destroy', $this->orderFile));

    $response->assertStatus(302);
    $response->assertSessionHas('status', 'Archivo eliminado correctamente.');
    $this->assertDatabaseMissing('order_files', ['id' => $this->orderFile->id]);
});
