<?php

return [

    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'SU\'UD API Documentation',
                'description' => 'API documentation for the SU\'UD project backend',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'SU\'UD Development Team',
                    'email' => 'dev@suud-project.com',
                ],
                'license' => [
                    'name' => 'MIT',
                ],
                'servers' => [
                    [
                        'url' => 'http://localhost:8000',
                        'description' => 'Local development server',
                    ],
                ],
            ],

            'routes' => [
                'api' => 'documentation',
            ],

            'paths' => [
                'use_absolute_path' => env('SWAGGER_USE_ABSOLUTE_PATH', true),
                'docs_json' => 'docs',
                'docs_yaml' => 'docs.yaml',
                'format_to_use_for_docs' => env('SWAGGER_FORMAT', 'json'),
                'swagger_ui_assets_path' => env('SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
                'views' => base_path('resources/views/swagger'),
                'base' => env('SWAGGER_BASE_PATH', null),
                'swagger_ui_jsoneditor' => false,
                'file_name' => env('SWAGGER_FILE_NAME', 'api-docs'),
                'operationId_hash' => env('SWAGGER_OPERATION_ID_HASH', false),
            ],

            'scanOptions' => [
                'analyser' => null,
                'analysis' => null,
                'processors' => [],
                'pattern' => null,
                'exclude' => [],
            ],

            'securityDefinitions' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'Enter token in format (Bearer &lt;token&gt;)',
                        'name' => 'Authorization',
                        'in' => 'header',
                    ],
                ],
                'security' => [
                    [
                        'bearerAuth' => [],
                    ],
                ],
            ],

            'generate_always' => env('SWAGGER_GENERATE_ALWAYS', false),
            'generate_yaml_copy' => env('SWAGGER_GENERATE_YAML_COPY', false),
            'proxy' => false,
            'additional_config_url' => null,
            'operations_sort' => env('SWAGGER_OPERATIONS_SORT', null),
            'validator_url' => null,
            'ui_config' => [
                'deepLinking' => true,
                'displayOperationId' => false,
                'defaultModelsExpandDepth' => 1,
                'defaultModelExpandDepth' => 1,
                'defaultModelRendering' => 'example',
                'displayRequestDuration' => false,
                'docExpansion' => 'none',
                'filter' => false,
                'maxDisplayedTags' => null,
                'operationsSorter' => null,
                'showExtensions' => false,
                'tagsSorter' => null,
                'onComplete' => null,
                'syntaxHighlight' => [
                    'activated' => true,
                    'theme' => 'agate',
                ],
            ],
            'constants' => [
                'SWAGGER_LUME_CONST_HOST' => env('SWAGGER_LUME_CONST_HOST', 'http://localhost:8000'),
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
            'group_options' => [],
        ],

        'paths' => [
            'docs' => storage_path('api-docs'),
            'views' => base_path('resources/views/vendor/swagger'),
            'base' => env('SWAGGER_BASE_PATH', null),
            'swagger_ui_assets_path' => env('SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
            'excludes' => [],
        ],

        'scanOptions' => [
            'default_processors_configuration' => [],
            'open_api_spec_version' => env('SWAGGER_VERSION', '3.0.0'),
        ],

        'securityDefinitions' => [
            'api_key_security_example' => [
                'type' => 'apiKey',
                'description' => 'A short description for security scheme',
                'name' => 'api_key',
                'in' => 'header',
            ],
        ],

        'generate_always' => env('SWAGGER_GENERATE_ALWAYS', false),
        'generate_yaml_copy' => env('SWAGGER_GENERATE_YAML_COPY', false),
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => env('SWAGGER_OPERATIONS_SORT', null),
        'validator_url' => null,
        'ui_config' => [],
        'constants' => [],
    ],

];
