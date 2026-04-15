<?php

namespace App\Console\Commands;

use App\Models\Doc;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('docs:sync')]
#[Description('Sync docs from the docs directory into the database')]
class SyncDocs extends Command
{
    public function handle()
    {
        $path = base_path('docs');

        $files = File::allFiles($path);

        $existingSlugs = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $relativePath = str_replace($path . '/', '', $file->getPathname());

            $slug = str_replace('.md', '', $relativePath);

            $content = File::get($file->getPathname());

            // remove code blocks
            $content = preg_replace('/```[\s\S]*?```/', '', $content);

            // remove markdown symbols
            $content = preg_replace('/[#>*_`]/', '', $content);

            // extract title (# Title)
            preg_match('/^# (.+)$/m', $content, $titleMatch);
            $title = $titleMatch[1] ?? $slug;

            // extract headings (##, ###)
            preg_match_all('/^##+ (.+)$/m', $content, $headingMatches);

            $doc = Doc::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'content' => $content,
                    'headings' => $headingMatches[1] ?? [],
                ]
            );

            $existingSlugs[] = $slug;
        }

        // delete removed docs
        Doc::whereNotIn('slug', $existingSlugs)->delete();

        $this->info('Docs synced successfully.');
    }
}
