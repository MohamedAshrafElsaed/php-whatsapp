<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipient_segment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['segment_id', 'recipient_id']);
            $table->index('segment_id');
            $table->index('recipient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipient_segment');
    }
};
