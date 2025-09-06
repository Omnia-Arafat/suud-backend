<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Get employee profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'location' => $user->location,
                    'specialization' => $user->specialization,
                    'university' => $user->university,
                    'profile_summary' => $user->profile_summary,
                    'avatar_url' => $user->avatar_url,
                    'cv_url' => $user->cv_url,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]
        ]);
    }

    /**
     * Update employee profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'university' => 'nullable|string|max:255',
            'profile_summary' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'profile' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'location' => $user->location,
                    'specialization' => $user->specialization,
                    'university' => $user->university,
                    'profile_summary' => $user->profile_summary,
                    'avatar_url' => $user->avatar_url,
                    'cv_url' => $user->cv_url,
                ]
            ]
        ]);
    }

    /**
     * Upload employee avatar
     */
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully',
            'data' => [
                'avatar_url' => $user->avatar_url
            ]
        ]);
    }

    /**
     * Upload employee CV
     */
    public function uploadCv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        // Delete old CV if exists
        if ($user->cv_path) {
            Storage::disk('public')->delete($user->cv_path);
        }

        // Store new CV
        $path = $request->file('cv')->store('cvs', 'public');

        $user->update(['cv_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'CV uploaded successfully',
            'data' => [
                'cv_url' => $user->cv_url
            ]
        ]);
    }

    /**
     * Apply for a job
     */
    public function applyForJob(Request $request, JobListing $job)
    {
        // Check if job is active and accepting applications
        if (!$job->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This job is no longer accepting applications'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $request->user();

        // Check if already applied
        $existingApplication = Application::where('user_id', $user->id)
            ->where('job_listing_id', $job->id)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied for this job'
            ], Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'cover_letter' => 'nullable|string|max:2000',
            'answers' => 'nullable|array',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // Custom CV for this application
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $applicationData = [
            'user_id' => $user->id,
            'job_listing_id' => $job->id,
            'cover_letter' => $request->cover_letter,
            'answers' => $request->answers,
        ];

        // Handle custom CV upload for this application
        if ($request->hasFile('cv')) {
            $cvPath = $request->file('cv')->store('applications/cvs', 'public');
            $applicationData['resume_path'] = $cvPath;
        }

        $application = Application::create($applicationData);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully',
            'data' => [
                'application' => $application->load('jobListing:id,title,company_id', 'jobListing.company:id,company_name')
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Get all employee applications
     */
    public function getApplications(Request $request)
    {
        $user = $request->user();
        
        $query = Application::where('user_id', $user->id)
            ->with([
                'jobListing:id,title,slug,location,job_type,company_id,status,application_deadline',
                'jobListing.company:id,company_name,logo_path'
            ])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $perPage = min($request->get('per_page', 15), 50);
        $applications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'applications' => $applications->items(),
                'pagination' => [
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                ]
            ]
        ]);
    }

    /**
     * Get a specific application details
     */
    public function getApplication(Request $request, Application $application)
    {
        $user = $request->user();

        // Make sure this application belongs to the authenticated employee
        if ($application->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $application->load([
            'jobListing',
            'jobListing.company',
            'reviewer:id,name'
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'application' => $application
            ]
        ]);
    }

    /**
     * Withdraw/delete an application
     */
    public function withdrawApplication(Request $request, Application $application)
    {
        $user = $request->user();

        // Make sure this application belongs to the authenticated employee
        if ($application->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if application can be withdrawn
        if (!$application->canBeWithdrawn()) {
            return response()->json([
                'success' => false,
                'message' => 'This application cannot be withdrawn at this stage'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Delete custom CV if it was uploaded for this application
        if ($application->resume_path) {
            Storage::disk('public')->delete($application->resume_path);
        }

        $application->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application withdrawn successfully'
        ]);
    }

    /**
     * Get employee dashboard stats
     */
    public function getDashboardStats(Request $request)
    {
        $user = $request->user();

        $totalApplications = Application::where('user_id', $user->id)->count();
        $pendingApplications = Application::where('user_id', $user->id)->where('status', 'submitted')->count();
        $viewedApplications = Application::where('user_id', $user->id)->where('status', 'viewed')->count();
        $shortlistedApplications = Application::where('user_id', $user->id)->where('status', 'shortlisted')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_applications' => $totalApplications,
                    'pending_applications' => $pendingApplications,
                    'viewed_applications' => $viewedApplications,
                    'shortlisted_applications' => $shortlistedApplications,
                    'profile_completion' => $this->calculateProfileCompletion($user)
                ]
            ]
        ]);
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion(User $user): int
    {
        $fields = [
            'name', 'email', 'phone', 'location', 
            'specialization', 'university', 'profile_summary', 
            'avatar_path', 'cv_path'
        ];

        $completedFields = 0;
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($fields)) * 100);
    }
}
