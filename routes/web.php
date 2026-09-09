<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Convenient redirects for Swagger / OpenAPI documentation
Route::redirect('/swagger', '/docs/api');
Route::redirect('/api/documentation', '/docs/api');
Route::redirect('/api/docs', '/docs/api');
