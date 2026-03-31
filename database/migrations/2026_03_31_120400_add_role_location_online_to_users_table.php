<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('passenger')->after('password');
            $table->decimal('lat', 10, 8)->nullable()->after('role');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');
            $table->boolean('is_online')->default(false)->after('lng');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'lat', 'lng', 'is_online']);
        });
    }
};
