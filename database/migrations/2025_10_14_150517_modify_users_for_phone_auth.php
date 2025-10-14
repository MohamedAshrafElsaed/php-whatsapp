<?php
// database/migrations/2025_10_14_000001_modify_users_for_phone_auth.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add phone authentication fields
            $table->string('country_code', 5)->nullable()->after('email');
            $table->string('phone', 20)->nullable()->after('country_code');
            $table->boolean('phone_verified')->default(false)->after('phone');

            // Make email nullable
            $table->string('email')->nullable()->change();

            // Add unique index on country_code + phone combination
            $table->unique(['country_code', 'phone'], 'users_country_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_country_phone_unique');
            $table->dropColumn(['country_code', 'phone', 'phone_verified']);
        });
    }
};
