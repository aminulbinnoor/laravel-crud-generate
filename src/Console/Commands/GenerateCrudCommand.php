<?php

namespace Aminul\CrudGenerate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GenerateCrudCommand extends Command
{
    protected $modelVariable;
    protected $signature = 'make:crud {name : The name of the model}
                                      {--fields= : Fields for the model (e.g., "name:string,email:string")}
                                      {--relations= : Define relationships (e.g., "hasMany:Comment,belongsTo:User")}
                                      {--sample : Generate with sample data}';

    protected $description = 'Generate complete CRUD structure with repository pattern';

    protected $files;
    protected $modelName;
    protected $modelPlural;
    protected $modelSnake;
    protected $modelKebab;
    protected $fields = [];
    protected $relations = [];

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
        $this->modelVariable = Str::camel($this->modelName);

        $this->parseFields();
        $this->parseRelations();

        // Generate BaseModel first
        $this->generateBaseModel();

        // Then generate other files
        $this->generateModel();
        $this->generateMigration();
        $this->generateRepositoryInterface();
        $this->generateRepository();
        $this->generateService();
        $this->generateController();
        $this->generateEditControllerRelations();
        $this->generateApiController();
        $this->generateStoreRequests();
        $this->generateUpdateRequests();
        $this->generateRelationFields();
        // Generate layouts if not exists
        $this->generateLayouts();
        $this->generateViews();
        $this->generateShowFields();
        $this->addRoutes();
        $this->addApiRoutes();

        $this->info("CRUD for {$this->modelName} generated successfully!");
        $this->info("Run: php artisan migrate");
        $this->info("Make sure you have a User model with id field for the audit relations.");

        if (!empty($this->relations)) {
            $this->info("Relationships defined: " . implode(', ', array_keys($this->relations)));
        }
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

    protected function parseRelations()
    {
        $relationsInput = $this->option('relations');
        if (!$relationsInput) {
            return;
        }

        foreach (explode(',', $relationsInput) as $relation) {
            $parts = explode(':', $relation);
            if (count($parts) === 2) {
                $relationType = trim($parts[0]);
                $relatedModel = trim($parts[1]);

                $this->relations[$relationType][] = $relatedModel;
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
            //'{{foreignKeys}}' => $this->generateForeignKeys(),
            '{{casts}}' => $this->generateCasts(),
            '{{relationWith}}' => $this->generateRelationWith(),
            '{{relations}}' => $this->generateRelations(),
        ];

        $path = app_path('Models/' . $this->modelName . '.php');
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateMigration()
    {
        $tableName = Str::plural(Str::snake($this->modelName));
        $migrationName = 'create_' . $tableName . '_table';

        // Use custom migration stub
        $stub = $this->getStub('migration');
        $replacements = [
            '{{tableName}}' => $tableName,
            '{{migrationFields}}' => $this->generateMigrationFields(),
            '{{foreignKeys}}' => $this->generateMigrationForeignKeys(),
        ];

        $timestamp = date('Y_m_d_His');
        $path = database_path("migrations/{$timestamp}_{$migrationName}.php");

        $this->createFile($path, $stub, $replacements);
        $this->info("Created: {$timestamp}_{$migrationName}.php");
    }

    protected function generateRelations()
    {
        if (empty($this->relations)) {
            return '';
        }

        $relationsCode = "\n\n    // Relationships";

        foreach ($this->relations as $relationType => $relatedModels) {
            foreach ($relatedModels as $relatedModel) {
                $methodName = $this->getRelationMethodName($relationType, $relatedModel);
                $relationsCode .= $this->generateRelationMethod($relationType, $relatedModel, $methodName);
            }
        }

        return $relationsCode;
    }

    protected function getRelationMethodName($relationType, $relatedModel)
    {
        $methodNames = [
            'hasMany' => Str::camel(Str::plural($relatedModel)),
            'hasOne' => Str::camel($relatedModel),
            'belongsTo' => Str::camel($relatedModel),
            'belongsToMany' => Str::camel(Str::plural($relatedModel)),
            'morphMany' => Str::camel(Str::plural($relatedModel)),
            'morphOne' => Str::camel($relatedModel),
            'morphTo' => 'parent',
        ];

        return $methodNames[$relationType] ?? Str::camel($relatedModel);
    }

    protected function generateRelationMethod($relationType, $relatedModel, $methodName)
    {
        $methods = [
            'hasMany' => "
    public function {$methodName}()
    {
        return \$this->{$relationType}(\\App\\Models\\{$relatedModel}::class);
    }",

            'hasOne' => "
    public function {$methodName}()
    {
        return \$this->{$relationType}(\\App\\Models\\{$relatedModel}::class);
    }",

            'belongsTo' => "
    public function {$methodName}()
    {
        return \$this->{$relationType}(\\App\\Models\\{$relatedModel}::class);
    }",

            'belongsToMany' => "
    public function {$methodName}()
    {
        return \$this->{$relationType}(\\App\\Models\\{$relatedModel}::class);
    }",

            'morphMany' => "
    public function {$methodName}()
    {
        return \$this->{$relationType}(\\App\\Models\\{$relatedModel}::class);
    }",

            'morphOne' => "
    public function {$methodName}()
    {
        return \$this->{$relationType}(\\App\\Models\\{$relatedModel}::class);
    }",

            'morphTo' => "
    public function {$methodName}()
    {
        return \$this->{$relationType}();
    }",
        ];

        return $methods[$relationType] ?? '';
    }

    // protected function generateForeignKeys()
    // {
    //     if (empty($this->relations['belongsTo'])) {
    //         return '';
    //     }

    //     $foreignKeys = '';
    //     foreach ($this->relations['belongsTo'] as $relatedModel) {
    //         $foreignKey = Str::snake($relatedModel) . '_id';
    //         $foreignKeys .= ",\n            '{$foreignKey}'";
    //     }

    //     return $foreignKeys;
    // }

    protected function generateMigrationForeignKeys()
    {
        if (empty($this->relations['belongsTo'])) {
            return '';
        }

        $foreignKeys = "\n";

        foreach ($this->relations['belongsTo'] as $relatedModel) {
            $foreignKey = Str::snake($relatedModel) . '_id';
            $tableName = Str::plural(Str::snake($relatedModel));
            $foreignKeys .= "            \$table->foreignId('{$foreignKey}')->constrained('{$tableName}')->onDelete('cascade');\n";
        }

        return $foreignKeys;
    }

    protected function generateMigrationFields()
    {
        $fields = '';
        foreach ($this->fields as $field => $type) {
            $fieldDefinition = $this->getFieldDefinition($field, $type);
            $fields .= "            \$table->{$fieldDefinition};\n";
        }
        return $fields;
    }

    protected function getFieldDefinition($field, $type)
    {
        $definitions = [
            'string'    => "string('{$field}')",
            'text'      => "text('{$field}')",
            'integer'   => "integer('{$field}')",
            'decimal'   => "decimal('{$field}', 8, 2)",
            'boolean'   => "boolean('{$field}')",
            'date'      => "date('{$field}')",
            'datetime'  => "datetime('{$field}')",
            'timestamp' => "timestamp('{$field}')",
            'json' => "json('{$field}')",
            'email' => "string('{$field}')",
        ];

        return $definitions[$type] ?? "string('{$field}')";
    }

    protected function generateFillable()
    {
        $fillable = array_merge(array_keys($this->fields), ['created_by', 'updated_by']);

        // Add foreign keys to fillable (only if they don't already exist)
        if (!empty($this->relations['belongsTo'])) {
            foreach ($this->relations['belongsTo'] as $relatedModel) {
                $foreignKey = Str::snake($relatedModel) . '_id';
                if (!in_array($foreignKey, $fillable)) {
                    $fillable[] = $foreignKey;
                }
            }
        }

        $fillableArray = "            '" . implode("',\n            '", $fillable) . "'";

        return "\n        " . $fillableArray;
    }

    protected function generateBaseModel()
    {
        $stub = $this->getStub('base-model');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
        ];

        $path = app_path('Models/BaseModel.php');

        // Only create BaseModel if it doesn't exist
        if (!$this->files->exists($path)) {
            $this->createFile($path, $stub, $replacements);
            $this->info("Created: BaseModel.php");
        } else {
            $this->info("BaseModel already exists, skipping...");
        }
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
            '{{modelVariable}}' => $this->modelVariable,
            '{{repositoryRelations}}' => $this->generateRepositoryRelations(),
        ];

        $repoPath = config('crud-generator.paths.repositories', 'Repositories');
        $path = app_path("{$repoPath}/{$this->modelName}Repository.php");
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateRepositoryRelations()
    {
        if (empty($this->relations)) {
            return '';
        }

        $relationsCode = "\n\n    // Relationship methods\n";

        foreach ($this->relations as $relationType => $relatedModels) {
            foreach ($relatedModels as $relatedModel) {
                $methodName = 'with' . $relatedModel;
                $relationsCode .= "
        public function {$methodName}()
        {
            return \$this->model->with('" . Str::camel($relatedModel) . "');
        }";
            }
        }

        return $relationsCode;
    }

    protected function generateService()
    {
        $stub = $this->getStub('service');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{modelVariable}}' => $this->modelVariable,
            '{{serviceRelations}}' => $this->generateServiceRelations(),
        ];

        $servicePath = config('crud-generator.paths.services', 'Services');
        $path = app_path("{$servicePath}/{$this->modelName}Service.php");
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateServiceRelations()
    {
        if (empty($this->relations)) {
            return '';
        }

        $relationsCode = "\n\n    // Relationship methods\n";

        foreach ($this->relations as $relationType => $relatedModels) {
            foreach ($relatedModels as $relatedModel) {
                $methodName = $this->getRelationMethodName($relationType, $relatedModel);
                $relationsCode .= $this->generateServiceRelationMethod($relationType, $relatedModel, $methodName);
            }
        }

        return $relationsCode;
    }

    protected function generateServiceRelationMethod($relationType, $relatedModel, $methodName)
    {
        $methods = [
            'hasMany' => "
        public function get{$relatedModel}By{$this->modelName}(\$id)
        {
            \${$this->modelVariable} = \$this->repository->find(\$id);
            return \${$this->modelVariable}->{$methodName};
        }",

            'belongsTo' => "
        public function get{$relatedModel}For{$this->modelName}(\$id)
        {
            \${$this->modelVariable} = \$this->repository->find(\$id);
            return \${$this->modelVariable}->{$methodName};
        }",
        ];

        return $methods[$relationType] ?? '';
    }

    protected function generateController()
    {
        $stub = $this->getStub('controller');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{modelPlural}}' => $this->modelPlural,
            '{{modelVariable}}' => $this->modelVariable,
            '{{modelPluralVariable}}' => Str::camel($this->modelPlural),
            '{{viewPath}}' => $this->modelKebab,
            '{{controllerRelations}}' => $this->generateControllerRelations(),
            '{{editControllerRelations}}' => $this->generateEditControllerRelations(),
        ];

        $controllerPath = config('crud-generator.paths.controllers', 'Http/Controllers');
        $path = app_path("{$controllerPath}/{$this->modelName}Controller.php");
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateControllerRelations()
    {
        if (empty($this->relations['belongsTo'])) {
            return "return view('{$this->modelKebab}.create');";
        }

        $relationsCode = "\n        // Load related data for forms\n";
        foreach ($this->relations['belongsTo'] as $relatedModel) {
            $relationVariable = Str::camel(Str::plural($relatedModel));
            $relationModel = $relatedModel;
            $relationsCode .= "        \${$relationVariable} = \\App\\Models\\{$relationModel}::all();\n";
        }

        $compactVars = [];
        foreach ($this->relations['belongsTo'] as $relatedModel) {
            $relationVariable = Str::camel(Str::plural($relatedModel));
            $compactVars[] = "'{$relationVariable}'";
        }

        $relationsCode .= "        return view('{$this->modelKebab}.create', compact(" . implode(', ', $compactVars) . "));";

        return $relationsCode;
    }

    protected function generateRelationFields()
    {
        if (empty($this->relations['belongsTo'])) {
            return '';
        }

        $fields = '';
        foreach ($this->relations['belongsTo'] as $relatedModel) {
            $foreignKey = Str::snake($relatedModel) . '_id';
            $label = Str::title(str_replace('_', ' ', $relatedModel));
            $relationVariable = Str::camel(Str::plural($relatedModel));

            $fields .= "
                        <div class=\"form-group\">
                            <label for=\"{$foreignKey}\">{$label} <span class=\"text-danger\">*</span></label>
                            <select name=\"{$foreignKey}\" id=\"{$foreignKey}\" class=\"form-control select2\" required>
                                <option value=\"\">Select {$label}</option>
                                @foreach(\${$relationVariable} as \$item)
                                    <option value=\"{{ \$item->id }}\" {{ old('{$foreignKey}') == \$item->id ? 'selected' : '' }}>
                                        {{ \$item->name ?? \$item->title ?? \$item->id }}
                                    </option>
                                @endforeach
                            </select>
                            @error('{$foreignKey}')
                                <span class=\"text-danger\">{{ \$message }}</span>
                            @enderror
                        </div>";
        }

        return $fields;
    }

    protected function generateEditControllerRelations()
    {
        if (empty($this->relations['belongsTo'])) {
            return "\n        return view('{$this->modelKebab}.edit', compact('{$this->modelVariable}'));";
        }

        $relationsCode = "\n\n        // Load related data for edit form\n";
        foreach ($this->relations['belongsTo'] as $relatedModel) {
            $relationVariable = Str::camel(Str::plural($relatedModel));
            $relationModel = $relatedModel;
            $relationsCode .= "        \${$relationVariable} = \\App\\Models\\{$relationModel}::all();\n";
        }

        $compactVars = ["'{$this->modelVariable}'"];
        foreach ($this->relations['belongsTo'] as $relatedModel) {
            $relationVariable = Str::camel(Str::plural($relatedModel));
            $compactVars[] = "'{$relationVariable}'";
        }

        $relationsCode .= "        return view('{$this->modelKebab}.edit', compact(" . implode(', ', $compactVars) . "));";

        return $relationsCode;
    }

    //generate api controller
    protected function generateApiController()
    {
        $stub = $this->getStub('api-controller');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{modelPlural}}' => $this->modelPlural,
            '{{modelVariable}}' => Str::camel($this->modelName),
            '{{modelPluralVariable}}' => Str::camel($this->modelPlural),
            '{{viewPath}}' => $this->modelKebab,
        ];

        $controllerPath = config('crud-generator.paths.api-controllers', 'Http/Controllers/API');
        $path = app_path("{$controllerPath}/{$this->modelName}Controller.php");
        $this->createFile($path, $stub, $replacements);
    }

    protected function generateStoreRequests()
    {
        // Generate Store Request
        $stub = $this->getStub('store-request');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{rules}}' => $this->generateValidationRules(),
            '{{relationRules}}' => $this->generateRelationValidationRules(),
        ];

        $requestPath = config('crud-generator.paths.requests', 'Http/Requests');
        $storePath = app_path("{$requestPath}/Store{$this->modelName}Request.php");
        $this->createFile($storePath, $stub, $replacements);
    }
    protected function generateUpdateRequests()
    {
        // Generate Store Request
        $stub = $this->getStub('update-request');
        $replacements = [
            '{{namespace}}' => config('crud-generator.namespace', 'App'),
            '{{modelName}}' => $this->modelName,
            '{{rules}}' => $this->generateValidationRules(),
            '{{relationRules}}' => $this->generateRelationValidationRules(),
        ];

        $requestPath = config('crud-generator.paths.requests', 'Http/Requests');
        $updatePath = app_path("{$requestPath}/Update{$this->modelName}Request.php");
        $this->createFile($updatePath, $stub, $replacements);
    }

    protected function generateLayouts()
    {
        // Only create layout if it doesn't exist
        $layoutPath = resource_path("views/layouts/app.blade.php");

        if (!$this->files->exists($layoutPath)) {
            $stub = $this->getStub('layouts/app');
            $replacements = [
                '{{modelName}}' => $this->modelName,
                '{{modelPlural}}' => $this->modelPlural,
                '{{modelVariable}}' => $this->modelVariable,
                '{{modelPluralVariable}}' => Str::camel($this->modelPlural),
                '{{viewPath}}' => $this->modelKebab,
            ];

            $this->createFile($layoutPath, $stub, $replacements);
            $this->info("Created: layouts/app.blade.php");
        } else {
            $this->info("Layout already exists, skipping...");
        }
    }

    protected function generateViews()
    {
        $views = ['index', 'create', 'edit', 'show'];

        foreach ($views as $view) {
            $stub = $this->getStub("views/{$view}");
            $replacements = [
                '{{modelName}}' => $this->modelName,
                '{{modelPlural}}' => $this->modelPlural,
                '{{modelVariable}}' => $this->modelVariable,
                '{{modelPluralVariable}}' => Str::camel($this->modelPlural),
                '{{viewPath}}' => $this->modelKebab,
                '{{fields}}' => $this->generateViewFields(),
                '{{relationFields}}' => $this->generateRelationFields(),
                '{{tableHeaders}}' => $this->generateTableHeaders(),
                '{{tableRows}}' => $this->generateTableRows(),
                '{{showFields}}' => $this->generateShowFields(),
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

    protected function addApiRoutes()
    {
        // also add to api.php
        $apiRouteContent = "\n// {$this->modelName} CRUD API Routes\n";
        $apiRouteContent .= "Route::apiResource('{$this->modelKebab}', \\App\\Http\\Controllers\\API\\{$this->modelName}Controller::class);\n";

        $apiRoutesPath = config('crud-generator.paths.routes', 'routes/api.php');

        if ($this->files->exists($apiRoutesPath)) {
            $this->files->append($apiRoutesPath, $apiRouteContent);
        }

    }

    // Helper methods for generating stub content
    protected function getStub($type)
    {
        return $this->files->get(__DIR__ . "/../../resources/stubs/{$type}.stub");
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

    protected function generateCasts()
    {
        $casts = [];
        foreach ($this->fields as $field => $type) {
            if (in_array($type, ['json', 'array', 'boolean', 'date', 'datetime', 'decimal'])) {
                $castType = $type === 'decimal' ? 'decimal:2' : $type;
                $casts[] = "'{$field}' => '{$castType}'";
            }
        }

        // Add default casts
        $defaultCasts = [
            "'created_at' => 'datetime'",
            "'updated_at' => 'datetime'",
            "'deleted_at' => 'datetime'",
        ];

        $allCasts = array_merge($casts, $defaultCasts);
        $castsArray = "            " . implode(",\n            ", $allCasts);

        return "\n        " . $castsArray;
    }

    protected function generateRelationWith()
    {
        if (empty($this->relations)) {
            return "\n        'creator',\n        'updater'";
        }

        $with = ["\n        'creator'", "'updater'"];

        foreach ($this->relations as $relationType => $relatedModels) {
            foreach ($relatedModels as $relatedModel) {
                $methodName = $this->getRelationMethodName($relationType, $relatedModel);
                $with[] = "'{$methodName}'";
            }
        }

        return "\n        " . implode(",\n        ", $with);
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

    protected function generateRelationValidationRules()
    {
        if (empty($this->relations['belongsTo'])) {
            return '';
        }

        $rules = [];
        foreach ($this->relations['belongsTo'] as $relatedModel) {
            $foreignKey = Str::snake($relatedModel) . '_id';
            $rules[] = "'{$foreignKey}' => 'required|exists:" . Str::plural(Str::snake($relatedModel)) . ",id'";
        }

        if (empty($rules)) {
            return '';
        }

        return ",\n            " . implode(",\n            ", $rules);
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

    protected function generateShowFields()
    {
        $fields = '';
        foreach ($this->fields as $field => $type) {
            $label = Str::title(str_replace('_', ' ', $field));
            $fields .= "
                                <p><strong>{$label}:</strong> {{ \${$this->modelVariable}->{$field} }}</p>";
        }
        return $fields;
    }
}
