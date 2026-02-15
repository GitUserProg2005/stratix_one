<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reposted_snippets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('snippet_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'snippet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reposted_snippets');
    }
};
