<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\JobListing;
use App\Models\Application;
use App\Models\ContactSubmission;
use App\Models\Company;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     tags={"Admin"},
     *     summary="Get Admin Dashboard Overview",
     *     description="Get comprehensive dashboard statistics including users, jobs, applications, and companies data",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="data",
     *                     type="object",
     *                     @OA\Property(
     *                         property="stats",
     *                         type="object",
     *                         @OA\Property(
     *                             property="users",
     *                             type="object",
     *                             @OA\Property(property="total", type="integer", example=1250),
     *                             @OA\Property(property="employees", type="integer", example=980),
     *                             @OA\Property(property="employers", type="integer", example=265),
     *                             @OA\Property(property="admins", type="integer", example=5),
     *                             @OA\Property(property="active", type="integer", example=1198),
     *                             @OA\Property(property="inactive", type="integer", example=52)
     *                         ),
     *                         @OA\Property(
     *                             property="jobs",
     *                             type="object",
     *                             @OA\Property(property="total", type="integer", example=450),
     *                             @OA\Property(property="active", type="integer", example=280),
     *                             @OA\Property(property="pending", type="integer", example=45),
     *                             @OA\Property(property="declined", type="integer", example=80),
     *                             @OA\Property(property="closed", type="integer", example=45)
     *                         ),
     *                         @OA\Property(
     *                             property="applications",
     *                             type="object",
     *                             @OA\Property(property="total", type="integer", example=3240),
     *                             @OA\Property(property="pending", type="integer", example=456),
     *                             @OA\Property(property="reviewed", type="integer", example=1234),
     *                             @OA\Property(property="accepted", type="integer", example=890),
     *                             @OA\Property(property="rejected", type="integer", example=660)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - Admin role required",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=false),
     *                 @OA\Property(property="message", type="string", example="Access denied")
     *             )
     *         )
     *     )
     * )
     *
     * Get admin dashboard overview
     */
    public function dashboard()
    {
        try {
            $stats = [
                'users' => [
                    'total' => User::count(),
                    'employees' => User::where('role', 'employee')->count(),
                    'employers' => User::where('role', 'employer')->count(),
                    'admins' => User::where('role', 'admin')->count(),
                    'active' => User::where('is_active', true)->count(),
                    'inactive' => User::where('is_active', false)->count(),
                ],
                'jobs' => [
                    'total' => JobListing::count(),
                    'active' => JobListing::where('status', 'active')->count(),
                    'pending' => JobListing::where('status', 'pending')->count(),
                    'declined' => JobListing::where('status', 'declined')->count(),
                    'closed' => JobListing::where('status', 'closed')->count(),
                    'this_month' => JobListing::whereMonth('created_at', now()->month)->count(),
                ],
                'applications' => [
                    'total' => Application::count(),
                    'pending' => Application::where('status', 'pending')->count(),
                    'reviewed' => Application::where('status', 'reviewed')->count(),
                    'accepted' => Application::where('status', 'accepted')->count(),
                    'rejected' => Application::where('status', 'rejected')->count(),
                    'today' => Application::whereDate('created_at', today())->count(),
                ],
                'companies' => [
                    'total' => Company::count(),
                    'verified' => Company::where('is_verified', true)->count(),
                    'pending_verification' => Company::where('is_verified', false)->count(),
                ],
                'contact' => [
                    'total' => ContactSubmission::count(),
                    'unread' => ContactSubmission::where('status', 'unread')->count(),
                    'replied' => ContactSubmission::where('status', 'replied')->count(),
                    'this_week' => ContactSubmission::where('created_at', '>=', now()->startOfWeek())->count(),
                ]
            ];

            // Recent activities
            $recent_users = User::latest()->take(5)->get(['id', 'name', 'email', 'role', 'created_at']);
            $recent_jobs = JobListing::with('company')->latest()->take(5)->get();
            $recent_applications = Application::with(['user', 'jobListing'])->latest()->take(5)->get();
            $recent_contacts = ContactSubmission::latest()->take(5)->get();

            // Monthly statistics for charts
            $monthly_registrations = User::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->pluck('count', 'month')
                ->toArray();

            $monthly_jobs = JobListing::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->pluck('count', 'month')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent' => [
                        'users' => $recent_users,
                        'jobs' => $recent_jobs,
                        'applications' => $recent_applications,
                        'contacts' => $recent_contacts
                    ],
                    'charts' => [
                        'monthly_registrations' => $monthly_registrations,
                        'monthly_jobs' => $monthly_jobs
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users with pagination and filters
     */
    public function users(Request $request)
    {
        try {
            $query = User::query();

            // Apply filters
            if ($request->has('role') && $request->role !== 'all') {
                $query->where('role', $request->role);
            }

            if ($request->has('status') && $request->status !== 'all') {
                $active = $request->status === 'active';
                $query->where('is_active', $active);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->with(['company'])
                           ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user status (activate/deactivate)
     */
    public function updateUserStatus(Request $request, User $user)
    {
        try {
            $request->validate([
                'is_active' => 'required|boolean'
            ]);

            $user->update([
                'is_active' => $request->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => $user->is_active ? 'User activated successfully' : 'User deactivated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all jobs with management options
     */
    public function jobs(Request $request)
    {
        try {
            $query = JobListing::with(['company']);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhereHas('company', function($companyQuery) use ($search) {
                          $companyQuery->where('company_name', 'like', "%{$search}%");
                      });
                });
            }

            // Order by created_at desc to show newest first
            $jobs = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $jobs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending jobs for admin approval
     */
    public function pendingJobs(Request $request)
    {
        try {
            $query = JobListing::pending()->with(['company']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhereHas('company', function($companyQuery) use ($search) {
                          $companyQuery->where('company_name', 'like', "%{$search}%");
                      });
                });
            }

            $jobs = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $jobs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load pending jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/jobs/{job}/approve",
     *     tags={"Admin"},
     *     summary="Approve Job Listing",
     *     description="Approve a pending job listing to make it active and visible to job seekers",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="job",
     *         in="path",
     *         required=true,
     *         description="Job ID to approve",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job approved successfully",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=true),
     *                 @OA\Property(property="message", type="string", example="Job approved successfully"),
     *                 @OA\Property(property="data", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - Admin role required",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=false),
     *                 @OA\Property(property="message", type="string", example="Only admins can approve job listings")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid job status",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=false),
     *                 @OA\Property(property="message", type="string", example="Only pending jobs can be approved")
     *             )
     *         )
     *     )
     * )
     *
     * Approve a job listing
     */
    public function approveJob(Request $request, JobListing $job)
    {
        try {
            // Check if user is admin
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can approve job listings'
                ], 403);
            }

            // Check if job is in pending status
            if ($job->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending jobs can be approved'
                ], 422);
            }

            $job->approve();
            $job->load('company');

            // Send notification to employer about approval
            $this->sendJobNotification(
                $job->company->user,
                'Job Approved! 🎉',
                "Your job posting '{$job->title}' has been approved and is now live on the platform.",
                'job_approved',
                ['job_id' => $job->id, 'job_title' => $job->title]
            );

            return response()->json([
                'success' => true,
                'message' => 'Job approved successfully',
                'data' => $job
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/jobs/{job}/decline",
     *     tags={"Admin"},
     *     summary="Decline Job Listing",
     *     description="Decline a pending job listing with a reason",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="job",
     *         in="path",
     *         required=true,
     *         description="Job ID to decline",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"reason"},
     *                 @OA\Property(property="reason", type="string", maxLength=500, example="Job requirements are not clear enough")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job declined successfully",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=true),
     *                 @OA\Property(property="message", type="string", example="Job declined successfully"),
     *                 @OA\Property(property="data", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - Admin role required",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=false),
     *                 @OA\Property(property="message", type="string", example="Only admins can decline job listings")
     *             )
     *         )
     *     )
     * )
     *
     * Decline a job listing
     */
    public function declineJob(Request $request, JobListing $job)
    {
        try {
            // Check if user is admin
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can decline job listings'
                ], 403);
            }

            // Validate decline reason
            $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            // Check if job is in pending status
            if ($job->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending jobs can be declined'
                ], 422);
            }

            $job->decline($request->reason);
            $job->load('company');

            // Send notification to employer about decline with reason
            $this->sendJobNotification(
                $job->company->user,
                'Job Declined',
                "Your job posting '{$job->title}' was declined. Reason: {$request->reason}",
                'job_declined',
                ['job_id' => $job->id, 'job_title' => $job->title, 'reason' => $request->reason]
            );

            return response()->json([
                'success' => true,
                'message' => 'Job declined successfully',
                'data' => $job
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to decline job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job details for admin review
     */
    public function jobDetails(JobListing $job)
    {
        try {
            $job->load(['company', 'applications']);
            
            return response()->json([
                'success' => true,
                'data' => $job
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load job details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all applications with management options
     */
    public function applications(Request $request)
    {
        try {
            $query = Application::with(['user', 'job.company.user']);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('job', function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            }

            $applications = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $applications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load applications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contact form submissions
     */
    public function contacts(Request $request)
    {
        try {
            $query = ContactSubmission::query();

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $contacts = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $contacts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load contacts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get platform analytics
     */
    public function analytics()
    {
        try {
            // User growth over the last 12 months
            $userGrowth = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $userGrowth[] = [
                    'month' => $date->format('M Y'),
                    'users' => User::whereYear('created_at', $date->year)
                                  ->whereMonth('created_at', $date->month)
                                  ->count()
                ];
            }

            // Job posting trends
            $jobTrends = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $jobTrends[] = [
                    'month' => $date->format('M Y'),
                    'jobs' => Job::whereYear('created_at', $date->year)
                                ->whereMonth('created_at', $date->month)
                                ->count()
                ];
            }

            // Application success rate
            $totalApplications = Application::count();
            $acceptedApplications = Application::where('status', 'accepted')->count();
            $successRate = $totalApplications > 0 ? ($acceptedApplications / $totalApplications) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'user_growth' => $userGrowth,
                    'job_trends' => $jobTrends,
                    'success_rate' => round($successRate, 2),
                    'top_locations' => $this->getTopJobLocations(),
                    'top_companies' => $this->getTopCompanies()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top job locations
     */
    private function getTopJobLocations()
    {
        return JobListing::select('location', DB::raw('count(*) as job_count'))
                  ->where('status', 'active')
                  ->groupBy('location')
                  ->orderBy('job_count', 'desc')
                  ->limit(10)
                  ->get();
    }

    /**
     * Get top companies by job postings
     */
    private function getTopCompanies()
    {
        return Company::withCount(['jobListings as active_jobs_count' => function($query) {
                          $query->where('status', 'active');
                      }])
                      ->orderBy('active_jobs_count', 'desc')
                      ->limit(10)
                      ->get();
    }

    /**
     * Send notification to user
     */
    private function sendJobNotification(User $user, string $title, string $message, string $type, array $data = []): void
    {
        try {
            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send notification', [
                'user_id' => $user->id,
                'title' => $title,
                'error' => $e->getMessage()
            ]);
        }
    }
}
