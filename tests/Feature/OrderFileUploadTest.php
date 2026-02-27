<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    // Setup basic data
    $this->role = Role::firstOrCreate(['name' => 'Admin']);
    $this->user = User::factory()->create(['role_id' => $this->role->id]);
    $this->client = Client::factory()->create();
    $this->stage = Stage::firstOrCreate(['name' => 'Corte'], ['default_sequence' => 10]);
    // Ensure permission
    \App\Models\RolePermission::updateOrCreate(
        ['role_id' => $this->role->id, 'resource_type' => 'orders'],
        ['can_create' => true, 'can_view' => true, 'can_edit' => true]
    );

    $this->material = \App\Models\Material::factory()->create([
        'name' => 'Test Material',
        'stock_quantity' => 100,
    ]);
});

it('can upload a PDF file when creating an order', function () {
    $this->actingAs($this->user);

    $file = UploadedFile::fake()->create('order.pdf', 500, 'application/pdf');

    $response = $this->post(route('orders.store'), [
        'client_id' => $this->client->id,
        'invoice_number' => 'TEST-PDF-123',
        'materials' => [
            ['material_id' => $this->material->id, 'estimated_quantity' => 2],
        ],
        'stages' => [
            ['stage_id' => $this->stage->id, 'sequence' => 1],
        ],
        'order_file' => $file,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('orders', ['invoice_number' => 'TEST-PDF-123']);

    $order = Order::where('invoice_number', 'TEST-PDF-123')->first();

    $this->assertDatabaseHas('order_files', [
        'order_id' => $order->id,
        'uploaded_by' => $this->user->id,
    ]);

    $orderFile = $order->orderFiles->first();
    $this->assertNotEmpty($orderFile->file_path);
    $this->assertStringNotContainsString('http', $orderFile->file_path);
    $this->assertStringNotContainsString('/storage/', $orderFile->file_path);

    $this->assertNotEmpty($orderFile->file_url);
    // In tests, file_url might be /storage/... or http://... depending on env
    $this->assertTrue(
        str_contains($orderFile->file_url, 'http') || str_starts_with($orderFile->file_url, '/storage/')
    );

    // Check if file exists in fake storage using the relative path directly
    Storage::disk('public')->assertExists($orderFile->file_path);
});

it('rejects non-PDF files', function () {
    $this->actingAs($this->user);

    $file = UploadedFile::fake()->create('image.jpg', 500, 'image/jpeg');

    $response = $this->post(route('orders.store'), [
        'client_id' => $this->client->id,
        'invoice_number' => 'TEST-JPG-123',
        'materials' => [
            ['material_id' => $this->material->id, 'estimated_quantity' => 2],
        ],
        'stages' => [
            ['stage_id' => $this->stage->id, 'sequence' => 1],
        ],
        'order_file' => $file,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['order_file']);
    $this->assertDatabaseMissing('orders', ['invoice_number' => 'TEST-JPG-123']);
});

it('works without a file (optional)', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('orders.store'), [
        'client_id' => $this->client->id,
        'invoice_number' => 'TEST-NO-FILE',
        'materials' => [
            ['material_id' => $this->material->id, 'estimated_quantity' => 2],
        ],
        'stages' => [
            ['stage_id' => $this->stage->id, 'sequence' => 1],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('orders', ['invoice_number' => 'TEST-NO-FILE']);

    $order = Order::where('invoice_number', 'TEST-NO-FILE')->first();
    $this->assertCount(0, $order->orderFiles);
});

it('can replace an existing file in an order', function () {
    $this->actingAs($this->user);

    // 1. Create an order with an initial file
    $oldFile = UploadedFile::fake()->create('old.pdf', 100, 'application/pdf');
    $response = $this->post(route('orders.store'), [
        'client_id' => $this->client->id,
        'invoice_number' => 'REPLACE-TEST',
        'materials' => [
            ['material_id' => $this->material->id, 'estimated_quantity' => 2],
        ],
        'stages' => [
            ['stage_id' => $this->stage->id, 'sequence' => 1],
        ],
        'order_file' => $oldFile,
    ]);

    $order = Order::where('invoice_number', 'REPLACE-TEST')->first();
    $oldFilePath = $order->orderFiles->first()->file_path;
    Storage::disk('public')->assertExists($oldFilePath);

    // 2. Upload a NEW file as replacement
    $newFile = UploadedFile::fake()->create('new.pdf', 200, 'application/pdf');

    // We need to use the update route (OrderManagementController@update)
    $response = $this->put(route('orders.update', $order), [
        'invoice_number' => 'REPLACE-TEST', // Same number
        'materials' => [ // Pass current materials to satisfy adjust()
            ['id' => $order->orderMaterials->first()->id, 'material_id' => $this->material->id, 'estimated_quantity' => 2],
        ],
        'order_file' => $newFile,
    ]);

    $response->assertRedirect();

    // Refresh order and check files
    $order->refresh();
    $this->assertCount(1, $order->orderFiles);

    $newFilePath = $order->orderFiles->first()->file_path;
    $this->assertNotEquals($oldFilePath, $newFilePath);

    // Verify old file is GONE from storage
    Storage::disk('public')->assertMissing($oldFilePath);

    // Verify new file EXISTS
    Storage::disk('public')->assertExists($newFilePath);
});
