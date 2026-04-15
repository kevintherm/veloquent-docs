<?php

namespace App\Http\Controllers;

use App\Docs\DocsManager;
use App\Models\Doc;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentationController extends Controller
{
    public function __construct(protected DocsManager $docsManager) {}

    public function show(Request $request, string $version, string $file = 'index')
    {
        \Log::info("show() called with version={$version}, file={$file}");
        
        $file = Str::replaceLast('.md', '', $file);

        // Build slug: version/path/to/file
        $slug = $version.'/'.$file;

        // Fetch doc from database
        $doc = Doc::forVersion($version)
            ->where('slug', $slug)
            ->first();

        if (! $doc) {
            abort(404);
        }

        // If requesting raw markdown
        if (Str::endsWith($request->getRequestUri(), '.md') || $request->query('raw')) {
            return response($doc->content)->header('Content-Type', 'text/markdown; charset=utf-8');
        }

        // Get heading anchor from query parameter (since URL fragments are client-side only)
        $headingAnchor = $request->query('heading');

        // Convert markdown to HTML
        $converter = $this->docsManager->getConverter();
        $htmlContent = (string) $converter->convert($doc->content);

        // Get available versions for switcher
        $availableVersions = $this->docsManager->getAvailableVersions();

        // Get sidebar for current version
        $sidebarCategories = $this->docsManager->getSidebar($version);

        return view('docs.viewer', [
            'content' => $htmlContent,
            'title' => $doc->title,
            'activeFile' => $file,
            'version' => $version,
            'availableVersions' => $availableVersions,
            'sidebarCategories' => $sidebarCategories,
            'headingAnchor' => $headingAnchor,
            'currentPath' => $file,
            'doc' => $doc,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->query('q', '');
        $version = $request->query('version');

        // If version specified, search only that version; otherwise search all
        $results = $this->docsManager->search($query, $version);

        $availableVersions = $this->docsManager->getAvailableVersions();

        // Get sidebar for current version (or first available if not specified)
        $sidebarVersion = $version ?? $availableVersions->first();
        $sidebarCategories = $this->docsManager->getSidebar($sidebarVersion);

        return view('docs.viewer', [
            'content' => null,
            'search_results' => $results,
            'search_query' => $query,
            'title' => 'Search Results',
            'activeFile' => null,
            'version' => $sidebarVersion,
            'availableVersions' => $availableVersions,
            'sidebarCategories' => $sidebarCategories,
        ]);
    }
}
