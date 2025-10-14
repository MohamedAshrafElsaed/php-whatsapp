<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add auth_credentials column to store Baileys authentication data
     * This replaces file-based auth (useMultiFileAuthState) with database storage
     */
    public function up(): void
    {
        Schema::table('wa_sessions', function (Blueprint $table) {
            // Store Baileys credentials as encrypted JSON
            // Contains: creds.json data from Baileys authentication
            $table->text('auth_credentials')->nullable()->after('meta_json');

            // Track last successful heartbeat for health monitoring
            $table->timestamp('last_heartbeat_at')->nullable()->after('last_seen_at');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        Schema::table('wa_sessions', function (Blueprint $table) {
            $table->dropColumn(['auth_credentials', 'last_heartbeat_at']);
        });
    }
};
