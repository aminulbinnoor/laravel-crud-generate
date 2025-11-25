<?php

namespace Aminul\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Aminul\CrudGenerator\Console\Commands\GenerateCrudCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register commands
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
            __DIR__ . '/../stubs/' => base_path('stubs/crud-generator/'),
        ], 'crud-generator-stubs');
    }
}
