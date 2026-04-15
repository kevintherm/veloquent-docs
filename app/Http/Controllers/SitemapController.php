<?php

namespace App\Http\Controllers;

use App\Docs\DocsManager;
use App\Models\Doc;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(DocsManager $docsManager): Response
    {
        $latestVersion = $docsManager->getLatestStableVersion();

        $urls = [
            route('home') => ['priority' => '1.0', 'changefreq' => 'daily'],
        ];

        // Add FAQ if the view exists
        if (view()->exists('faq')) {
            $urls[url('faq')] = ['priority' => '0.7', 'changefreq' => 'monthly'];
        }

        if ($latestVersion) {
            $docs = Doc::where('version', $latestVersion)->get();
            foreach ($docs as $doc) {
                $path = str_replace($latestVersion.'/', '', $doc->slug);
                $url = route('docs.show', ['version' => $latestVersion, 'file' => $path]);
                $urls[$url] = ['priority' => '0.8', 'changefreq' => 'weekly'];
            }
        }

        return response()->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'text/xml');
    }
}
