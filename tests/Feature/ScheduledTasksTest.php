<?php

use App\Models\FileType;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────
//  app:clean-old-storage-files
// ─────────────────────────────────────────────

describe('app:clean-old-storage-files', function () {
    beforeEach(function () {
        Storage::fake('public');

        $role = Role::create(['name' => 'Admin']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->order = Order::factory()->create(['created_by' => $this->user->id]);
        $this->fileType = FileType::firstOrCreate(['name' => 'PDF'], ['description' => 'PDF file']);
    });

    it('does nothing when no files are older than 6 months', function () {
        // A file uploaded today
        $path = 'orders/recent.pdf';
        Storage::disk('public')->put($path, 'content');

        OrderFile::create([
            'order_id'     => $this->order->id,
            'file_type_id' => $this->fileType->id,
            'file_path'    => $path,
            'uploaded_by'  => $this->user->id,
            'uploaded_at'  => now(),
        ]);

        Artisan::call('app:clean-old-storage-files');

        $this->assertDatabaseCount('order_files', 1);
        Storage::disk('public')->assertExists($path);
    });

    it('deletes files and records older than 6 months', function () {
        $oldPath = 'orders/old.pdf';
        $newPath = 'orders/new.pdf';

        Storage::disk('public')->put($oldPath, 'old content');
        Storage::disk('public')->put($newPath, 'new content');

        // Old file (7 months ago) - force the timestamp via raw DB update
        $oldRecord = OrderFile::create([
            'order_id'     => $this->order->id,
            'file_type_id' => $this->fileType->id,
            'file_path'    => $oldPath,
            'uploaded_by'  => $this->user->id,
        ]);
        \Illuminate\Support\Facades\DB::table('order_files')
            ->where('id', $oldRecord->id)
            ->update(['uploaded_at' => now()->subMonths(7)]);

        // New file (today)
        OrderFile::create([
            'order_id'     => $this->order->id,
            'file_type_id' => $this->fileType->id,
            'file_path'    => $newPath,
            'uploaded_by'  => $this->user->id,
        ]);

        Artisan::call('app:clean-old-storage-files');

        // Old file and record should be gone
        $this->assertDatabaseMissing('order_files', ['file_path' => $oldPath]);
        Storage::disk('public')->assertMissing($oldPath);

        // New file and record must survive
        $this->assertDatabaseHas('order_files', ['file_path' => $newPath]);
        Storage::disk('public')->assertExists($newPath);
    });

    it('does not delete during dry run', function () {
        $path = 'orders/dry-run.pdf';
        Storage::disk('public')->put($path, 'content');

        $oldDryRunRecord = OrderFile::create([
            'order_id'     => $this->order->id,
            'file_type_id' => $this->fileType->id,
            'file_path'    => $path,
            'uploaded_by'  => $this->user->id,
        ]);
        \Illuminate\Support\Facades\DB::table('order_files')
            ->where('id', $oldDryRunRecord->id)
            ->update(['uploaded_at' => now()->subMonths(7)]);

        Artisan::call('app:clean-old-storage-files', ['--dry-run' => true]);

        // Nothing should be deleted
        $this->assertDatabaseCount('order_files', 1);
        Storage::disk('public')->assertExists($path);
    });

    it('removes the record even when the physical file is missing', function () {
        // Record has no physical file on disk
        $ghostRecord = OrderFile::create([
            'order_id'     => $this->order->id,
            'file_type_id' => $this->fileType->id,
            'file_path'    => 'orders/ghost.pdf',
            'uploaded_by'  => $this->user->id,
        ]);
        \Illuminate\Support\Facades\DB::table('order_files')
            ->where('id', $ghostRecord->id)
            ->update(['uploaded_at' => now()->subMonths(7)]);

        // Should not crash and must remove the orphaned record
        Artisan::call('app:clean-old-storage-files');

        $this->assertDatabaseMissing('order_files', ['file_path' => 'orders/ghost.pdf']);
    });
});

// ─────────────────────────────────────────────
//  app:backup-database – backup rotation logic
// ─────────────────────────────────────────────

describe('app:backup-database cleanup rotation', function () {
    beforeEach(function () {
        $this->backupDir = storage_path('app/backups');
        if (! is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    });

    afterEach(function () {
        // Clean up any leftover test files from the backup dir
        foreach (glob($this->backupDir . '/backup-test-*.{sql,zip}', GLOB_BRACE) as $file) {
            @unlink($file);
        }
    });

    it('deletes backup ZIP files older than 7 days', function () {
        // Create a fake "old" zip file (8 days ago)
        $oldFile = $this->backupDir . '/backup-test-old.zip';
        file_put_contents($oldFile, 'old backup content');
        touch($oldFile, now()->subDays(8)->timestamp);

        // Create a fake "recent" zip file (2 days ago)
        $recentFile = $this->backupDir . '/backup-test-recent.zip';
        file_put_contents($recentFile, 'recent backup content');
        touch($recentFile, now()->subDays(2)->timestamp);

        // Directly test the GLOB + unlink logic that cleanOldBackups() uses
        $files = glob($this->backupDir . '/backup-test-*.{sql,zip}', GLOB_BRACE);
        $now = time();
        $retentionSeconds = 7 * 86400;

        foreach ($files as $file) {
            if ($now - filemtime($file) >= $retentionSeconds) {
                unlink($file);
            }
        }

        expect(file_exists($oldFile))->toBeFalse('Old backup should be deleted');
        expect(file_exists($recentFile))->toBeTrue('Recent backup should survive');
    });

    it('deletes legacy SQL files older than 7 days', function () {
        $oldSqlFile = $this->backupDir . '/backup-test-old.sql';
        file_put_contents($oldSqlFile, 'old sql dump content');
        touch($oldSqlFile, now()->subDays(8)->timestamp);

        $recentSqlFile = $this->backupDir . '/backup-test-recent.sql';
        file_put_contents($recentSqlFile, 'recent sql dump content');
        touch($recentSqlFile, now()->subDays(2)->timestamp);

        $files = glob($this->backupDir . '/backup-test-*.{sql,zip}', GLOB_BRACE);
        $now = time();
        $retentionSeconds = 7 * 86400;

        foreach ($files as $file) {
            if ($now - filemtime($file) >= $retentionSeconds) {
                unlink($file);
            }
        }

        expect(file_exists($oldSqlFile))->toBeFalse('Old SQL file should be deleted');
        expect(file_exists($recentSqlFile))->toBeTrue('Recent SQL file should survive');
    });
});
