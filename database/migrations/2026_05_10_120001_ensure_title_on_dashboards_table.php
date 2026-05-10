<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dashboards')) {
            return;
        }

        if (Schema::hasColumn('dashboards', 'title')) {
            return;
        }

        Schema::table('dashboards', function (Blueprint $table) {
            $table->string('title')->default('')->after('workflow_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('dashboards')) {
            return;
        }

        if (!Schema::hasColumn('dashboards', 'title')) {
            return;
        }

        Schema::table('dashboards', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};

