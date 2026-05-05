<?php

use App\Docs\DocsManager;
use App\Http\Controllers\DocumentationController;
use App\Models\Doc;
use Illuminate\Support\Facades\Route;

$path = config('docs.path', 'docs');

Route::prefix($path)->group(function () use ($path) {
    // Home redirect - goes to latest version
    Route::get('/', function (DocsManager $docsManager) use ($path) {
        $latestVersion = $docsManager->getAvailableVersions()->first();

        if (! $latestVersion) {
            abort(404);
        }

        return redirect("{$path}/{$latestVersion}/getting-started/introduction");
    })->name('docs.home');

    // Search endpoint (searches across all versions)
    Route::get('/search', [DocumentationController::class, 'search'])
        ->name('docs.search')
        ->middleware('throttle:search');

    Route::get('/{section}', function ($section, DocsManager $docsManager) use ($path) {
        $versions = $docsManager->getAvailableVersions();

        foreach ($versions as $version) {
            $match = Doc::forVersion($version)
                ->where('slug', 'like', "%{$section}%")
                ->first();

            if ($match) {
                return redirect("{$path}/{$match->slug}");
            }
        }

        return redirect()->back();
    })->where('section', '^(?!search$)[a-z\-]+$')->name('docs.shortcut');

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
