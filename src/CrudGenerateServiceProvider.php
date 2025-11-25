<?php

namespace Aminul\CrudGenerate;

use Illuminate\Support\ServiceProvider;
use Aminul\CrudGenerate\Console\Commands\GenerateCrudCommand;

class CrudGenerateServiceProvider extends ServiceProvider
{
    public function register()
    {
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
