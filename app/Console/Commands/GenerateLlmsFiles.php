<?php

namespace App\Console\Commands;

use App\Models\Doc;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('docs:generate-llms')]
#[Description('Generate llms-full.txt for the latest release version')]
class GenerateLlmsFiles extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $devPrefix = config('docs.dev_prefix', 'dev-');

        $versions = Doc::distinct()
            ->pluck('version')
            ->filter(function ($version) use ($devPrefix) {
                // Exclude dev branches and main
                return ! str_starts_with($version, $devPrefix) && $version !== 'main';
            })
            ->values()
            ->toArray();

        if (empty($versions)) {
            $this->error('No release versions found in the database.');

            return self::FAILURE;
        }

        // Sort versions descending to pick the latest
        usort($versions, function ($a, $b) {
            return version_compare($b, $a);
        });

        $latestVersion = $versions[0];

        $this->info("Latest release version identified: {$latestVersion}");

        $this->generateLlmsFullTxt($latestVersion);

        $this->info('✓ llms-full.txt generated successfully in public/');

        return self::SUCCESS;
    }

    /**
     * Generate the full llms-full.txt file
     */
    private function generateLlmsFullTxt(string $version): void
    {
        $docs = Doc::where('version', $version)
            ->orderBy('slug')
            ->get();

        $content = "# Veloquent Full Documentation (Version: {$version})\n\n";
        $content .= "Generated at: ".now()->toDateTimeString()."\n\n";
        $content .= "Veloquent is a laravel based backend as a service that integrate realtime database, authentication, file storage and admin dashboard.\n\n";

        foreach ($docs as $doc) {
            $content .= "--- FILE: {$doc->slug} ---\n";
            $content .= $doc->content."\n\n";
        }

        File::put(public_path('llms-full.txt'), $content);
    }
}
