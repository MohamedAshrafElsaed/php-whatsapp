<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('wa_auto_reply_enabled')->default(false)->after('email');
            $table->text('wa_auto_reply_message')->nullable()->after('wa_auto_reply_enabled');
            $table->boolean('wa_auto_mark_read')->default(false)->after('wa_auto_reply_message');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'wa_auto_reply_enabled',
                'wa_auto_reply_message',
                'wa_auto_mark_read',
            ]);
        });
    }
};
