<?php

use App\Models\Doc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Cleanup files before each test to ensure isolation
    if (File::exists(public_path('llms-full.txt'))) {
        File::delete(public_path('llms-full.txt'));
    }
});

afterEach(function () {
    // Cleanup files after each test
    if (File::exists(public_path('llms-full.txt'))) {
        File::delete(public_path('llms-full.txt'));
    }
});

it('generates llms-full.txt for the latest release version', function () {
    // Seed docs for different versions
    Doc::create([
        'version' => '1.x',
        'slug' => '1.x/intro',
        'title' => 'Intro v1',
        'content' => 'v1 content',
        'headings' => [],
    ]);

    Doc::create([
        'version' => '2.x',
        'slug' => '2.x/intro',
        'title' => 'Intro v2',
        'content' => 'v2 content',
        'headings' => [],
    ]);

    Doc::create([
        'version' => 'dev-main',
        'slug' => 'dev-main/intro',
        'title' => 'Intro main',
        'content' => 'main content',
        'headings' => [],
    ]);

    $this->artisan('docs:generate-llms')
        ->expectsOutput('Latest release version identified: 2.x')
        ->assertExitCode(0);

    expect(File::exists(public_path('llms-full.txt')))->toBeTrue();

    $llmsFullContent = File::get(public_path('llms-full.txt'));
    expect($llmsFullContent)->toContain('Veloquent Full Documentation (Version: 2.x)');
    expect($llmsFullContent)->toContain('Generated at: ');
    expect($llmsFullContent)->toContain('v2 content');
    // It should ONLY contain 2.x content
    expect($llmsFullContent)->not->toContain('v1 content');
    expect($llmsFullContent)->not->toContain('main content');
});

it('fails if no release versions are found', function () {
    Doc::create([
        'version' => 'dev-main',
        'slug' => 'dev-main/intro',
        'title' => 'Intro main',
        'content' => 'main content',
        'headings' => [],
    ]);

    $this->artisan('docs:generate-llms')
        ->expectsOutput('No release versions found in the database.')
        ->assertExitCode(1);

    expect(File::exists(public_path('llms-full.txt')))->toBeFalse();
});

it('sorts versions correctly to pick the highest', function () {
    Doc::create(['version' => '1.0', 'slug' => '1.0/a', 'title' => 'a', 'content' => 'v1.0', 'headings' => []]);
    Doc::create(['version' => '1.10', 'slug' => '1.10/a', 'title' => 'a', 'content' => 'v1.10', 'headings' => []]);
    Doc::create(['version' => '1.2', 'slug' => '1.2/a', 'title' => 'a', 'content' => 'v1.2', 'headings' => []]);

    $this->artisan('docs:generate-llms')
        ->expectsOutput('Latest release version identified: 1.10')
        ->assertExitCode(0);

    $llmsFullContent = File::get(public_path('llms-full.txt'));
    expect($llmsFullContent)->toContain('v1.10');
    expect($llmsFullContent)->not->toContain('v1.2');
});
