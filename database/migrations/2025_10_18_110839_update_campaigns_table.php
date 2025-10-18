<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Add wa_session_id to link campaign to specific device
            $table->foreignId('wa_session_id')->nullable()->after('user_id')
                ->constrained('wa_sessions')
                ->onDelete('set null');
        });

        Schema::table('messages', function (Blueprint $table) {
            // Add wa_session_id to track which device sent the message
            $table->foreignId('wa_session_id')->nullable()->after('campaign_id')
                ->constrained('wa_sessions')
                ->onDelete('set null');
        });

        Schema::table('recipients', function (Blueprint $table) {
            // Add index for faster lookups
            $table->index(['user_id', 'phone_raw']);
            $table->index(['user_id', 'phone_e164']);
            $table->index(['user_id', 'first_name']);
            $table->index(['user_id', 'last_name']);
            $table->index(['user_id', 'first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['wa_session_id']);
            $table->dropColumn('wa_session_id');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['wa_session_id']);
            $table->dropColumn('wa_session_id');
        });

        Schema::table('recipients', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'phone']);
        });
    }
};
