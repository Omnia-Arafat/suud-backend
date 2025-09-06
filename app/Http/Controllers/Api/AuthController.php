<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OpenApi\Post(
     *     path="/api/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Create a new user account and return authentication token",
     *     @OpenApi\RequestBody(
     *         required=true,
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 required={"name", "email", "password", "password_confirmation", "role"},
     *                 @OpenApi\Property(property="name", type="string", example="John Doe"),
     *                 @OpenApi\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OpenApi\Property(property="password", type="string", format="password", minLength=8, example="password123"),
     *                 @OpenApi\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *                 @OpenApi\Property(property="role", type="string", enum={"employee", "employer"}, example="employee", description="User role - required field"),
     *                 @OpenApi\Property(property="company_name", type="string", example="Tech Solutions Inc.", description="Company name - required only when role is employer"),
     *                 @OpenApi\Property(property="specialization", type="string", example="Software Engineering", description="Employee specialization - optional"),
     *                 @OpenApi\Property(property="university", type="string", example="Cairo University", description="Employee university - optional"),
     *                 @OpenApi\Property(property="phone", type="string", example="+201234567890", description="Phone number - optional"),
     *                 @OpenApi\Property(property="location", type="string", example="Cairo, Egypt", description="Location - optional")
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="success", type="boolean", example=true),
     *                 @OpenApi\Property(property="message", type="string", example="User registered successfully"),
     *                 @OpenApi\Property(
     *                     property="data",
     *                     type="object",
     *                     @OpenApi\Property(
     *                         property="user",
     *                         type="object",
     *                         @OpenApi\Property(property="id", type="integer", example=1),
     *                         @OpenApi\Property(property="name", type="string", example="John Doe"),
     *                         @OpenApi\Property(property="email", type="string", example="john@example.com"),
     *                         @OpenApi\Property(property="created_at", type="string", format="date-time")
     *                     ),
     *                     @OpenApi\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz"),
     *                     @OpenApi\Property(property="token_type", type="string", example="Bearer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="success", type="boolean", example=false),
     *                 @OpenApi\Property(property="message", type="string", example="Validation errors"),
     *                 @OpenApi\Property(property="errors", type="object")
     *             )
     *         )
     *     )
     * )
     * 
     * Register a new user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:employee,employer',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            // Employee specific fields
            'specialization' => 'nullable|string|max:255',
            'university' => 'nullable|string|max:255',
            // Company fields for employers (optional at registration)
            'company_name' => 'required_if:role,employer|string|max:255',
        ], [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'role.required' => 'Please select your role (employee or employer)',
            'role.in' => 'Role must be either employee or employer',
            'company_name.required_if' => 'Company name is required for employers',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'location' => $request->location,
            'specialization' => $request->specialization,
            'university' => $request->university,
        ]);

        // Create company profile for employers
        if ($request->role === 'employer' && $request->company_name) {
            $user->company()->create([
                'company_name' => $request->company_name,
                'location' => $request->location,
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $user->updateLastLogin();

        // Load relationships for response
        $user->load('company');

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * @OpenApi\Post(
     *     path="/api/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     description="Authenticate user and return access token",
     *     @OpenApi\RequestBody(
     *         required=true,
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 required={"email", "password"},
     *                 @OpenApi\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OpenApi\Property(property="password", type="string", format="password", example="password123")
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=200,
     *         description="Login successful",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="success", type="boolean", example=true),
     *                 @OpenApi\Property(property="message", type="string", example="Login successful"),
     *                 @OpenApi\Property(
     *                     property="data",
     *                     type="object",
     *                     @OpenApi\Property(
     *                         property="user",
     *                         type="object",
     *                         @OpenApi\Property(property="id", type="integer", example=1),
     *                         @OpenApi\Property(property="name", type="string", example="John Doe"),
     *                         @OpenApi\Property(property="email", type="string", example="john@example.com")
     *                     ),
     *                     @OpenApi\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz"),
     *                     @OpenApi\Property(property="token_type", type="string", example="Bearer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="success", type="boolean", example=false),
     *                 @OpenApi\Property(property="message", type="string", example="Invalid credentials")
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="success", type="boolean", example=false),
     *                 @OpenApi\Property(property="message", type="string", example="Validation errors"),
     *                 @OpenApi\Property(property="errors", type="object")
     *             )
     *         )
     *     )
     * )
     * 
     * Login user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;
        $user->updateLastLogin();

        // Load relationships based on role
        if ($user->isEmployer()) {
            $user->load('company');
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * @OpenApi\Post(
     *     path="/api/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     description="Revoke the current access token",
     *     security={"sanctum": {}},
     *     @OpenApi\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="success", type="boolean", example=true),
     *                 @OpenApi\Property(property="message", type="string", example="Logout successful")
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="message", type="string", example="Unauthenticated.")
     *             )
     *         )
     *     )
     * )
     * 
     * Logout user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * @OpenApi\Post(
     *     path="/api/auth/refresh",
     *     tags={"Authentication"},
     *     summary="Refresh token",
     *     description="Refresh the current access token",
     *     security={"sanctum": {}},
     *     @OpenApi\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="success", type="boolean", example=true),
     *                 @OpenApi\Property(property="message", type="string", example="Token refreshed successfully"),
     *                 @OpenApi\Property(
     *                     property="data",
     *                     type="object",
     *                     @OpenApi\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz"),
     *                     @OpenApi\Property(property="token_type", type="string", example="Bearer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="message", type="string", example="Unauthenticated.")
     *             )
     *         )
     *     )
     * )
     * 
     * Refresh token
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * @OpenApi\Get(
     *     path="/api/auth/me",
     *     tags={"Authentication"},
     *     summary="Get authenticated user",
     *     description="Get the current authenticated user information",
     *     security={"sanctum": {}},
     *     @OpenApi\Response(
     *         response=200,
     *         description="User information retrieved successfully",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="success", type="boolean", example=true),
     *                 @OpenApi\Property(
     *                     property="data",
     *                     type="object",
     *                     @OpenApi\Property(
     *                         property="user",
     *                         type="object",
     *                         @OpenApi\Property(property="id", type="integer", example=1),
     *                         @OpenApi\Property(property="name", type="string", example="John Doe"),
     *                         @OpenApi\Property(property="email", type="string", example="john@example.com"),
     *                         @OpenApi\Property(property="created_at", type="string", format="date-time"),
     *                         @OpenApi\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="message", type="string", example="Unauthenticated.")
     *             )
     *         )
     *     )
     * )
     * 
     * Get authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()
            ]
        ]);
    }
}
