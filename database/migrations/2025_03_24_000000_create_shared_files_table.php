<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shared_files', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique(); // Unique token for accessing the file
            $table->string('file_path'); // S3 path to the encrypted file
            $table->string('file_name'); // Original filename for display
            $table->string('file_size'); // Size of the file
            $table->string('iv'); // Initialization Vector used for encryption
            $table->timestamp('expires_at'); // When the file access expires
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_files');
    }
};