<?php

namespace App\Console\Commands;

use App\Models\OrderFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOldStorageFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-old-storage-files {--dry-run : Print the files that would be deleted without actually deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes records and physical files from order_files that are older than 6 months.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $sixMonthsAgo = now()->subMonths(6);
        $dryRun = $this->option('dry-run');

        $files = OrderFile::where('uploaded_at', '<', $sixMonthsAgo)->get();

        if ($files->isEmpty()) {
            $this->info('No files older than 6 months found.');

            return;
        }

        $this->info("Found {$files->count()} files older than 6 months.");

        foreach ($files as $file) {
            if ($dryRun) {
                $this->line("Would delete: {$file->file_path} (ID: {$file->id}, Uploaded: {$file->uploaded_at})");

                continue;
            }

            // Delete physical file from the public disk (where order files are stored)
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
                $this->line("Deleted file: {$file->file_path}");
            } else {
                $this->warn("File not found on disk: {$file->file_path}");
            }

            $file->delete();
            $this->line("Deleted record ID: {$file->id}");
        }

        if (! $dryRun) {
            $this->info('Cleanup completed successfully.');
        } else {
            $this->info('Dry run completed. No files were deleted.');
        }
    }
}
