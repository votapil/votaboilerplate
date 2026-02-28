<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'VotaBoilerplate API is running']);
});

Route::mcp('/mcp/app', \App\Mcp\AppServer::class);
