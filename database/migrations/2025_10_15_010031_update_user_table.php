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
        Schema::table('users', function (Blueprint $table) {
            // Add new columns
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('industry')->nullable()->after('email');

            // Make email nullable
            $table->string('email')->nullable()->change();

            // Drop name column if it exists (will be replaced by first_name + last_name)
            // Note: If you want to migrate existing data, do it before dropping
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore name column
            $table->string('name')->after('id');

            // Remove new columns
            $table->dropColumn(['first_name', 'last_name', 'industry']);

            // Make email required again
            $table->string('email')->nullable(false)->change();
        });
    }
};
