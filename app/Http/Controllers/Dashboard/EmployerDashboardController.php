<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Job;
use App\Models\Application;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployerDashboardController extends Controller
{
    /**
     * Get employer dashboard overview
     */
    public function dashboard()
    {
        try {
            $user = Auth::user();
            $company = $user->company;
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company profile not found. Please complete your company setup.'
                ], 404);
            }

            // Get jobs posted by this employer's company
            $companyJobs = $company->jobs();
            
            $stats = [
                'jobs' => [
                    'total' => $companyJobs->count(),
                    'active' => $companyJobs->where('status', 'active')->count(),
                    'draft' => $companyJobs->where('status', 'draft')->count(),
                    'closed' => $companyJobs->where('status', 'closed')->count(),
                    'this_month' => $companyJobs->whereMonth('created_at', now()->month)->count(),
                ],
                'applications' => [
                    'total' => Application::whereHas('job', function($q) use ($company) {
                        $q->where('company_id', $company->id);
                    })->count(),
                    'pending' => Application::whereHas('job', function($q) use ($company) {
                        $q->where('company_id', $company->id);
                    })->where('status', 'pending')->count(),
                    'reviewed' => Application::whereHas('job', function($q) use ($company) {
                        $q->where('company_id', $company->id);
                    })->where('status', 'reviewed')->count(),
                    'accepted' => Application::whereHas('job', function($q) use ($company) {
                        $q->where('company_id', $company->id);
                    })->where('status', 'accepted')->count(),
                    'new_today' => Application::whereHas('job', function($q) use ($company) {
                        $q->where('company_id', $company->id);
                    })->whereDate('created_at', today())->count(),
                ],
                'company' => [
                    'profile_completion' => $this->calculateCompanyCompletion($company),
                    'is_verified' => $company->is_verified,
                    'total_views' => 0, // Can be implemented later
                    'employee_count' => $company->employee_count ?? 0,
                ]
            ];

            // Recent applications to company jobs
            $recent_applications = Application::whereHas('job', function($q) use ($company) {
                $q->where('company_id', $company->id);
            })->with(['user', 'job'])->latest()->take(5)->get();

            // Company's active jobs
            $active_jobs = $company->jobs()
                ->where('status', 'active')
                ->withCount('applications')
                ->latest()
                ->take(5)
                ->get();

            // Top performing jobs (by application count)
            $top_jobs = $company->jobs()
                ->withCount('applications')
                ->orderBy('applications_count', 'desc')
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_applications' => $recent_applications,
                    'active_jobs' => $active_jobs,
                    'top_jobs' => $top_jobs,
                    'company' => $company
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
     * Get employer's job postings
     */
    public function myJobs(Request $request)
    {
        try {
            $user = Auth::user();
            $company = $user->company;
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company profile not found'
                ], 404);
            }

            $query = $company->jobs()->withCount('applications');

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

            $jobs = $query->paginate($request->get('per_page', 10));

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
     * Get applications for employer's jobs
     */
    public function jobApplications(Request $request)
    {
        try {
            $user = Auth::user();
            $company = $user->company;
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company profile not found'
                ], 404);
            }

            $query = Application::whereHas('job', function($q) use ($company) {
                $q->where('company_id', $company->id);
            })->with(['user', 'job']);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('job_id') && $request->job_id !== 'all') {
                $query->where('job_id', $request->job_id);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('specialization', 'like', "%{$search}%")
                      ->orWhere('university', 'like', "%{$search}%");
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
     * Update application status
     */
    public function updateApplicationStatus(Request $request, Application $application)
    {
        try {
            $user = Auth::user();
            $company = $user->company;
            
            // Verify this application belongs to employer's company
            if (!$application->job || $application->job->company_id !== $company->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this application'
                ], 403);
            }

            $request->validate([
                'status' => 'required|in:pending,reviewed,accepted,rejected',
                'notes' => 'nullable|string|max:1000'
            ]);

            $application->update([
                'status' => $request->status,
                'notes' => $request->notes,
                'reviewed_by' => $user->id,
                'reviewed_at' => now()
            ]);

            // TODO: Send notification email to applicant

            return response()->json([
                'success' => true,
                'message' => 'Application status updated successfully',
                'data' => $application->load(['user', 'job'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update application status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update company profile
     */
    public function updateCompany(Request $request)
    {
        try {
            $user = Auth::user();
            $company = $user->company;
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company profile not found'
                ], 404);
            }

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string|max:2000',
                'industry' => 'sometimes|required|string|max:255',
                'website' => 'sometimes|nullable|url|max:255',
                'phone' => 'sometimes|nullable|string|max:20',
                'address' => 'sometimes|nullable|string|max:500',
                'employee_count' => 'sometimes|nullable|integer|min:1',
                'founded_year' => 'sometimes|nullable|integer|min:1800|max:' . date('Y'),
                'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $updateData = $request->only([
                'name', 'description', 'industry', 'website', 'phone', 
                'address', 'employee_count', 'founded_year'
            ]);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($company->logo_path) {
                    Storage::delete($company->logo_path);
                }
                
                $logoPath = $request->file('logo')->store('company-logos', 'public');
                $updateData['logo_path'] = $logoPath;
            }

            $company->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Company profile updated successfully',
                'data' => [
                    'company' => $company->fresh(),
                    'completion' => $this->calculateCompanyCompletion($company->fresh())
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get company profile
     */
    public function getCompany()
    {
        try {
            $user = Auth::user();
            $company = $user->company;
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company profile not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'company' => $company,
                    'completion' => $this->calculateCompanyCompletion($company),
                    'stats' => [
                        'total_jobs' => $company->jobs()->count(),
                        'active_jobs' => $company->jobs()->where('status', 'active')->count(),
                        'total_applications' => Application::whereHas('job', function($q) use ($company) {
                            $q->where('company_id', $company->id);
                        })->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load company profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change employer password
     */
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = Auth::user();

            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get hiring analytics
     */
    public function analytics()
    {
        try {
            $user = Auth::user();
            $company = $user->company;
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company profile not found'
                ], 404);
            }

            $stats = [
                'monthly_applications' => [],
                'application_status_breakdown' => [
                    'pending' => Application::whereHas('job', function($q) use ($company) {
                        $q->where('company_id', $company->id);
                    })->where('status', 'pending')->count(),
                    'reviewed' => Application::whereHas('job', function($q) use ($company) {
                        $q->where('company_id', $company->id);
                    })->where('status', 'reviewed')->count(),
                    'accepted' => Application::whereHas('job', function($q) use ($company) {
                        $q->where('company_id', $company->id);
                    })->where('status', 'accepted')->count(),
                    'rejected' => Application::whereHas('job', function($q) use ($company) {
                        $q->where('company_id', $company->id);
                    })->where('status', 'rejected')->count(),
                ],
                'job_performance' => []
            ];

            // Get monthly application data for the last 6 months
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $count = Application::whereHas('job', function($q) use ($company) {
                    $q->where('company_id', $company->id);
                })->whereYear('created_at', $date->year)
                  ->whereMonth('created_at', $date->month)
                  ->count();
                
                $stats['monthly_applications'][] = [
                    'month' => $date->format('M Y'),
                    'applications' => $count
                ];
            }

            // Job performance data
            $stats['job_performance'] = $company->jobs()
                ->withCount('applications')
                ->orderBy('applications_count', 'desc')
                ->limit(10)
                ->get(['id', 'title', 'created_at'])
                ->map(function($job) {
                    return [
                        'job_title' => $job->title,
                        'applications_count' => $job->applications_count,
                        'created_at' => $job->created_at->format('M d, Y')
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $stats
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
     * Calculate company profile completion percentage
     */
    private function calculateCompanyCompletion($company)
    {
        $fields = [
            'name' => !empty($company->name),
            'description' => !empty($company->description),
            'industry' => !empty($company->industry),
            'website' => !empty($company->website),
            'phone' => !empty($company->phone),
            'address' => !empty($company->address),
            'employee_count' => !empty($company->employee_count),
            'founded_year' => !empty($company->founded_year),
            'logo' => !empty($company->logo_path),
        ];

        $completedFields = array_filter($fields);
        $totalFields = count($fields);
        $completedCount = count($completedFields);

        return round(($completedCount / $totalFields) * 100);
    }
}
