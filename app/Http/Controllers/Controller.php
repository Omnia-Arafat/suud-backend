<?php

namespace App\Http\Controllers;

/**
 * @OpenApi\Info(
 *     title="SU'UD API Documentation",
 *     version="1.0.0",
 *     description="API documentation for the SU'UD project - A comprehensive solution for user management and authentication",
 *     @OpenApi\Contact(
 *         email="support@suud.com",
 *         name="SU'UD Support Team"
 *     )
 * )
 * 
 * @OpenApi\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 * 
 * @OpenApi\Server(
 *     url="https://api.suud.com",
 *     description="Production Server"
 * )
 * 
 * @OpenApi\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum Bearer Token"
 * )
 * 
 * @OpenApi\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 * 
 * @OpenApi\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 * 
 * @OpenApi\Tag(
 *     name="System",
 *     description="System information and health check endpoints"
 * )
 * 
 * @OpenApi\Tag(
 *     name="Jobs",
 *     description="Job listings management endpoints"
 * )
 * 
 * @OpenApi\Tag(
 *     name="Applications",
 *     description="Job applications management endpoints"
 * )
 * 
 * @OpenApi\Tag(
 *     name="User Management",
 *     description="User management and profile endpoints"
 * )
 */
abstract class Controller
{
    //
}
