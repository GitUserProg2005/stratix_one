<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes_rate', function (Blueprint $table) {
            $table->foreignId('rate_id')->constrained('rates')->cascadeOnDelete();
            $table->foreignId('node_type_id')->constrained('node_types')->cascadeOnDelete();

            $table->primary(['rate_id', 'node_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes_rate');
    }
};
