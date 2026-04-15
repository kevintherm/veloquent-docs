<?php

return [
    'path' => 'docs',

    // Source repository URL for syncing documentation
    'repository' => env('DOCS_REPOSITORY_URL', ''),

    // Prefix for development branch versions (non-numeric branches)
    'dev_prefix' => 'dev-',
];
