<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Live events channel for a project (requires auth)
Broadcast::channel('project.{projectId}.events', function ($user, $projectId) {
    return $user->projects()->where('id', $projectId)->exists();
});

// Live crashes channel
Broadcast::channel('project.{projectId}.crashes', function ($user, $projectId) {
    return $user->projects()->where('id', $projectId)->exists();
});
