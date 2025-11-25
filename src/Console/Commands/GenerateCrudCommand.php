<?php

namespace Aminul\CrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
    protected $signature = 'make:crud {name : The name of the model}
                                      {--fields= : Fields for the model (e.g., "name:string,email:string")}
                                      {--sample : Generate with sample data}';

    protected $description = 'Generate complete CRUD structure with repository pattern';

    protected $files;
    protected $modelName;
    protected $modelPlural;
    protected $modelSnake;
    protected $modelKebab;
    protected $fields = [];

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $this->modelName = Str::studly($this->argument('name'));
        $this->modelPlural = Str::plural($this->modelName);
        $this->modelSnake = Str::snake($this->modelName);
        $this->modelKebab = Str::kebab($this->modelName);

        $this->parseFields();

        $this->generateModel();
        $this->generateMigration();
        $this->generateRepositoryInterface();
        $this->generateRepository();
        $this->generateService();
        $this->generateController();
        $this->generateRequests();
        $this->generateViews();
        $this->addRoutes();

        $this->info("CRUD for {$this->modelName} generated successfully!");
        $this->info("Run: php artisan migrate");
    }

    protected function parseFields()
    {
        $fieldsInput = $this->option('fields') ?: 'name:string,email:string,description:text';

        foreach (explode(',', $fieldsInput) as $field) {
            $parts = explode(':', $field);
            if (count($parts) === 2) {
                $this->fields[trim($parts[0])] = trim($parts[1]);
            }
        }
    }

    protected function generateModel()
    {
        $stub = $this->getStub('model');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{fillable}}' => $this->generateFillable(),
            '{{casts}}' => $this->generateCasts(),
        ];

        $path = app_path('Models/' . $this->modelName . '.php');
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateMigration()
    {
        $tableName = Str::plural(Str::snake($this->modelName));
        $migrationName = 'create_' . $tableName . '_table';

        $this->call('make:migration', [
            'name' => $migrationName,
        ]);

        // We would need to modify the migration file, but for simplicity,
        // we'll just create a basic one
    }

    protected function generateRepositoryInterface()
    {
        $stub = $this->getStub('repository-interface');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
        ];

        $interfacePath = config('crud-generator.paths.interfaces', 'Repositories/Contracts');
        $path = app_path("{$interfacePath}/{$this->modelName}RepositoryInterface.php");
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateRepository()
    {
        $stub = $this->getStub('repository');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{modelVariable}}' => Str::camel($this->modelName),
        ];

        $repoPath = config('crud-generator.paths.repositories', 'Repositories');
        $path = app_path("{$repoPath}/{$this->modelName}Repository.php");
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateService()
    {
        $stub = $this->getStub('service');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{modelVariable}}' => Str::camel($this->modelName),
        ];

        $servicePath = config('crud-generator.paths.services', 'Services');
        $path = app_path("{$servicePath}/{$this->modelName}Service.php");
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateController()
    {
        $stub = $this->getStub('controller');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{modelPlural}}' => $this->modelPlural,
            '{{modelVariable}}' => Str::camel($this->modelName),
            '{{modelPluralVariable}}' => Str::camel($this->modelPlural),
            '{{viewPath}}' => $this->modelKebab,
        ];

        $controllerPath = config('crud-generator.paths.controllers', 'Http/Controllers');
        $path = app_path("{$controllerPath}/{$this->modelName}Controller.php");
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateRequests()
    {
        // Generate Store Request
        $stub = $this->getStub('request');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{rules}}' => $this->generateValidationRules(),
        ];

        $requestPath = config('crud-generator.paths.requests', 'Http/Requests');

        $storePath = app_path("{$requestPath}/Store{$this->modelName}Request.php");
        $this->createFile($storePath, $stub, $replacements);

        $updatePath = app_path("{$requestPath}/Update{$this->modelName}Request.php");
        $this->createFile($updatePath, $stub, $replacements);
    }

    protected function generateViews()
    {
        $views = ['index', 'create', 'edit', 'show'];

        foreach ($views as $view) {
            $stub = $this->getStub("views/{$view}");
            $replacements = [
                '{{modelName}}' => $this->modelName,
                '{{modelPlural}}' => $this->modelPlural,
                '{{modelVariable}}' => Str::camel($this->modelName),
                '{{modelPluralVariable}}' => Str::camel($this->modelPlural),
                '{{viewPath}}' => $this->modelKebab,
                '{{fields}}' => $this->generateViewFields(),
                '{{tableHeaders}}' => $this->generateTableHeaders(),
                '{{tableRows}}' => $this->generateTableRows(),
            ];

            $path = resource_path("views/{$this->modelKebab}/{$view}.blade.php");
            $this->createFile($path, $stub, $replacements);
        }
    }

    protected function addRoutes()
    {
        $routeContent = "\n// {$this->modelName} CRUD Routes\n";
        $routeContent .= "Route::resource('{$this->modelKebab}', \\App\\Http\\Controllers\\{$this->modelName}Controller::class);\n";

        $routesPath = base_path('routes/web.php');

        if ($this->files->exists($routesPath)) {
            $this->files->append($routesPath, $routeContent);
        }
    }

    // Helper methods for generating stub content
    protected function getStub($type)
    {
        return $this->files->get(__DIR__ . "/../../../stubs/{$type}.stub");
    }

    protected function createFile($path, $stub, $replacements)
    {
        $directory = dirname($path);

        if (!$this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        $this->files->put($path, $content);
        $this->info("Created: {$path}");
    }

    protected function generateFillable()
    {
        $fillable = array_keys($this->fields);
        return "['" . implode("', '", $fillable) . "']";
    }

    protected function generateCasts()
    {
        $casts = [];
        foreach ($this->fields as $field => $type) {
            if (in_array($type, ['json', 'array', 'boolean', 'date', 'datetime'])) {
                $casts[] = "'{$field}' => '{$type}'";
            }
        }

        return $casts ? "[\n            " . implode(",\n            ", $casts) . "\n        ]" : '[]';
    }

    protected function generateValidationRules()
    {
        $rules = [];
        foreach ($this->fields as $field => $type) {
            $rule = 'required';

            if ($type === 'string' || $type === 'text') {
                $rule .= '|string';
                if ($type === 'string') {
                    $rule .= '|max:255';
                }
            } elseif ($type === 'email') {
                $rule .= '|email';
            } elseif ($type === 'integer') {
                $rule .= '|integer';
            }

            $rules[] = "'{$field}' => '{$rule}'";
        }

        return implode(",\n            ", $rules);
    }

    protected function generateViewFields()
    {
        $fields = '';
        foreach ($this->fields as $field => $type) {
            $label = Str::title(str_replace('_', ' ', $field));
            $fields .= "
            <div class=\"form-group\">
                <label for=\"{$field}\">{$label}:</label>
                <input type=\"text\" name=\"{$field}\" id=\"{$field}\" class=\"form-control\" value=\"{{ old('{$field}', \${$this->modelVariable}->{$field} ?? '') }}\" required>
            </div>";
        }
        return $fields;
    }

    protected function generateTableHeaders()
    {
        $headers = '';
        foreach ($this->fields as $field => $type) {
            $label = Str::title(str_replace('_', ' ', $field));
            $headers .= "<th>{$label}</th>\n                        ";
        }
        return $headers;
    }

    protected function generateTableRows()
    {
        $rows = '';
        foreach ($this->fields as $field => $type) {
            $rows .= "<td>{{ \${$this->modelVariable}->{$field} }}</td>\n                            ";
        }
        return $rows;
    }
}
