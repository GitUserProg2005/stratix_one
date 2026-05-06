<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // We create dashboards not always tied to a workflow (manual metrics).
        // Postgres: make FK nullable by dropping constraint and NOT NULL.
        DB::statement('ALTER TABLE dashboards DROP CONSTRAINT IF EXISTS dashboards_workflow_id_foreign');
        DB::statement('ALTER TABLE dashboards ALTER COLUMN workflow_id DROP NOT NULL');
        DB::statement(
            'ALTER TABLE dashboards ADD CONSTRAINT dashboards_workflow_id_foreign FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE SET NULL',
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE dashboards DROP CONSTRAINT IF EXISTS dashboards_workflow_id_foreign');
        DB::statement('ALTER TABLE dashboards ALTER COLUMN workflow_id SET NOT NULL');
        DB::statement(
            'ALTER TABLE dashboards ADD CONSTRAINT dashboards_workflow_id_foreign FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE',
        );
    }
};
