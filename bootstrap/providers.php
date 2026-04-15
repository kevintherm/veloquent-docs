<?php

use App\Providers\AppServiceProvider;
use App\Providers\DocumentationServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    DocumentationServiceProvider::class
];
