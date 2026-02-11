<?php

namespace App\Console\Commands;

use App\Models\SharedFile;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteExpiredFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired shared files from storage and database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredFiles = SharedFile::where('expires_at', '<', Carbon::now())->get();

        $disk = config('filesystems.default');
        $count = 0;

        foreach ($expiredFiles as $file) {
            // Delete the file from configured storage disk
            if (Storage::disk($disk)->exists($file->file_path)) {
                Storage::disk($disk)->delete($file->file_path);
            }

            // Delete the record from the database
            $file->delete();
            $count++;
        }

        $this->info("Deleted {$count} expired shared files.");
        return Command::SUCCESS;
    }
}
