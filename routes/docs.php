<?php

use App\Http\Controllers\DocumentationController;
use App\Models\Doc;
use Illuminate\Support\Facades\Route;

$path = config('docs.path', 'docs');

Route::prefix($path)->group(function () use ($path) {
    // Home redirect - goes to latest version
    Route::get('/', function () use ($path) {
        $latestVersion = Doc::distinct('version')
            ->orderByDesc('version')
            ->first()?->version;

        if (! $latestVersion) {
            return redirect("{$path}/");
        }

        return redirect("{$path}/{$latestVersion}/getting-started/introduction");
    })->name('docs.home');

    // Search endpoint (searches across all versions)
    Route::get('/search', [DocumentationController::class, 'search'])
        ->name('docs.search')
        ->middleware('throttle:search');

    // Versioned docs routes
    Route::prefix('{version}')->group(function () use ($path) {
        // Version home redirect
        Route::get('/', function ($version) use ($path) {
            return redirect("{$path}/{$version}/getting-started/introduction");
        });

        // Show doc page
        Route::get('/{file?}', [DocumentationController::class, 'show'])
            ->name('docs.show')
            ->where('file', '.*');
    });
});
