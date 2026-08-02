<?php

use App\Infrastructure\Providers\DomainServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    DomainServiceProvider::class,
];
