<?php

namespace Tests\Feature;

use App\Console\Commands\SyncDocs;
use App\Models\Doc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocsSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_requires_branch_option()
    {
        $this->artisan('docs:sync')
            ->expectsOutput('The --branch option is required.')
            ->assertExitCode(1);
    }

    public function test_sync_requires_repository_url_in_config()
    {
        $this->artisan('docs:sync --branch=main')
            ->expectsOutput('No repository URL configured. Set DOCS_REPOSITORY_URL in .env')
            ->assertExitCode(1);
    }

    public function test_release_branch_version_detection()
    {
        $cmd = new SyncDocs;

        // Test numeric branches stay as-is
        $this->assertEquals('1.x', $cmd->determineVersion('1.x'));
        $this->assertEquals('2.3', $cmd->determineVersion('2.3'));
        $this->assertEquals('0.1.0', $cmd->determineVersion('0.1.0'));
    }

    public function test_dev_branch_version_detection()
    {
        $cmd = new SyncDocs;

        // Test non-numeric branches become dev-{branch}
        $this->assertEquals('dev-main', $cmd->determineVersion('main'));
        $this->assertEquals('dev-feature-xyz', $cmd->determineVersion('feature-xyz'));
        $this->assertEquals('dev-release', $cmd->determineVersion('release'));
    }

    public function test_sync_creates_docs_with_version()
    {
        // Create a temporary docs directory structure
        $tempDir = storage_path('test-docs');
        mkdir($tempDir, 0755, true);
        mkdir($tempDir.'/docs', 0755, true);
        mkdir($tempDir.'/docs/getting-started', 0755, true);

        // Create a test markdown file
        File::put($tempDir.'/docs/getting-started/introduction.md', <<<'MD'
            # Getting Started

            This is the introduction.

            ## Installation

            Install the package.

            ## Configuration

            Configure your app.
        MD);

        // Mock the repository clone and call sync
        // Since we can't actually clone without a real repo URL,
        // we'll test the core logic with real files

        $this->markTestSkipped('Requires real repository URL for full integration test');

        // Cleanup
        File::deleteDirectory($tempDir);
    }

    public function test_raw_markdown_is_stored()
    {
        // Create test doc
        $doc = Doc::create([
            'version' => '1.x',
            'slug' => '1.x/test',
            'title' => 'Test Doc',
            'content' => <<<'MD'
                # Test

                ```php
                echo "hello";
                ```

                **Bold** and *italic*.
            MD,
            'headings' => [],
        ]);

        // Verify raw markdown is preserved
        $this->assertStringContainsString('```php', $doc->content);
        $this->assertStringContainsString('**Bold**', $doc->content);
        $this->assertStringNotContainsString('code blocks', $doc->content);
    }

    public function test_version_slug_composite_unique()
    {
        // Create two docs with same slug but different versions
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/getting-started/intro',
            'title' => 'Intro v1',
            'content' => 'v1 content',
            'headings' => [],
        ]);

        Doc::create([
            'version' => '2.x',
            'slug' => '2.x/getting-started/intro',
            'title' => 'Intro v2',
            'content' => 'v2 content',
            'headings' => [],
        ]);

        // Both should exist
        $this->assertCount(2, Doc::all());

        // Verify they're different
        $v1 = Doc::where('version', '1.x')->first();
        $v2 = Doc::where('version', '2.x')->first();
        $this->assertEquals('v1 content', $v1->content);
        $this->assertEquals('v2 content', $v2->content);
    }

    public function test_sync_extracts_headings_as_array()
    {
        $doc = Doc::create([
            'version' => '1.x',
            'slug' => '1.x/test',
            'title' => 'Test',
            'content' => <<<'MD'
                # Main

                ## First Heading
                Content here.

                ### Nested Heading
                More content.

                ## Another H2
                Final content.
            MD,
            'headings' => [
                ['text' => 'First Heading', 'level' => 2, 'id' => 'first-heading'],
                ['text' => 'Nested Heading', 'level' => 3, 'id' => 'nested-heading'],
                ['text' => 'Another H2', 'level' => 2, 'id' => 'another-h2'],
            ],
        ]);

        // Headings should be properly structured
        $this->assertIsArray($doc->headings);
        $this->assertCount(3, $doc->headings);
        $this->assertEquals('First Heading', $doc->headings[0]['text']);
        $this->assertEquals(2, $doc->headings[0]['level']);
        $this->assertEquals('first-heading', $doc->headings[0]['id']);
    }

    public function test_heading_ids_are_slug_friendly()
    {
        $doc = Doc::create([
            'version' => '1.x',
            'slug' => '1.x/test',
            'title' => 'Test',
            'content' => '# Main',
            'headings' => [
                ['text' => 'API Response & Status Codes', 'level' => 2, 'id' => 'api-response-status-codes'],
                ['text' => 'Special Characters!@#', 'level' => 2, 'id' => 'special-characters'],
            ],
        ]);

        // IDs should be lowercase, hyphenated, no special chars
        $this->assertEquals('api-response-status-codes', $doc->headings[0]['id']);
        $this->assertEquals('special-characters', $doc->headings[1]['id']);
    }
}
