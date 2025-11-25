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

        // Auto-bind repositories
        $this->autoBindRepositories();
    }

    /**
     * Automatically bind repository interfaces for generated CRUDs
     */
    protected function autoBindRepositories(): void
    {
        $appNamespace = trim($this->app->getNamespace(), '\\');
        $contractsPath = app_path('Repositories/Contracts');

        if (!is_dir($contractsPath)) {
            return;
        }

        $interfaceFiles = glob($contractsPath . '/*RepositoryInterface.php');

        foreach ($interfaceFiles as $interfaceFile) {
            $interfaceName = basename($interfaceFile, '.php');
            $modelName = str_replace('RepositoryInterface', '', $interfaceName);

            $interfaceClass = "{$appNamespace}\\Repositories\\Contracts\\{$interfaceName}";
            $repositoryClass = "{$appNamespace}\\Repositories\\{$modelName}Repository";

            if (interface_exists($interfaceClass) && class_exists($repositoryClass)) {
                $this->app->bind($interfaceClass, $repositoryClass);
            }
        }
    }

}
