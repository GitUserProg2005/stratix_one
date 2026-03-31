<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_territories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['tournament_id', 'driver_id']);
        });

        DB::statement('ALTER TABLE driver_territories ADD COLUMN geom geometry(Polygon, 4326)');
        DB::statement('CREATE INDEX driver_territories_geom_gix ON driver_territories USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_territories');
    }
};

