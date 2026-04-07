<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_files', function (Blueprint $table) {
            $table->timestamp('downloaded_at')->nullable()->after('expires_at');
            $table->string('key_hash', 64)->nullable()->after('downloaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('shared_files', function (Blueprint $table) {
            $table->dropColumn(['downloaded_at', 'key_hash']);
        });
    }
};
