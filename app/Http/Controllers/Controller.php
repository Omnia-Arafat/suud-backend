<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="SU'UD Job Portal API",
 *     version="1.0.0",
 *     description="Complete API documentation for the SU'UD Job Portal platform. This API provides comprehensive job management, user authentication, application tracking, and administrative features for a modern job portal platform.",
 *     @OA\Contact(
 *         email="admin@suud.sa",
 *         name="SU'UD Development Team"
 *     ),
 *     @OA\License(
 *         name="Private License",
 *         url=""
 *     )
 * )
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 * @OA\Server(
 *     url="https://api.suud.sa",
 *     description="Production Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     description="Laravel Sanctum Bearer Token Authentication",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Tag(
 *     name="System",
 *     description="System health and information endpoints"
 * )
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and token management"
 * )
 * @OA\Tag(
 *     name="Jobs",
 *     description="Public job listings and search functionality"
 * )
 * @OA\Tag(
 *     name="Applications",
 *     description="Job application management for employees and employers"
 * )
 * @OA\Tag(
 *     name="Employer",
 *     description="Employer-specific job management endpoints"
 * )
 * @OA\Tag(
 *     name="Admin",
 *     description="Administrative endpoints for platform management"
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="User management and profile operations"
 * )
 */
abstract class Controller
{
    //
}
