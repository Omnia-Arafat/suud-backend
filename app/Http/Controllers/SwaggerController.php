<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SwaggerController extends Controller
{
    /**
     * Display the Swagger UI documentation
     * 
     * @return \Illuminate\Http\Response
     */
    public function ui()
    {
        $swaggerJson = $this->generateSwaggerJson();
        
        return view('swagger.ui', compact('swaggerJson'));
    }

    /**
     * Get the OpenAPI JSON documentation
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function json()
    {
        return response()->json($this->generateSwaggerJson());
    }

    /**
     * Get the OpenAPI YAML documentation
     * 
     * @return \Illuminate\Http\Response
     */
    public function yaml()
    {
        $json = $this->generateSwaggerJson();
        $yaml = yaml_emit($json);
        
        return response($yaml, 200, [
            'Content-Type' => 'application/x-yaml',
            'Content-Disposition' => 'inline; filename=api-docs.yaml'
        ]);
    }

    /**
     * Generate the OpenAPI/Swagger JSON specification
     * 
     * @return array
     */
    private function generateSwaggerJson()
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'SU\'UD API Documentation',
                'description' => 'Comprehensive API documentation for the SU\'UD project backend',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'SU\'UD Development Team',
                    'email' => 'dev@suud-project.com'
                ],
                'license' => [
                    'name' => 'MIT'
                ]
            ],
            'servers' => [
                [
                    'url' => 'http://localhost:8000',
                    'description' => 'Local development server'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'Enter your bearer token'
                    ]
                ],
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'John Doe'],
                            'email' => ['type' => 'string', 'example' => 'john@example.com'],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                            'updated_at' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ],
                    'SuccessResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => true],
                            'message' => ['type' => 'string', 'example' => 'Operation successful'],
                            'data' => ['type' => 'object']
                        ]
                    ],
                    'ErrorResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => false],
                            'message' => ['type' => 'string', 'example' => 'Operation failed'],
                            'errors' => ['type' => 'object']
                        ]
                    ]
                ]
            ],
            'paths' => [
                '/api/health' => [
                    'get' => [
                        'tags' => ['System'],
                        'summary' => 'Health check',
                        'description' => 'Check if the API is running',
                        'responses' => [
                            '200' => [
                                'description' => 'API is healthy',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'status' => ['type' => 'string', 'example' => 'OK'],
                                                'message' => ['type' => 'string', 'example' => 'SU\'UD API is running'],
                                                'timestamp' => ['type' => 'string', 'format' => 'date-time']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/public/info' => [
                    'get' => [
                        'tags' => ['System'],
                        'summary' => 'Get API information',
                        'description' => 'Get basic information about the API',
                        'responses' => [
                            '200' => [
                                'description' => 'API information',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'app_name' => ['type' => 'string'],
                                                'version' => ['type' => 'string'],
                                                'description' => ['type' => 'string']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/auth/register' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Register a new user',
                        'description' => 'Create a new user account',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'email', 'password', 'password_confirmation'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'example' => 'John Doe'],
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                                            'password' => ['type' => 'string', 'format' => 'password', 'example' => 'password123'],
                                            'password_confirmation' => ['type' => 'string', 'format' => 'password', 'example' => 'password123']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'User registered successfully',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['$ref' => '#/components/schemas/SuccessResponse'],
                                                [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'data' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'user' => ['$ref' => '#/components/schemas/User'],
                                                                'token' => ['type' => 'string'],
                                                                'token_type' => ['type' => 'string', 'example' => 'Bearer']
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '422' => [
                                'description' => 'Validation error',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/auth/login' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Login user',
                        'description' => 'Authenticate user and get access token',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['email', 'password'],
                                        'properties' => [
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                                            'password' => ['type' => 'string', 'format' => 'password', 'example' => 'password123']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Login successful',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['$ref' => '#/components/schemas/SuccessResponse'],
                                                [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'data' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'user' => ['$ref' => '#/components/schemas/User'],
                                                                'token' => ['type' => 'string'],
                                                                'token_type' => ['type' => 'string', 'example' => 'Bearer']
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '401' => [
                                'description' => 'Invalid credentials',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/auth/me' => [
                    'get' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Get current user',
                        'description' => 'Get information about the authenticated user',
                        'security' => [['bearerAuth' => []]],
                        'responses' => [
                            '200' => [
                                'description' => 'User information',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['$ref' => '#/components/schemas/SuccessResponse'],
                                                [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'data' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'user' => ['$ref' => '#/components/schemas/User']
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            '401' => [
                                'description' => 'Unauthorized',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/users' => [
                    'get' => [
                        'tags' => ['Users'],
                        'summary' => 'List users',
                        'description' => 'Get a paginated list of users',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [
                            [
                                'name' => 'per_page',
                                'in' => 'query',
                                'description' => 'Number of users per page',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'default' => 15]
                            ],
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'description' => 'Page number',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'default' => 1]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Users list',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['$ref' => '#/components/schemas/SuccessResponse'],
                                                [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'data' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'current_page' => ['type' => 'integer'],
                                                                'data' => [
                                                                    'type' => 'array',
                                                                    'items' => ['$ref' => '#/components/schemas/User']
                                                                ],
                                                                'total' => ['type' => 'integer']
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'post' => [
                        'tags' => ['Users'],
                        'summary' => 'Create user',
                        'description' => 'Create a new user',
                        'security' => [['bearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'email', 'password'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'example' => 'Jane Smith'],
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'jane@example.com'],
                                            'password' => ['type' => 'string', 'format' => 'password', 'example' => 'password123']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'User created successfully',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'allOf' => [
                                                ['$ref' => '#/components/schemas/SuccessResponse'],
                                                [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'data' => ['$ref' => '#/components/schemas/User']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
