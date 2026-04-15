<?php

use App\Models\Doc;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('homepage has basic seo tags', function () {
    $this->get(route('home'))
        ->assertStatus(200)
        ->assertSee('<meta name="description"', false)
        ->assertSee('<meta property="og:title"', false)
        ->assertSee('<link rel="canonical"', false);
});

test('documentation pages have dynamic seo tags and canonicals', function () {
    // Create releases 1.x and 2.x
    Doc::create([
        'version' => '1.x',
        'slug' => '1.x/test-doc',
        'title' => 'Test Document',
        'content' => 'This is a test document about Veloquent features that should be indexed and have meta description.',
        'headings' => [],
    ]);

    Doc::create([
        'version' => '2.x',
        'slug' => '2.x/test-doc',
        'title' => 'Test Document v2',
        'content' => 'This is a test document v2 about Veloquent features.',
        'headings' => [],
    ]);

    // Check version 1.x doc
    // Canonical should point to 2.x doc (latest stable)
    $response = $this->get('/docs/1.x/test-doc');
    $response->assertStatus(200)
        ->assertSee('Test Document - Veloquent')
        ->assertSee('<meta name="description" content="This is a test document about Veloquent features that should be indexed and have meta description."', false)
        ->assertSee('<link rel="canonical" href="http://localhost/docs/2.x/test-doc">', false)
        ->assertSee('<meta name="robots" content="noindex, follow"', false); // Because it's an old version

    // Check version 2.x doc
    $response2 = $this->get('/docs/2.x/test-doc');
    $response2->assertStatus(200)
        ->assertSee('Test Document v2 - Veloquent')
        ->assertSee('<link rel="canonical" href="http://localhost/docs/2.x/test-doc">', false)
        ->assertDontSee('<meta name="robots" content="noindex, follow"', false); // Latest version should be indexable
});

test('sitemap.xml returns valid xml and contains docs', function () {
    Doc::create([
        'version' => '2.x',
        'slug' => '2.x/test-doc',
        'title' => 'Test',
        'content' => 'Test',
        'headings' => [],
    ]);

    $response = $this->get('sitemap.xml');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/xml; charset=utf-8')
        ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
        ->assertSee('http://localhost')
        ->assertSee('http://localhost/docs/2.x/test-doc');
});
