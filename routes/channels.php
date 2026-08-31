<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast channels
|--------------------------------------------------------------------------
|
| Authorization callbacks for private/presence channels. The template ships
| no broadcast driver — this file exists so that adding one is a config
| change, not a bootstrap change. The private user channel below is the one
| Laravel's own notification broadcasting expects.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, int $id): bool {
    return (int) $user->id === $id;
});
