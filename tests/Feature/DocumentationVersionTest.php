<?php

namespace Tests\Feature;

use App\Models\Doc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentationVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_doc_home_redirects_to_highest_numeric_version_even_if_dev_exists()
    {
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/getting-started/introduction',
            'title' => 'v1',
            'content' => 'v1 content',
            'headings' => [],
        ]);

        Doc::create([
            'version' => '2.x',
            'slug' => '2.x/getting-started/introduction',
            'title' => 'v2',
            'content' => 'v2 content',
            'headings' => [],
        ]);

        Doc::create([
            'version' => 'dev-main',
            'slug' => 'dev-main/getting-started/introduction',
            'title' => 'dev-main',
            'content' => 'dev-main content',
            'headings' => [],
        ]);

        $response = $this->get('/docs');

        $response->assertRedirect('/docs/2.x/getting-started/introduction');
    }

    public function test_shortcut_route_prioritizes_numeric_versions()
    {
        Doc::create([
            'version' => '1.x',
            'slug' => '1.x/getting-started/introduction',
            'title' => 'v1',
            'content' => 'v1 content',
            'headings' => [],
        ]);

        Doc::create([
            'version' => 'dev-main',
            'slug' => 'dev-main/getting-started/introduction',
            'title' => 'dev-main',
            'content' => 'dev-main content',
            'headings' => [],
        ]);

        // Shortcut to getting-started should go to 1.x not dev-main
        $response = $this->get('/docs/getting-started');

        $response->assertRedirect('/docs/1.x/getting-started/introduction');
    }
}
