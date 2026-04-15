<?php

namespace Tests\Feature;

use App\Models\Doc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentationTest extends TestCase
{
    use RefreshDatabase;

    protected function createSampleDocs()
    {
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/getting-started/introduction',
            'title' => 'Getting Started',
            'content' => <<<'MD'
                # Getting Started

                Welcome to the documentation.

                ## Installation

                Install the package first.

                ## Usage

                Use the library like this.
            MD,
            'headings' => [
                ['text' => 'Installation', 'level' => 2, 'id' => 'installation'],
                ['text' => 'Usage', 'level' => 2, 'id' => 'usage'],
            ],
        ]);

        Doc::create([
            'version' => '2.x',
            'slug' => '2.x/getting-started/introduction',
            'title' => 'Getting Started v2',
            'content' => <<<'MD'
                # Getting Started (v2)

                Welcome to the v2 documentation.

                ## Quick Start

                Get started quickly.

                ## Features

                Amazing features.
            MD,
            'headings' => [
                ['text' => 'Quick Start', 'level' => 2, 'id' => 'quick-start'],
                ['text' => 'Features', 'level' => 2, 'id' => 'features'],
            ],
        ]);
    }

    public function test_versioned_docs_route()
    {
        $this->createSampleDocs();

        $response = $this->get('/docs/1.x/getting-started/introduction');

        $response->assertStatus(200);
        $response->assertViewIs('docs.viewer');
        $response->assertViewHas('title', 'Getting Started');
        $response->assertViewHas('version', '1.x');
    }

    public function test_version_2_docs_loads_different_content()
    {
        $this->createSampleDocs();

        $response1 = $this->get('/docs/1.x/getting-started/introduction');
        $response2 = $this->get('/docs/2.x/getting-started/introduction');

        $response1->assertSee('Welcome to the documentation');
        $response2->assertSee('Welcome to the v2 documentation');
    }

    public function test_doc_not_found_for_wrong_version()
    {
        $this->createSampleDocs();

        $response = $this->get('/docs/3.x/getting-started/introduction');

        $response->assertStatus(404);
    }

    public function test_doc_home_redirect()
    {
        $this->createSampleDocs();

        $response = $this->get('/docs/');

        $response->assertRedirect();
        // Should redirect to the latest version's getting-started
        $this->assertTrue(
            str_contains($response->headers->get('location'), '/docs/2.x/getting-started/introduction')
            || str_contains($response->headers->get('location'), '/docs/1.x/getting-started/introduction')
        );
    }

    public function test_version_specific_home_redirect()
    {
        $this->createSampleDocs();

        $response = $this->get('/docs/1.x/');

        $response->assertRedirect('/docs/1.x/getting-started/introduction');
    }

    public function test_raw_markdown_response()
    {
        $this->createSampleDocs();

        $response = $this->get('/docs/1.x/getting-started/introduction.md');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $response->assertSee('# Getting Started');
        $response->assertSee('## Installation');
    }

    public function test_raw_query_parameter()
    {
        $this->createSampleDocs();

        $response = $this->get('/docs/1.x/getting-started/introduction?raw');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/markdown; charset=utf-8');
        $response->assertSee('# Getting Started');
    }

    public function test_heading_anchor_in_query_parameter()
    {
        $this->createSampleDocs();

        $response = $this->get('/docs/1.x/getting-started/introduction?heading=installation');

        $response->assertStatus(200);
        $response->assertViewHas('headingAnchor', 'installation');
    }

    public function test_version_switcher_available_versions()
    {
        $this->createSampleDocs();

        $response = $this->get('/docs/1.x/getting-started/introduction');

        $response->assertViewHas('availableVersions');
        $versions = $response->viewData('availableVersions');
        $this->assertContains('1.x', $versions);
        $this->assertContains('2.x', $versions);
    }

    public function test_sidebar_is_version_specific()
    {
        $this->createSampleDocs();

        // Add more docs to test sidebar
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/the-basics/models',
            'title' => 'Models',
            'content' => '# Models',
            'headings' => [],
        ]);

        Doc::create([
            'version' => '2.x',
            'slug' => '2.x/architecture/design',
            'title' => 'Design Patterns',
            'content' => '# Design',
            'headings' => [],
        ]);

        $response = $this->get('/docs/1.x/getting-started/introduction');

        $response->assertViewHas('sidebarCategories');
        $sidebar = $response->viewData('sidebarCategories');

        // v1.x should have getting-started and the-basics
        $this->assertArrayHasKey('getting-started', $sidebar);
        $this->assertArrayHasKey('the-basics', $sidebar);
    }

    public function test_search_finds_docs()
    {
        $this->createSampleDocs();

        $response = $this->get('/docs/search?q=installation');

        $response->assertStatus(200);
        $response->assertViewHas('search_results');
        $response->assertViewHas('search_query', 'installation');

        $results = $response->viewData('search_results');
        $this->assertGreaterThan(0, count($results));
    }

    public function test_search_version_scoping()
    {
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/test',
            'title' => 'Version 1 Only',
            'content' => 'This document exists in v1.x only',
            'headings' => [],
        ]);

        Doc::create([
            'version' => '2.x',
            'slug' => '2.x/test',
            'title' => 'Version 2 Only',
            'content' => 'This document exists in v2.x only',
            'headings' => [],
        ]);

        // Search without version should find both
        $response = $this->get('/docs/search?q=document');
        $results = $response->viewData('search_results');
        $this->assertCount(2, $results);

        // Search with version filter should find one per version
        $responseV1 = $this->get('/docs/search?q=document&version=1.x');
        $resultsV1 = $responseV1->viewData('search_results');
        $this->assertCount(1, $resultsV1);
    }

    public function test_search_strips_markdown_in_snippet()
    {
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/code-example',
            'title' => 'Code Example',
            'content' => <<<'MD'
                # Code Example

                This is a **bold** statement with `code` and a [link](http://example.com).

                ```php
                echo "test";
                ```

                More **content** here.
            MD,
            'headings' => [],
        ]);

        $response = $this->get('/docs/search?q=bold');
        $results = $response->viewData('search_results');

        $snippet = $results[0]['snippet'] ?? '';

        // Snippet should not contain markdown syntax
        $this->assertStringNotContainsString('**', $snippet);
        $this->assertStringNotContainsString('[link]', $snippet);
        $this->assertStringNotContainsString('```', $snippet);

        // But should contain the actual text
        $this->assertStringContainsString('bold', $snippet);
    }

    public function test_heading_highlighted_in_snippet()
    {
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/test',
            'title' => 'Test',
            'content' => 'This test document contains the word installation multiple times. Installation is important.',
            'headings' => [],
        ]);

        $response = $this->get('/docs/search?q=installation');
        $results = $response->viewData('search_results');

        $snippet = $results[0]['snippet'] ?? '';

        // Should contain highlighted mark tag
        $this->assertStringContainsString('<mark>', $snippet);
        $this->assertStringContainsString('installation', $snippet);
    }
}
