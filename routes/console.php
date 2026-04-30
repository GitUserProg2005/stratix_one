<?php

use App\Jobs\ExecuteWorkflow;
use App\Models\WorkflowTimer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (Schema::hasTable('workflow_timers')) {
    foreach (WorkflowTimer::all() as $timer) {
        Schedule::call(function () use ($timer) {
            ExecuteWorkflow::dispatch((int) $timer->workflow_id, (int) $timer->node_id);
        })
            ->cron($timer->cron)
            ->name("wf_timer_{$timer->workflow_id}_{$timer->node_id}")
            ->withoutOverlapping();
    }
}
