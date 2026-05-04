<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('faq', FaqController::class)->name('faq');

Route::get('sitemap.xml', SitemapController::class)->name('seo.sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/docs.php';
require __DIR__.'/settings.php';
