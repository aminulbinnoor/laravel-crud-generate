<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Namespace
    |--------------------------------------------------------------------------
    |
    | This option defines the default namespace for generated classes.
    |
    */
    'namespace' => 'App',

    /*
    |--------------------------------------------------------------------------
    | Default Paths
    |--------------------------------------------------------------------------
    |
    | These options define the default paths for generated classes.
    |
    */
    'paths' => [
        'models' => 'Models',
        'repositories' => 'Repositories',
        'interfaces' => 'Repositories/Contracts',
        'services' => 'Services',
        'controllers' => 'Http/Controllers',
        'api-controllers' => 'Http/Controllers/API',
        'requests' => 'Http/Requests',
        'routes' => 'routes/api.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sample Data
    |--------------------------------------------------------------------------
    |
    | Default sample data for the generated CRUD.
    |
    */
    'sample_data' => [
        'name' => 'Sample Name',
        'email' => 'sample@example.com',
        'description' => 'This is a sample description',
        'status' => 'active',
    ],
];
