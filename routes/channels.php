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

Broadcast::channel('workflow-completed.{workflowId}', function ($user, $workflowId) {
    return $user !== null;
});

Broadcast::channel('workflow-presence.{workflowId}', function ($user, $workflowId) {
    return ['id' => $user->id, 'name' => $user->name, 'avatar_url' => $user->avatar_url];
});

Broadcast::channel('workflow-updated.{workflowId}', function ($user, $workflowId) {
    return $user !== null;
});

Broadcast::channel('ai-chat-states.{roomId}', function ($user, $roomId) {
    if ($user === null) {
        return false;
    }

    return \App\Models\Room::query()
        ->whereKey($roomId)
        ->where('owner_id', $user->id)
        ->exists();
});