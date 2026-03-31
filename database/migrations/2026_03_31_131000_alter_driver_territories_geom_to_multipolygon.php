<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE driver_territories ALTER COLUMN geom TYPE geometry(MultiPolygon, 4326) USING ST_Multi(geom)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE driver_territories ALTER COLUMN geom TYPE geometry(Polygon, 4326) USING ST_GeometryN(geom, 1)');
    }
};

