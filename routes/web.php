<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/openapi.yaml', function () {
    $path = base_path('docs/openapi.yaml');
    abort_unless(file_exists($path), 404);

    return response(file_get_contents($path), 200, [
        'Content-Type' => 'text/yaml; charset=utf-8',
        'Content-Disposition' => 'inline; filename="openapi.yaml"',
    ]);
})->name('docs.openapi');

Route::get('/docs/swagger-ui', function () {
    return view('docs.swagger');
})->name('docs.swagger');

Route::get('/docs/integration-guide', function () {
    $path = base_path('docs/API_INTEGRATION_GUIDE.md');
    abort_unless(file_exists($path), 404);

    return response(file_get_contents($path), 200, [
        'Content-Type' => 'text/markdown; charset=utf-8',
        'Content-Disposition' => 'inline; filename="API_INTEGRATION_GUIDE.md"',
    ]);
})->name('docs.guide');
