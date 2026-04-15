<?php

namespace App\Docs;

use App\Models\Doc;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\MarkdownConverter;

class DocsManager
{
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
     * Get the sidebar navigation structure.
     */
    public function getSidebar(): Collection
    {
        $docsPath = base_path('docs');
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

        $categories = collect(File::directories($docsPath))
            ->mapWithKeys(function ($directory) {
                $folderName = basename($directory);
                $files = collect(File::files($directory))
                    ->filter(fn ($file) => $file->getExtension() === 'md')
                    ->map(fn ($file) => [
                        'name' => basename($file, '.md'),
                        'title' => $this->formatTitle(basename($file, '.md')),
                        'path' => $folderName.'/'.basename($file, '.md'),
                    ])
                    ->sortBy('name')
                    ->values();

                if ($files->isEmpty()) {
                    return [];
                }

                return [$folderName => [
                    'title' => $this->formatTitle($folderName),
                    'files' => $files,
                ]];
            })
            ->sortBy(function ($item, $key) use ($order) {
                $pos = array_search($key, $order);

                return $pos === false ? 999 : $pos;
            });

        return $categories;
    }

    /**
     * Search documentation files.
     */
    public function search(string $query): Collection
    {
        if (empty($query)) {
            return collect();
        }

        $cacheKey = 'doc_search_'.Str::slug($query);

        $cached = Cache::remember($cacheKey, now()->addWeek(), function () use ($query) {
            return Doc::search($query)->take(limit: 10)->get()->map(function (Doc $doc) use ($query) {
                $snippet = $this->extractHighlightedSnippet($doc->content, $query);

                return [
                    'title' => $doc->title,
                    'path' => $doc->slug,
                    'snippet' => $snippet,
                ];
            })->values()->all();
        });

        return collect($cached ?? []);
    }

    /**
     * Extract a highlighted snippet from content around search query keywords.
     */
    protected function extractHighlightedSnippet(string $content, string $query): string
    {
        $contextLength = 100;

        // Find first occurrence (case-insensitive)
        $position = stripos($content, $query);

        if ($position === false) {
            return Str::limit($content, 200);
        }

        // Extract context window around the match
        $start = max(0, $position - $contextLength);
        $end = min(strlen($content), $position + strlen($query) + $contextLength);
        $snippet = substr($content, $start, $end - $start);

        // Add ellipsis if truncated
        if ($start > 0) {
            $snippet = '...'.$snippet;
        }
        if ($end < strlen($content)) {
            $snippet = $snippet.'...';
        }

        // Highlight the query term (case-insensitive)
        $snippet = preg_replace(
            '/('.preg_quote($query, '/').')/iu',
            '<mark>$1</mark>',
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
}
