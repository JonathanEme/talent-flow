<?php

use Illuminate\Support\Facades\Route;

Route::get('/api-docs.json', function () {
    return response()->file(base_path('docs/openapi.json'), [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'no-store',
    ]);
});

Route::get('/api-docs', function () {
    return view('api-docs');
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
