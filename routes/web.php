<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-error', function () {
    return response()->json([
        'app_key_set' => !empty(config('app.key')),
        'db_connection' => config('database.default'),
        'db_url_set' => !empty(env('DB_URL')),
        'app_env' => config('app.env'),
        'storage_writable' => is_writable(storage_path()),
    ]);
});

Route::get('/', function () {
    return view('welcome');
});
