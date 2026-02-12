<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_listens', function (Blueprint $table) {
            $table->foreignId('snippet_id')->nullable()
                ->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_listens', function (Blueprint $table) {
            $table->dropForeign(['snippet_id']);
            $table->dropColumn('snippet_id');
        });
    }
};
