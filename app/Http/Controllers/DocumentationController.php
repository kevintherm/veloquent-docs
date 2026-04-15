<?php

namespace App\Http\Controllers;

use App\Docs\DocsManager;
use App\Models\Doc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentationController extends Controller
{
    public function __construct(protected DocsManager $docsManager) {}

    public function show(Request $request, string $version, string $file = 'index')
    {
        Log::info("show() called with version={$version}, file={$file}");

        $file = Str::replaceLast('.md', '', $file);

        $slug = $version.'/'.$file;

        $doc = Doc::forVersion($version)
            ->where('slug', $slug)
            ->first();

        if (! $doc) {
            abort(404);
        }

        if (Str::endsWith($request->getRequestUri(), '.md') || $request->query('raw')) {
            return response($doc->content)->header('Content-Type', 'text/markdown; charset=utf-8');
        }

        $headingAnchor = $request->query('heading');

        $converter = $this->docsManager->getConverter();
        $htmlContent = (string) $converter->convert($doc->content);
        $htmlContent = $this->docsManager->removeMarkdownExtensionsFromLinks($htmlContent);

        $availableVersions = $this->docsManager->getAvailableVersions();

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

        $results = $this->docsManager->search($query, $version);

        $availableVersions = $this->docsManager->getAvailableVersions();

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
