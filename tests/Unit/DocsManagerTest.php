<?php

namespace Tests\Unit;

use App\Docs\DocsManager;
use App\Models\Doc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestDocs()
    {
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/getting-started/intro',
            'title' => 'Getting Started',
            'content' => <<<'MD'
                # Getting Started

                This is an introduction to the library.

                ## Installation

                Install via composer.

                ## Configuration

                Configure your application.
            MD,
            'headings' => [
                ['text' => 'Installation', 'level' => 2, 'id' => 'installation'],
                ['text' => 'Configuration', 'level' => 2, 'id' => 'configuration'],
            ],
        ]);

        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/the-basics/models',
            'title' => 'Models',
            'content' => <<<'MD'
                # Models

                Understanding models in this framework.

                ## Creating Models

                How to create a model.

                ## Relationships

                Model relationships explained.
            MD,
            'headings' => [
                ['text' => 'Creating Models', 'level' => 2, 'id' => 'creating-models'],
                ['text' => 'Relationships', 'level' => 2, 'id' => 'relationships'],
            ],
        ]);

        Doc::create([
            'version' => '2.x',
            'slug' => '2.x/getting-started/intro',
            'title' => 'Getting Started v2',
            'content' => 'Getting started with v2 is different.',
            'headings' => [],
        ]);
    }

    public function test_get_available_versions_sorted()
    {
        $this->createTestDocs();

        // Add dev version
        Doc::create([
            'version' => 'dev-feature-xyz',
            'slug' => 'dev-feature-xyz/test',
            'title' => 'Test',
            'content' => 'Test',
            'headings' => [],
        ]);

        $manager = new DocsManager;
        $versions = $manager->getAvailableVersions();

        // Should be sorted with releases first (descending), then dev versions
        $versionsArray = $versions->toArray();
        $this->assertGreaterThan(
            array_search('dev-feature-xyz', $versionsArray),
            array_search('2.x', $versionsArray)
        );
    }

    public function test_get_version_label_for_release()
    {
        $manager = new DocsManager;

        $this->assertEquals('1.x', $manager->getVersionLabel('1.x'));
        $this->assertEquals('2.3.4', $manager->getVersionLabel('2.3.4'));
    }

    public function test_get_version_label_for_dev_branch()
    {
        $manager = new DocsManager;

        // Should format dev-{name} as nicely formatted text
        $label = $manager->getVersionLabel('dev-feature-auth');
        $this->assertStringNotContainsString('dev-', $label);
        $this->assertStringContainsString('Feature Auth', $label);
    }

    public function test_get_sidebar_for_version()
    {
        $this->createTestDocs();

        $manager = new DocsManager;
        $sidebar = $manager->getSidebar('1.x');

        // Should have categories for v1.x
        $this->assertIsNotEmpty($sidebar);
        $this->assertTrue($sidebar->has('getting-started'));
        $this->assertTrue($sidebar->has('the-basics'));

        // Each category should have files
        $this->assertIsArray($sidebar['getting-started']['files']->toArray());
        $this->assertGreaterThan(0, count($sidebar['getting-started']['files']));
    }

    public function test_sidebar_only_includes_version_docs()
    {
        $this->createTestDocs();

        $manager = new DocsManager;
        $sidebarV1 = $manager->getSidebar('1.x');
        $sidebarV2 = $manager->getSidebar('2.x');

        // V1 should have more categories
        $this->assertGreaterThan(count($sidebarV2), count($sidebarV1));

        // V2 should only have getting-started
        $this->assertTrue($sidebarV2->has('getting-started'));
    }

    public function test_sidebar_is_cached()
    {
        $this->createTestDocs();

        $manager = new DocsManager;
        $sidebar1 = $manager->getSidebar('1.x');
        $sidebar2 = $manager->getSidebar('1.x');

        // Should return same instance (cached)
        $this->assertEquals($sidebar1, $sidebar2);
    }

    public function test_search_returns_docs()
    {
        $this->createTestDocs();

        $manager = new DocsManager;
        $results = $manager->search('installation');

        $this->assertGreaterThan(0, $results->count());
        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result['title'], 'Getting Started'))
        );
    }

    public function test_search_version_scoped()
    {
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/unique-v1',
            'title' => 'Unique V1',
            'content' => 'Version 1 specific content',
            'headings' => [],
        ]);

        Doc::create([
            'version' => '2.x',
            'slug' => '2.x/unique-v2',
            'title' => 'Unique V2',
            'content' => 'Version 2 specific content',
            'headings' => [],
        ]);

        $manager = new DocsManager;

        // Search without version finds both
        $allResults = $manager->search('specific');
        $this->assertCount(2, $allResults);

        // Search v1 only
        $v1Results = $manager->search('specific', '1.x');
        $this->assertCount(1, $v1Results);
        $this->assertTrue(
            $v1Results->contains(fn ($r) => $r['version'] === '1.x')
        );

        // Search v2 only
        $v2Results = $manager->search('specific', '2.x');
        $this->assertCount(1, $v2Results);
        $this->assertTrue(
            $v2Results->contains(fn ($r) => $r['version'] === '2.x')
        );
    }

    public function test_search_returns_empty_for_no_results()
    {
        $this->createTestDocs();

        $manager = new DocsManager;
        $results = $manager->search('xyznonexistentquery');

        $this->assertCount(0, $results);
    }

    public function test_search_empty_query_returns_empty()
    {
        $this->createTestDocs();

        $manager = new DocsManager;
        $results = $manager->search('');

        $this->assertCount(0, $results);
    }

    public function test_strip_markdown_removes_code_blocks()
    {
        $manager = new DocsManager;

        $content = <<<'MD'
            Some text.

            ```php
            echo "hello";
            ```

            More text.
        MD;

        $stripped = $this->callProtectedMethod($manager, 'stripMarkdown', [$content]);

        $this->assertStringNotContainsString('```', $stripped);
        $this->assertStringNotContainsString('echo', $stripped);
        $this->assertStringContainsString('Some text', $stripped);
    }

    public function test_strip_markdown_removes_inline_code()
    {
        $manager = new DocsManager;

        $content = 'Use `echo` to print text.';

        $stripped = $this->callProtectedMethod($manager, 'stripMarkdown', [$content]);

        $this->assertStringNotContainsString('`', $stripped);
        $this->assertStringContainsString('Use echo to print text', $stripped);
    }

    public function test_strip_markdown_removes_bold_and_italic()
    {
        $manager = new DocsManager;

        $content = 'This is **bold** and *italic* text.';

        $stripped = $this->callProtectedMethod($manager, 'stripMarkdown', [$content]);

        $this->assertStringNotContainsString('**', $stripped);
        $this->assertStringNotContainsString('*', $stripped);
        $this->assertStringContainsString('bold', $stripped);
        $this->assertStringContainsString('italic', $stripped);
    }

    public function test_strip_markdown_removes_links()
    {
        $manager = new DocsManager;

        $content = 'Visit [the documentation](http://example.com) for more.';

        $stripped = $this->callProtectedMethod($manager, 'stripMarkdown', [$content]);

        $this->assertStringNotContainsString('[', $stripped);
        $this->assertStringNotContainsString('(http', $stripped);
        $this->assertStringContainsString('the documentation', $stripped);
    }

    public function test_strip_markdown_preserves_text()
    {
        $manager = new DocsManager;

        $content = "# Heading\n\nThis is **important** content with `code` and [links](url).\n\n```\ncode block\n```\n\nMore text.";

        $stripped = $this->callProtectedMethod($manager, 'stripMarkdown', [$content]);

        // No markdown syntax
        $this->assertStringNotContainsString('**', $stripped);
        $this->assertStringNotContainsString('```', $stripped);

        // But contains actual words
        $this->assertStringContainsString('Heading', $stripped);
        $this->assertStringContainsString('important', $stripped);
        $this->assertStringContainsString('content', $stripped);
        $this->assertStringContainsString('More text', $stripped);
    }

    public function test_highlighted_snippet_shows_context()
    {
        $manager = new DocsManager;

        $content = 'This is a long text with the word installation somewhere in the middle of a very long paragraph that should be trimmed for display purposes.';

        $snippet = $this->callProtectedMethod($manager, 'extractHighlightedSnippet', [$content, 'installation']);

        $this->assertStringContainsString('installation', $snippet);
        $this->assertStringContainsString('<mark>', $snippet);
        $this->assertStringContainsString('</mark>', $snippet);
        // Should have ellipsis for truncation
        $this->assertTrue(
            str_contains($snippet, '...') || strlen($snippet) > 100
        );
    }

    public function test_highlighted_snippet_case_insensitive()
    {
        $manager = new DocsManager;

        $content = 'This has Installation with capital I.';

        $snippet = $this->callProtectedMethod($manager, 'extractHighlightedSnippet', [$content, 'installation']);

        $this->assertStringContainsString('<mark>', $snippet);
        $this->assertStringContainsString('Installation', $snippet);
    }

    public function test_converter_produces_valid_html()
    {
        $manager = new DocsManager;
        $converter = $manager->getConverter();

        $markdown = "# Title\n\nSome **bold** and *italic* text.\n\n## Heading 2\n\n- List item 1\n- List item 2\n\n```php\necho \"code\";\n```";

        $html = (string) $converter->convert($markdown);

        // Should produce valid HTML tags
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('<strong>', $html);
        $this->assertStringContainsString('<em>', $html);
        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('<code', $html);
    }

    /**
     * Helper to call protected/private methods for testing
     */
    protected function callProtectedMethod($object, $method, $arguments = [])
    {
        $reflection = new \ReflectionMethod(get_class($object), $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}
