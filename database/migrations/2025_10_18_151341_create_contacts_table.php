<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wa_session_id')->constrained()->onDelete('cascade');

            $table->string('jid')->index(); // WhatsApp JID (e.g., 201120133111@s.whatsapp.net)
            $table->string('phone_raw')->nullable(); // Raw phone number extracted from JID
            $table->string('phone_e164')->nullable()->index(); // E.164 formatted phone
            $table->string('first_name')->nullable()->index();
            $table->string('last_name')->nullable()->index();
            $table->string('full_name')->nullable()->index(); // Store the original name from WhatsApp
            $table->boolean('is_valid')->default(true); // Whether the phone number is valid

            $table->timestamps();

            // Unique constraint to prevent duplicate contacts per user/session
            $table->unique(['user_id', 'wa_session_id', 'jid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
