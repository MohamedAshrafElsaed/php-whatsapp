<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Add wa_session_id to link campaign to specific device
            if (!Schema::hasColumn('campaigns', 'wa_session_id')) {
                $table->foreignId('wa_session_id')->nullable()->after('user_id')
                    ->constrained('wa_sessions')
                    ->onDelete('set null');
            }

            // Add additional fields if they don't exist
            if (!Schema::hasColumn('campaigns', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('campaigns', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('finished_at');
            }

            if (!Schema::hasColumn('campaigns', 'total_recipients')) {
                $table->integer('total_recipients')->default(0)->after('throttling_cfg_json');
            }

            if (!Schema::hasColumn('campaigns', 'sent_count')) {
                $table->integer('sent_count')->default(0)->after('total_recipients');
            }

            if (!Schema::hasColumn('campaigns', 'failed_count')) {
                $table->integer('failed_count')->default(0)->after('sent_count');
            }

            if (!Schema::hasColumn('campaigns', 'settings_json')) {
                $table->json('settings_json')->nullable()->after('throttling_cfg_json');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            // Add wa_session_id to track which device sent the message
            if (!Schema::hasColumn('messages', 'wa_session_id')) {
                $table->foreignId('wa_session_id')->nullable()->after('campaign_id')
                    ->constrained('wa_sessions')
                    ->onDelete('set null');
            }
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
