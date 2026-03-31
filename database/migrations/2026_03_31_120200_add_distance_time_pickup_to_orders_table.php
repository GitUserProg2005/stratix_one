<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('distance_pickup', 10, 2)->nullable()->after('price');
            $table->integer('time_pickup')->nullable()->after('distance_pickup');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['distance_pickup', 'time_pickup']);
        });
    }
};
