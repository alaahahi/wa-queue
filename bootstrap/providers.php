<?php

use App\Monitor\Providers\MonitorServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
    TenancyServiceProvider::class,
    MonitorServiceProvider::class,
];
