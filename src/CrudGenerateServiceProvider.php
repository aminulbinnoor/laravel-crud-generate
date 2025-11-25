<?php

namespace Aminul\CrudGenerate;

use Illuminate\Support\ServiceProvider;
use Aminul\CrudGenerate\Console\Commands\GenerateCrudCommand;

class CrudGenerateServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->loadViewsFrom(__DIR__.'/resources/stubs', 'CrudGenerate');
        $this->commands([
            GenerateCrudCommand::class,
        ]);
    }

    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/crud-generator.php' => config_path('crud-generator.php'),
        ], 'crud-generator-config');

        // Publish stubs
        $this->publishes([
            __DIR__.'/resources/stubs' => resource_path('vendor/aminul/stubs'),
        ]);
    }

}
