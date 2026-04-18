<?php

namespace Modules\Division\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class DivisionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Division';
    protected string $nameLower = 'division';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }
}
