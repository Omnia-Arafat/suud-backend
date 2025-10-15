<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\JobListing;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeDashboardController extends Controller
{
    /**
     * Get employee dashboard overview
     */
    public function dashboard()
    {
        try {
            $user = Auth::user();

            $stats = [
                'applications' => [
                    'total' => $user->applications()->count(),
                    'pending' => $user->applications()->where('status', 'pending')->count(),
                    'reviewed' => $user->applications()->where('status', 'reviewed')->count(),
                    'accepted' => $user->applications()->where('status', 'accepted')->count(),
                    'rejected' => $user->applications()->where('status', 'rejected')->count(),
                    'this_month' => $user->applications()->whereMonth('created_at', now()->month)->count(),
                ],
                'jobs' => [
                    'available' => JobListing::where('status', 'active')->count(),
                    'applied_to' => $user->applications()->count(),
                    'new_today' => JobListing::where('status', 'active')->whereDate('created_at', today())->count(),
                    'matching_specialization' => JobListing::where('status', 'active')
                        ->where('title', 'like', '%' . $user->specialization . '%')
                        ->orWhere('description', 'like', '%' . $user->specialization . '%')
                        ->count(),
                ],
                'profile' => [
                    'completion' => $this->calculateProfileCompletion($user),
                    'views' => 0, // Can be implemented later
                    'cv_uploaded' => !empty($user->cv_path),
                    'avatar_uploaded' => !empty($user->avatar_path),
                ]
            ];

            // Recent applications
            $recent_applications = $user->applications()
                ->with(['job.company.user'])
                ->latest()
                ->take(5)
                ->get();

            // Recommended jobs based on specialization
            $recommended_jobs = JobListing::where('status', 'active')
                ->where(function($query) use ($user) {
                    $query->where('title', 'like', '%' . $user->specialization . '%')
                          ->orWhere('description', 'like', '%' . $user->specialization . '%')
                          ->orWhere('requirements', 'like', '%' . $user->specialization . '%');
                })
                ->whereNotIn('id', $user->applications()->pluck('job_listing_id'))
                ->with(['company.user'])
                ->latest()
                ->take(5)
                ->get();

            // Latest job opportunities
            $latest_jobs = JobListing::where('status', 'active')
                ->whereNotIn('id', $user->applications()->pluck('job_listing_id'))
                ->with(['company.user'])
                ->latest()
                ->take(8)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_applications' => $recent_applications,
                    'recommended_jobs' => $recommended_jobs,
                    'latest_jobs' => $latest_jobs,
                    'profile' => $user->only(['name', 'email', 'specialization', 'university', 'profile_summary', 'avatar_url', 'cv_url'])
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
     * Get employee applications with pagination
     */
    public function myApplications(Request $request)
    {
        try {
            $user = Auth::user();
            $query = $user->applications()->with(['job.company.user']);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('job', function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('company', function($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $applications = $query->paginate($request->get('per_page', 10));

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
     * Get available jobs for employee
     */
    public function availableJobs(Request $request)
    {
        try {
            $user = Auth::user();
            $query = JobListing::where('status', 'active')
                        ->whereNotIn('id', $user->applications()->pluck('job_listing_id'))
                        ->with(['company.user']);

            // Apply filters
            if ($request->has('location') && $request->location !== 'all') {
                $query->where('location', 'like', '%' . $request->location . '%');
            }

            if ($request->has('employment_type') && $request->employment_type !== 'all') {
                $query->where('employment_type', $request->employment_type);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('requirements', 'like', "%{$search}%")
                      ->orWhereHas('company', function($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sort by relevance to user's specialization if no specific sorting
            if (!$request->has('sort') && !empty($user->specialization)) {
                $query->orderByRaw(
                    "CASE
                        WHEN title LIKE '%{$user->specialization}%' THEN 1
                        WHEN description LIKE '%{$user->specialization}%' THEN 2
                        WHEN requirements LIKE '%{$user->specialization}%' THEN 3
                        ELSE 4
                    END"
                )->orderBy('created_at', 'desc');
            } else {
                $query->latest();
            }

            $jobs = $query->paginate($request->get('per_page', 12));

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
     * Update employee profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'specialization' => 'sometimes|required|string|max:255',
                'university' => 'sometimes|required|string|max:255',
                'profile_summary' => 'sometimes|required|string|max:1000',
                'phone' => 'sometimes|nullable|string|max:20',
                'location' => 'sometimes|nullable|string|max:255',
                'avatar' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'cv' => 'sometimes|nullable|mimes:pdf,doc,docx|max:5120' // 5MB max
            ]);

            $updateData = $request->only([
                'name', 'specialization', 'university', 'profile_summary', 'phone', 'location'
            ]);

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar_path) {
                    Storage::delete($user->avatar_path);
                }

                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $updateData['avatar_path'] = $avatarPath;
            }

            // Handle CV upload
            if ($request->hasFile('cv')) {
                // Delete old CV if exists
                if ($user->cv_path) {
                    Storage::delete($user->cv_path);
                }

                $cvPath = $request->file('cv')->store('cvs', 'public');
                $updateData['cv_path'] = $cvPath;
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user->fresh(),
                    'completion' => $this->calculateProfileCompletion($user->fresh())
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change employee password
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
     * Get employee profile
     */
    public function getProfile()
    {
        try {
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'completion' => $this->calculateProfileCompletion($user),
                    'stats' => [
                        'total_applications' => $user->applications()->count(),
                        'pending_applications' => $user->applications()->where('status', 'pending')->count(),
                        'accepted_applications' => $user->applications()->where('status', 'accepted')->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion($user)
    {
        $fields = [
            'name' => !empty($user->name),
            'email' => !empty($user->email),
            'specialization' => !empty($user->specialization),
            'university' => !empty($user->university),
            'profile_summary' => !empty($user->profile_summary),
            'phone' => !empty($user->phone),
            'location' => !empty($user->location),
            'avatar' => !empty($user->avatar_path),
            'cv' => !empty($user->cv_path),
        ];

        $completedFields = array_filter($fields);
        $totalFields = count($fields);
        $completedCount = count($completedFields);

        return round(($completedCount / $totalFields) * 100);
    }

    /**
     * Get application statistics
     */
    public function applicationStats()
    {
        try {
            $user = Auth::user();

            $stats = [
                'monthly_applications' => [],
                'status_breakdown' => [
                    'pending' => $user->applications()->where('status', 'pending')->count(),
                    'reviewed' => $user->applications()->where('status', 'reviewed')->count(),
                    'accepted' => $user->applications()->where('status', 'accepted')->count(),
                    'rejected' => $user->applications()->where('status', 'rejected')->count(),
                ],
                'application_trends' => []
            ];

            // Get monthly application data for the last 6 months
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $count = $user->applications()
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();

                $stats['monthly_applications'][] = [
                    'month' => $date->format('M Y'),
                    'applications' => $count
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load application statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get saved jobs for employee
     */
    public function savedJobs(Request $request)
    {
        try {
            $user = Auth::user();

            // For now, return empty array since we don't have a saved_jobs table yet
            // This is a placeholder implementation
            $savedJobs = collect([]);

            return response()->json([
                'success' => true,
                'data' => [
                    'jobs' => $savedJobs,
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $request->get('per_page', 15),
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load saved jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save a job for employee
     */
    public function saveJob(Request $request)
    {
        try {
            $request->validate([
                'job_id' => 'required|integer|exists:job_listings,id'
            ]);

            $user = Auth::user();

            // For now, return success since we don't have a saved_jobs table yet
            // This is a placeholder implementation
            return response()->json([
                'success' => true,
                'message' => 'Job saved successfully',
                'data' => [
                    'job_id' => $request->job_id,
                    'saved' => true
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove saved job for employee
     */
    public function removeSavedJob($jobId)
    {
        try {
            $user = Auth::user();

            // For now, return success since we don't have a saved_jobs table yet
            // This is a placeholder implementation
            return response()->json([
                'success' => true,
                'message' => 'Job removed from saved list',
                'data' => [
                    'job_id' => $jobId,
                    'saved' => false
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove saved job',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
