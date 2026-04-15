<?php

namespace App\Docs;

use App\Models\Doc;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

class DocsManager
{
    /**
     * Remove .md extensions from links in HTML content.
     * Allows .md to only be used when explicitly requested via query parameter or file extension.
     */
    public function removeMarkdownExtensionsFromLinks(string $html): string
    {
        return preg_replace('/(href=["\'](?:[^"\']*))\.md(["\'])/i', '$1$2', $html);
    }

    /**
     * Get the markdown converter instance.
     */
    public function getConverter(): MarkdownConverter
    {
        $config = [
            'heading_permalink' => [
                'html_class' => 'heading-permalink',
                'id_prefix' => 'content',
                'apply_id_to_heading' => true,
                'heading_class' => '',
                'fragment_prefix' => 'content',
                'insert' => 'before',
                'min_heading_level' => 1,
                'max_heading_level' => 6,
                'title' => 'Permalink',
                'symbol' => '#',
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new AttributesExtension);

        return new MarkdownConverter($environment);
    }

    /**
     * Get all available versions, sorted with releases first
     */
    public function getAvailableVersions(): Collection
    {
        $versions = Doc::distinct('version')
            ->orderByDesc('version')
            ->pluck('version');

        // Separate releases (numeric) from dev (dev-*)
        $releases = $versions->filter(fn ($v) => ! str_starts_with($v, 'dev-'))
            ->sort()
            ->reverse()
            ->values();

        $devVersions = $versions->filter(fn ($v) => str_starts_with($v, 'dev-'))
            ->sort()
            ->values();

        return $releases->concat($devVersions);
    }

    /**
     * Get formatted label for a version
     */
    public function getVersionLabel(string $version): string
    {
        if (str_starts_with($version, 'dev-')) {
            $branch = substr($version, strlen('dev-'));

            return Str::headline(str_replace('-', ' ', $branch));
        }

        return $version;
    }

    /**
     * Get the sidebar navigation structure for a specific version.
     */
    public function getSidebar(?string $version = null): Collection
    {
        if (! $version) {
            return collect();
        }

        $order = [
            'getting-started',
            'architecture-concepts',
            'the-basics',
            'security',
            'database',
            'api-documentation',
            'realtime',
            'changelog',
        ];

        $docs = Doc::forVersion($version)
            ->orderBy('slug')
            ->get();

        $groupedDocs = $docs->groupBy(function (Doc $doc) {
            // Extract first segment: version/segment/rest -> segment
            $parts = explode('/', $doc->slug);

            return $parts[1] ?? 'root';
        });

        $categories = [];

        foreach ($groupedDocs as $category => $categoryDocs) {
            $files = [];
            foreach ($categoryDocs as $doc) {
                $files[] = [
                    'name' => basename(str_replace($version.'/', '', $doc->slug)),
                    'title' => $doc->title,
                    'path' => str_replace($version.'/', '', $doc->slug),
                ];
            }

            usort($files, fn ($a, $b) => strcmp($a['name'], $b['name']));

            if (! empty($files)) {
                $categories[$category] = [
                    'title' => $this->formatTitle($category),
                    'files' => $files,
                ];
            }
        }

        $sorted = [];
        foreach ($order as $key) {
            if (isset($categories[$key])) {
                $sorted[$key] = $categories[$key];
            }
        }

        // Add any remaining categories not in order
        foreach ($categories as $key => $value) {
            if (! isset($sorted[$key])) {
                $sorted[$key] = $value;
            }
        }

        return collect($sorted);
    }

    /**
     * Search documentation files.
     * If version is specified, only search within that version.
     */
    public function search(string $query, ?string $version = null): Collection
    {
        if (empty($query)) {
            return collect();
        }

        $cacheKey = $version
            ? "doc_search_{$version}_".Str::slug($query)
            : 'doc_search_'.Str::slug($query);

        $results = Cache::remember($cacheKey, now()->addWeek(), function () use ($query, $version) {
            $docs = Doc::search($query)->take(10)->get();

            // Filter by version in PHP instead of using Scout's where()
            // to avoid Algolia filter syntax issues
            if ($version) {
                $docs = $docs->filter(fn (Doc $doc) => $doc->version === $version);
            }

            return $docs->map(function (Doc $doc) use ($query) {
                $cleanedContent = $this->stripMarkdown($doc->content);
                $snippet = $this->extractHighlightedSnippet($cleanedContent, $query);

                return [
                    'title' => $doc->title,
                    'path' => str_replace(explode('/', $doc->slug)[0].'/', '', $doc->slug),
                    'version' => $doc->version,
                    'snippet' => $snippet,
                ];
            })->toArray();
        });

        return collect($results);
    }

    /**
     * Strip markdown syntax from content for clean display
     */
    protected function stripMarkdown(string $content): string
    {
        // Remove code blocks
        $content = preg_replace('/```[\s\S]*?```/', '', $content);

        // Remove inline code
        $content = preg_replace('/`([^`]*)`/', '$1', $content);

        // Remove markdown link syntax [text](url)
        $content = preg_replace('/\[([^\]]*)\]\([^\)]*\)/', '$1', $content);

        // Remove bold/italic markdown
        $content = preg_replace('/\*\*([^\*]*)\*\*/', '$1', $content);
        $content = preg_replace('/\*([^\*]*)\*/', '$1', $content);
        $content = preg_replace('/__([^_]*)__/', '$1', $content);
        $content = preg_replace('/_([^_]*)_/', '$1', $content);

        // Remove headings but keep content
        $content = preg_replace('/^#+\s+/m', '', $content);

        // Remove blockquote markers
        $content = preg_replace('/^>\s+/m', '', $content);

        return trim($content);
    }

    /**
     * Extract a highlighted snippet from content around search query keywords.
     * Content should already be cleaned of markdown by stripMarkdown()
     */
    protected function extractHighlightedSnippet(string $content, string $query): string
    {
        $contextLength = 100;

        $position = stripos($content, $query);

        if ($position === false) {
            return Str::limit($content, 200);
        }

        $start = max(0, $position - $contextLength);
        $end = min(strlen($content), $position + strlen($query) + $contextLength);
        $snippet = substr($content, $start, $end - $start);

        if ($start > 0) {
            $snippet = '...'.$snippet;
        }
        if ($end < strlen($content)) {
            $snippet = $snippet.'...';
        }

        $snippet = preg_replace(
            '/'.preg_quote($query, '/').'/iu',
            '<mark>$0</mark>',
            $snippet
        );

        return $snippet;
    }

    /**
     * Format a string into a title.
     */
    protected function formatTitle(string $value): string
    {
        return Str::headline(str_replace('-', ' ', $value));
    }

    /**
     * Get the latest stable (non-dev) version.
     */
    public function getLatestStableVersion(): ?string
    {
        return $this->getAvailableVersions()
            ->filter(fn ($v) => ! str_starts_with($v, 'dev-'))
            ->first();
    }

    /**
     * Generate a meta description from content.
     */
    public function generateDescription(string $content, int $limit = 160): string
    {
        $stripped = $this->stripMarkdown($content);

        return Str::limit($stripped, $limit);
    }
}
