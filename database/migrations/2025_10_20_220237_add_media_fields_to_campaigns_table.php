<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('message_type', [
                'text',
                'image',
                'video',
                'audio',
                'file',
                'link',
                'location',
                'contact',
                'poll'
            ])->default('text')->after('message_template');

            $table->string('media_path')->nullable()->after('message_type');
            $table->string('media_filename')->nullable()->after('media_path');
            $table->string('media_mime_type')->nullable()->after('media_filename');
            $table->text('caption')->nullable()->after('media_mime_type');

            // For link messages
            $table->text('link_url')->nullable()->after('caption');

            // For location messages
            $table->decimal('latitude', 10, 8)->nullable()->after('link_url');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            // For contact messages
            $table->string('contact_name')->nullable()->after('longitude');
            $table->string('contact_phone')->nullable()->after('contact_name');

            // For poll messages
            $table->string('poll_question')->nullable()->after('contact_phone');
            $table->json('poll_options')->nullable()->after('poll_question');
            $table->integer('poll_max_answer')->nullable()->after('poll_options');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'message_type',
                'media_path',
                'media_filename',
                'media_mime_type',
                'caption',
                'link_url',
                'latitude',
                'longitude',
                'contact_name',
                'contact_phone',
                'poll_question',
                'poll_options',
                'poll_max_answer',
            ]);
        });
    }
};
