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

        // Auto-bind repositories
        $this->bindRepositories();
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

    /**
     * Automatically bind repository interfaces with their implementations.
     *
     * @return void
     */
    protected function bindRepositories()
    {
        // Get the application namespace
        $namespace = $this->app->getNamespace();

        // Define the paths for interfaces and repositories
        $interfacePath = app_path('Repositories/Contracts');
        $repositoryPath = app_path('Repositories');

        // Check if the directories exist
        if (!is_dir($interfacePath) || !is_dir($repositoryPath)) {
            return;
        }

        // Get all interface files
        $interfaceFiles = glob($interfacePath . '/*RepositoryInterface.php');

        foreach ($interfaceFiles as $interfaceFile) {
            // Extract interface name from filename
            $interfaceName = basename($interfaceFile, '.php');

            // Extract model name from interface name (remove 'RepositoryInterface')
            $modelName = str_replace('RepositoryInterface', '', $interfaceName);

            // Build full class names
            $interfaceClass = "{$namespace}Repositories\\Contracts\\{$interfaceName}";
            $repositoryClass = "{$namespace}Repositories\\{$modelName}Repository";

            // Check if both classes exist before binding
            if (class_exists($interfaceClass) && class_exists($repositoryClass)) {
                $this->app->bind($interfaceClass, $repositoryClass);
            }
        }
    }
}
