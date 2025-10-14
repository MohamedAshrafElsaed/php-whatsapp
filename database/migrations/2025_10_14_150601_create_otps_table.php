<?php
// database/migrations/2025_10_14_000002_create_otps_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 5);
            $table->string('phone', 20);
            $table->string('otp_code', 6);
            $table->enum('type', ['register', 'login', 'verify'])->default('login');
            $table->boolean('verified')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['country_code', 'phone']);
            $table->index('expires_at');
            $table->index('verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
