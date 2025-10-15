<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\EmployeeDashboardController;
use App\Http\Controllers\Dashboard\EmployerDashboardController;
use App\Http\Controllers\SystemController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your SU'UD application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Health check
Route::get('/health', [SystemController::class, 'health']);

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User management
    Route::apiResource('users', UserController::class);

    // Job management (for employers)
    Route::get('/employer/jobs', [JobController::class, 'employerJobs']);
    Route::post('/jobs', [JobController::class, 'store']);
    Route::put('/jobs/{job}', [JobController::class, 'update']);
    Route::delete('/jobs/{job}', [JobController::class, 'destroy']);

    // Applications management
    Route::apiResource('applications', ApplicationController::class);
    Route::get('/applications/stats', [ApplicationController::class, 'stats']);

    // Candidates browsing (for employers)
    Route::get('/candidates', function (Request $request) {
        // Only employers can browse candidates
        if (!$request->user()->isEmployer()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only employers can browse candidates.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Candidates endpoint - to be implemented',
            'data' => []
        ]);
    });

    // Get authenticated user with relationships
    Route::get('/user', function (Request $request) {
        $user = $request->user();

        // Load relationships based on role
        if ($user->isEmployer()) {
            $user->load(['company']);
        }

        return response()->json([
            'success' => true,
            'data' => ['user' => $user]
        ]);
    });
});

// Public Job Listings (No authentication required)
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/filters', [JobController::class, 'filters']);
Route::get('/jobs/stats', [JobController::class, 'stats']);
Route::get('/jobs/recent', [JobController::class, 'recent']);
Route::get('/jobs/{job:slug}', [JobController::class, 'show']);

// Public routes
Route::prefix('public')->group(function () {
    // Add any public endpoints here
    Route::get('/info', [SystemController::class, 'info']);
});

// Contact form endpoint
Route::post('/contact', [SystemController::class, 'contact']);

// Company-related API endpoints
Route::get('/companies', function () {
    // This would eventually fetch companies from database
    return response()->json([
        'success' => true,
        'message' => 'Companies endpoint - to be implemented',
        'data' => []
    ]);
});

Route::get('/companies/{id}', function ($id) {
    return response()->json([
        'success' => true,
        'message' => 'Company details endpoint - to be implemented',
        'data' => ['id' => $id]
    ]);
});

// ==============================================================================
// ROLE-BASED DASHBOARD ROUTES
// ==============================================================================

// Admin Dashboard Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);
    Route::get('/users', [AdminDashboardController::class, 'users']);
    Route::patch('/users/{user}/status', [AdminDashboardController::class, 'updateUserStatus']);
    
    // Job management
    Route::get('/jobs', [AdminDashboardController::class, 'jobs']);
    Route::get('/jobs/{job}/details', [AdminDashboardController::class, 'jobDetails']);
    Route::patch('/jobs/{job}/approve', [AdminDashboardController::class, 'approveJob']);
    Route::patch('/jobs/{job}/decline', [AdminDashboardController::class, 'declineJob']);
    
    Route::get('/applications', [AdminDashboardController::class, 'applications']);
    Route::get('/contacts', [AdminDashboardController::class, 'contacts']);
    Route::get('/analytics', [AdminDashboardController::class, 'analytics']);
});

// Employee Dashboard Routes
Route::middleware(['auth:sanctum', 'employee'])->prefix('employee')->group(function () {
    Route::get('/dashboard', [EmployeeDashboardController::class, 'dashboard']);
    Route::get('/applications', [EmployeeDashboardController::class, 'myApplications']);
    Route::get('/jobs', [EmployeeDashboardController::class, 'availableJobs']);
    Route::get('/profile', [EmployeeDashboardController::class, 'getProfile']);
    Route::patch('/profile', [EmployeeDashboardController::class, 'updateProfile']);
    Route::patch('/password', [EmployeeDashboardController::class, 'changePassword']);
    Route::get('/stats', [EmployeeDashboardController::class, 'applicationStats']);
    Route::get('/saved-jobs', [EmployeeDashboardController::class, 'savedJobs']);
    Route::post('/saved-jobs', [EmployeeDashboardController::class, 'saveJob']);
    Route::delete('/saved-jobs/{jobId}', [EmployeeDashboardController::class, 'removeSavedJob']);
});

// Employer Dashboard Routes
Route::middleware(['auth:sanctum', 'employer'])->prefix('employer')->group(function () {
    Route::get('/dashboard', [EmployerDashboardController::class, 'dashboard']);
    Route::get('/jobs', [EmployerDashboardController::class, 'myJobs']);
    Route::get('/applications', [EmployerDashboardController::class, 'jobApplications']);
    Route::patch('/applications/{application}/status', [EmployerDashboardController::class, 'updateApplicationStatus']);
    Route::get('/company', [EmployerDashboardController::class, 'getCompany']);
    Route::patch('/company', [EmployerDashboardController::class, 'updateCompany']);
    Route::patch('/password', [EmployerDashboardController::class, 'changePassword']);
    Route::get('/analytics', [EmployerDashboardController::class, 'analytics']);
});

