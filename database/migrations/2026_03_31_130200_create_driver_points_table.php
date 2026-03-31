<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE driver_points ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX driver_points_geom_gix ON driver_points USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_points');
    }
};

