<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone_raw');
            $table->string('phone_e164')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->json('extra_json')->nullable();
            $table->boolean('is_valid')->default(false);
            $table->json('validation_errors_json')->nullable();
            $table->timestamps();

            $table->index('import_id');
            $table->index('user_id');
            $table->index('phone_e164');
            $table->index('is_valid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipients');
    }
};
