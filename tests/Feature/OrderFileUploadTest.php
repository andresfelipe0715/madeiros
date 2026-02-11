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
    \App\Models\RoleOrderPermission::updateOrCreate(
        ['role_id' => $this->role->id],
        ['can_create' => true, 'can_view' => true]
    );
});

it('can upload a PDF file when creating an order', function () {
    $this->actingAs($this->user);

    $file = UploadedFile::fake()->create('order.pdf', 500, 'application/pdf');

    $response = $this->post(route('orders.store'), [
        'client_id' => $this->client->id,
        'invoice_number' => 'TEST-PDF-123',
        'material' => 'Test Material',
        'stages' => [$this->stage->id],
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
        'material' => 'Test Material',
        'stages' => [$this->stage->id],
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
        'material' => 'Test Material',
        'stages' => [$this->stage->id],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('orders', ['invoice_number' => 'TEST-NO-FILE']);

    $order = Order::where('invoice_number', 'TEST-NO-FILE')->first();
    $this->assertCount(0, $order->orderFiles);
});
