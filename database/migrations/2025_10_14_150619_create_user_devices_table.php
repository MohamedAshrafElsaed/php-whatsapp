<?php
// database/migrations/2025_10_14_000003_create_user_devices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_fingerprint')->unique(); // Browser fingerprint
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->boolean('is_mobile')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('device_fingerprint');
            $table->index('last_used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
