<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            $table->decimal('pickup_lat', 10, 8)->nullable()->after('driver_id');
            $table->decimal('pickup_lng', 11, 8)->nullable()->after('pickup_lat');
            $table->decimal('destination_lat', 10, 8)->nullable()->after('pickup_lng');
            $table->decimal('destination_lng', 11, 8)->nullable()->after('destination_lat');
            $table->timestamp('accepted_at')->nullable()->after('status');
            $table->timestamp('arrived_at')->nullable()->after('accepted_at');
            $table->timestamp('completed_at')->nullable()->after('arrived_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn([
                'driver_id',
                'pickup_lat',
                'pickup_lng',
                'destination_lat',
                'destination_lng',
                'accepted_at',
                'arrived_at',
                'completed_at',
            ]);
        });
    }
};
