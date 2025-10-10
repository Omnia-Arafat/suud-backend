<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Job;
use App\Models\Application;
use App\Models\ContactSubmission;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
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
                    'total' => Job::count(),
                    'active' => Job::where('status', 'active')->count(),
                    'draft' => Job::where('status', 'draft')->count(),
                    'closed' => Job::where('status', 'closed')->count(),
                    'this_month' => Job::whereMonth('created_at', now()->month)->count(),
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
            $recent_jobs = Job::with('company.user')->latest()->take(5)->get();
            $recent_applications = Application::with(['user', 'job'])->latest()->take(5)->get();
            $recent_contacts = ContactSubmission::latest()->take(5)->get();

            // Monthly statistics for charts
            $monthly_registrations = User::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->pluck('count', 'month')
                ->toArray();

            $monthly_jobs = Job::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
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
            $query = Job::with(['company.user']);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%");
                });
            }

            $jobs = $query->paginate($request->get('per_page', 15));

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
        return Job::select('location', DB::raw('count(*) as job_count'))
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
        return Company::withCount('jobs')
                      ->orderBy('jobs_count', 'desc')
                      ->limit(10)
                      ->get();
    }
}
