<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_timers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->string('node_id');
            $table->string('cron');
            $table->timestamps();

            $table->index('workflow_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_timers');
    }
};
