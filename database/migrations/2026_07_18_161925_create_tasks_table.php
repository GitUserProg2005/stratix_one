<?php

use App\Enums\TaskDifficulty;
use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->nestedSet();
            $table->string('title');
            $table->timestamp('due_at')->nullable();
            $table->string('status')->default(TaskStatus::Started->value);
            $table->string('difficulty')->default(TaskDifficulty::Normal->value);
            $table->timestamps();
        });

        Schema::create('task_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_workers');
        Schema::dropIfExists('tasks');
    }
};
