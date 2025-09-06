<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'SU\'UD API Documentation',
            ],
            'routes' => [
                'api' => 'api/documentation',
                'docs' => 'docs',
            ],
            'paths' => [
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
                'annotations' => [
                    base_path('app'),
                ],
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
            'views' => base_path('resources/views/vendor/l5-swagger'),
            'base' => env('L5_SWAGGER_BASE_PATH', null),
            'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
            'excludes' => [],
        ],
        'scanOptions' => [
            'analyser' => null,
            'analysis' => null,
            'processors' => [
                // \OpenApi\Processors\DocBlockDescriptions::class,
                // \OpenApi\Processors\MergeIntoOpenApi::class,
                // \OpenApi\Processors\MergeIntoComponents::class,
                // \OpenApi\Processors\ExpandClasses::class,
                // \OpenApi\Processors\ExpandInterfaces::class,
                // \OpenApi\Processors\ExpandTraits::class,
                // \OpenApi\Processors\ExpandEnums::class,
                // \OpenApi\Processors\AugmentSchemas::class,
                // \OpenApi\Processors\AugmentProperties::class,
                // \OpenApi\Processors\BuildPaths::class,
                // \OpenApi\Processors\AugmentParameters::class,
                // \OpenApi\Processors\AugmentRefs::class,
                // \OpenApi\Processors\MergeJsonContent::class,
                // \OpenApi\Processors\MergeXmlContent::class,
                // \OpenApi\Processors\OperationId::class,
                // \OpenApi\Processors\CleanUnmerged::class,
            ],
            'pattern' => null,
            'exclude' => [],
            'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', '3.0.0'),
        ],
        'securityDefinitions' => [
            'securitySchemes' => [
                'sanctum' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],
            'security' => [
                [
                    'sanctum' => []
                ],
            ],
        ],
            'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true),
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),
        'validator_url' => null,
        'ui' => [
            'display' => [
                'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter' => env('L5_SWAGGER_UI_FILTERS', true),
            ],
            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', false),
            ],
        ],
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost:8000'),
        ],
    ],
];
