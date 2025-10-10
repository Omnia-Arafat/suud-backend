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
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'SU\'UD API is running',
        'timestamp' => now()->toISOString()
    ]);
});

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
Route::get('/jobs/{job:slug}', [JobController::class, 'show']);

// Public routes
Route::prefix('public')->group(function () {
    // Add any public endpoints here
    Route::get('/info', function () {
        return response()->json([
            'app_name' => config('app.name'),
            'version' => '1.0.0',
            'description' => 'SU\'UD Project API'
        ]);
    });
});

// Contact form endpoint
Route::post('/contact', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'subject' => 'required|string|max:255',
        'message' => 'required|string|max:5000'
    ]);

    try {
        // Log the contact form submission
        \Log::info('Contact form submission', [
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);

        // Send email notification to admin
        $adminEmail = 'RSL111@hotmail.com';
        $emailData = [
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'ip' => $request->ip(),
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];

        // Send email using Laravel Mail
        \Mail::send([], [], function ($mail) use ($emailData, $adminEmail) {
            $mail->to($adminEmail)
                ->subject('New Contact Form Submission - ' . $emailData['subject'])
                ->html(
                    '<h2>New Contact Form Submission</h2>' .
                    '<p><strong>Name:</strong> ' . htmlspecialchars($emailData['name']) . '</p>' .
                    '<p><strong>Email:</strong> ' . htmlspecialchars($emailData['email']) . '</p>' .
                    '<p><strong>Subject:</strong> ' . htmlspecialchars($emailData['subject']) . '</p>' .
                    '<p><strong>Message:</strong></p>' .
                    '<div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;">' .
                    nl2br(htmlspecialchars($emailData['message'])) .
                    '</div>' .
                    '<hr>' .
                    '<p><small><strong>Submitted:</strong> ' . $emailData['timestamp'] . '</small></p>' .
                    '<p><small><strong>IP Address:</strong> ' . htmlspecialchars($emailData['ip']) . '</small></p>'
                );
        });

        // Send auto-reply to user
        \Mail::send([], [], function ($mail) use ($emailData) {
            $mail->to($emailData['email'])
                ->subject('Thank you for contacting SU\'UD Platform')
                ->html(
                    '<h2>Thank you for contacting us!</h2>' .
                    '<p>Dear ' . htmlspecialchars($emailData['name']) . ',</p>' .
                    '<p>We have received your message and will get back to you within 24-48 hours.</p>' .
                    '<p><strong>Your message:</strong></p>' .
                    '<div style="background-color: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #4f46e5;">' .
                    '<strong>Subject:</strong> ' . htmlspecialchars($emailData['subject']) . '<br><br>' .
                    nl2br(htmlspecialchars($emailData['message'])) .
                    '</div>' .
                    '<p>Best regards,<br>The SU\'UD Team</p>' .
                    '<hr>' .
                    '<p><small>This is an automated message. Please do not reply to this email.</small></p>'
                );
        });

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your message. We will get back to you soon!'
        ]);
    } catch (\Exception $e) {
        \Log::error('Contact form error', [
            'error' => $e->getMessage(),
            'request_data' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'There was an error sending your message. Please try again later.'
        ], 500);
    }
});

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
    Route::get('/jobs', [AdminDashboardController::class, 'jobs']);
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

