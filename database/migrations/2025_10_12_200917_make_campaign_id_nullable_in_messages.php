<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->foreignId('campaign_id')->nullable()->change()->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->foreignId('campaign_id')->nullable(false)->change()->constrained()->onDelete('cascade');
        });
    }
};
