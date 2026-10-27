<?php

use App\Providers\ApplicationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    ApplicationServiceProvider::class,
    FortifyServiceProvider::class,
];
