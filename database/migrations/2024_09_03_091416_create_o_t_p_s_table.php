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
        Schema::create('otps', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('token')->unique(); // Unique token for each OTP
            $table->text('password'); // Encrypted password
            $table->string('iv'); // Initialization Vector for encryption
            $table->timestamp('expires_at'); // Expiration timestamp for OTP
            $table->timestamps(); // Created and updated timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
