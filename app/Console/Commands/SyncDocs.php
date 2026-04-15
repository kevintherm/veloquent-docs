<?php

namespace App\Console\Commands;

use App\Models\Doc;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

#[Signature('docs:sync {--branch=}')]
#[Description('Sync docs from a source repository branch into the database')]
class SyncDocs extends Command
{
    public function handle()
    {
        $branch = $this->option('branch');

        if (! $branch) {
            $this->error('The --branch option is required.');

            return self::FAILURE;
        }

        $repositoryUrl = config('docs.repository');

        if (! $repositoryUrl) {
            $this->error('No repository URL configured. Set DOCS_REPOSITORY_URL in .env');

            return self::FAILURE;
        }

        $version = $this->determineVersion($branch);

        $this->info("Syncing docs from branch: {$branch} -> version: {$version}");

        $tempDir = storage_path('app/docs-sync-'.time());
        $docsDir = $tempDir.'/docs';

        try {
            $this->cloneRepository($repositoryUrl, $branch, $tempDir);

            if (! is_dir($docsDir)) {
                throw new RuntimeException("No docs directory found in repository at branch {$branch}");
            }

            $this->syncDocsFromDirectory($docsDir, $version);

            $this->info("✓ Docs synced successfully for version: {$version}");

            return self::SUCCESS;
        } catch (RuntimeException $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            if (is_dir($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Determine version from branch name
     * Numeric-starting branches (1.x, 2.3) stay as-is
     * Non-numeric branches become dev-{branch}
     */
    public function determineVersion(string $branch): string
    {
        if (preg_match('/^\d/', $branch)) {
            return $branch;
        }

        return config('docs.dev_prefix').$branch;
    }

    /**
     * Clone the repository at the specified branch
     */
    private function cloneRepository(string $url, string $branch, string $targetDir): void
    {
        $command = sprintf(
            'git clone --depth 1 --branch %s %s %s 2>&1',
            escapeshellarg($branch),
            escapeshellarg($url),
            escapeshellarg($targetDir)
        );

        $output = [];
        $statusCode = 0;
        exec($command, $output, $statusCode);

        if ($statusCode !== 0) {
            throw new RuntimeException(
                'Failed to clone repository: '.implode("\n", $output)
            );
        }
    }

    /**
     * Sync documentation files from directory
     */
    private function syncDocsFromDirectory(string $docsDir, string $version): void
    {
        $files = File::allFiles($docsDir);
        $syncedSlugs = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            // Skip index.md files
            if ($file->getFilename() === 'index.md') {
                continue;
            }

            $relativePath = str_replace($docsDir.'/', '', $file->getPathname());
            $slug = $version.'/'.str_replace('.md', '', $relativePath);

            $content = File::get($file->getPathname());

            // Extract title from first # heading
            preg_match('/^# (.+?)$/m', $content, $titleMatch);
            $title = trim($titleMatch[1] ?? $slug);

            // Extract all headings (##, ###, etc) - include level 2+ for TOC
            $headings = [];
            preg_match_all('/^(#{2,6}) +(.+?)$/m', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $headingText = trim($match[2]);
                $level = strlen($match[1]); // Length of # symbols

                // Create slug-friendly ID
                $id = strtolower($headingText);
                $id = preg_replace('/[^a-z0-9]+/', '-', $id);
                $id = trim($id, '-');

                $headings[] = [
                    'text' => $headingText,
                    'level' => $level,
                    'id' => 'content-'.$id,
                ];
            }

            $searchableContent = $this->generateSearchableContent($content);

            Doc::updateOrCreate(
                ['version' => $version, 'slug' => $slug],
                [
                    'title' => $title,
                    'content' => $content,
                    'searchable_content' => $searchableContent,
                    'headings' => $headings,
                ]
            );

            $syncedSlugs[] = $slug;

            $this->line("  ✓ {$slug}");
        }

        Doc::where('version', $version)
            ->whereNotIn('slug', $syncedSlugs)
            ->delete();

        $this->deleteLocalDocsForVersion($version);
    }

    /**
     * Delete local docs folder for this version (DB is now source of truth)
     */
    private function deleteLocalDocsForVersion(string $version): void
    {
        $localDocsPath = base_path('docs/'.$version);

        if (is_dir($localDocsPath)) {
            File::deleteDirectory($localDocsPath);
            $this->line("  🗑️  Deleted local docs/{$version}/ (DB is source of truth)");
        }
    }

    /**
     * Generate searchable content for indexing
     * Strips markdown syntax and truncates to keep record size under Algolia limit
     */
    private function generateSearchableContent(string $rawMarkdown): string
    {
        // Remove code blocks
        $content = preg_replace('/```[\s\S]*?```/', '', $rawMarkdown);

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

        // Clean up whitespace
        $content = preg_replace('/\s+/', ' ', trim($content));

        // Truncate to 5000 chars to keep record size under 10KB limit
        return substr($content, 0, 5000);
    }
}
