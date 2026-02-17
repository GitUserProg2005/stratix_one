<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('days')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->unique('chat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_streaks');
    }
};
