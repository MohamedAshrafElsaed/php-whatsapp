<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('import_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('message_template');
            $table->json('variables_json')->nullable();
            $table->enum('status', ['draft', 'running', 'paused', 'canceled', 'finished'])->default('draft');
            $table->json('throttling_cfg_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('import_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
