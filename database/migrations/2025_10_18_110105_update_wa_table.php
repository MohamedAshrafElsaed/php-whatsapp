<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_sessions', function (Blueprint $table) {
            // Add unique device identifier
            $table->string('device_id')->unique()->after('user_id');

            // Add device label for user identification
            $table->string('device_label')->nullable()->after('device_id');

            // Store bridge instance information
            $table->string('bridge_instance_url')->nullable()->after('device_label');
            $table->integer('bridge_instance_port')->nullable()->after('bridge_instance_url');

            // Add is_primary flag to identify main account
            $table->boolean('is_primary')->default(false)->after('bridge_instance_port');

            // Remove unique constraint from user_id
//            $table->dropUnique(['user_id']);

            // Add composite unique constraint
            $table->unique(['user_id', 'device_id']);

            // Add index for faster lookups
            $table->index(['user_id', 'status']);
        });

        // Update existing records with device_id
        DB::table('wa_sessions')->whereNull('device_id')->update([
            'device_id' => DB::raw("CONCAT('device_', id)"),
            'device_label' => 'Primary Device',
            'is_primary' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('wa_sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropUnique(['user_id', 'device_id']);
            $table->dropColumn([
                'device_id',
                'device_label',
                'bridge_instance_url',
                'bridge_instance_port',
                'is_primary',
            ]);
            $table->unique('user_id');
        });
    }
};
