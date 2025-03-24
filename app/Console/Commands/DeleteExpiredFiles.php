<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SharedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

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
    protected $description = 'Delete expired shared files from S3 and database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredFiles = SharedFile::where('expires_at', '<', Carbon::now())->get();
        
        $count = 0;
        foreach ($expiredFiles as $file) {
            // Delete the file from S3
            if (Storage::disk('s3')->exists($file->file_path)) {
                Storage::disk('s3')->delete($file->file_path);
            }
            
            // Delete the record from the database
            $file->delete();
            $count++;
        }
        
        $this->info("Deleted {$count} expired shared files.");
        return Command::SUCCESS;
    }
}