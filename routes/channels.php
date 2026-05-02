<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('workflow-step.{workflowId}', function ($user, $workflowId) {
    return $user !== null;
});

Broadcast::channel('workflow-diff-applied.{workflowId}', function ($user, $workflowId) {
    return $user !== null;
});

