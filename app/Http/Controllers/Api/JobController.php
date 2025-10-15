<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/jobs",
     *     tags={"Jobs"},
     *     summary="Get all job listings",
     *     description="Retrieve a paginated list of active job listings with optional filtering",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for job title or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="location",
     *         in="query",
     *         description="Filter by job location",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="job_type",
     *         in="query",
     *         description="Filter by job type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"full_time", "part_time", "contract", "internship"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jobs retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     *
     * Get paginated list of active jobs with filtering
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = JobListing::active()
            ->with(['company:id,company_name,logo_path,location'])
            ->search($request->get('search'))
            ->location($request->get('location'))
            ->jobType($request->get('job_type'));

        // Additional filters
        if ($request->has('category') && $request->category) {
            $query->where('category', 'LIKE', '%' . $request->category . '%');
        }

        if ($request->has('experience_level') && $request->experience_level) {
            $query->where('experience_level', $request->experience_level);
        }

        if ($request->has('salary_min') && $request->salary_min) {
            $query->where('salary_max', '>=', $request->salary_min);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortFields = ['created_at', 'title', 'salary_min', 'views_count', 'applications_count'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->get('per_page', 15), 50); // Max 50 per page
        $jobs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'jobs' => $jobs->items(),
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                    'from' => $jobs->firstItem(),
                    'to' => $jobs->lastItem(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/jobs/{job:slug}",
     *     tags={"Jobs"},
     *     summary="Get job details",
     *     description="Get detailed information for a single job by slug",
     *     @OA\Parameter(
     *         name="job:slug",
     *         in="path",
     *         required=true,
     *         description="Job slug identifier",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job not found or no longer available",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Job not found or no longer available")
     *         )
     *     )
     * )
     *
     * Get detailed information for a single job
     *
     * @param JobListing $job
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(JobListing $job)
    {
        // Only show active jobs to public
        if ($job->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Job not found or no longer available'
            ], 404);
        }

        // Increment view count
        $job->incrementViews();

        // Load related data
        $job->load(['company:id,company_name,logo_path,website,description,industry,company_size,location,founded_year']);

        return response()->json([
            'success' => true,
            'data' => [
                'job' => $job
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/jobs/filters",
     *     tags={"Jobs"},
     *     summary="Get job filter options",
     *     description="Get available filter options for job listings",
     *     @OA\Response(
     *         response=200,
     *         description="Filter options retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="job_types", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="experience_levels", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="locations", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     *
     * Get available filter options for jobs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function filters()
    {
        $jobTypes = JobListing::active()->distinct()->pluck('job_type')->filter()->values();
        $experienceLevels = JobListing::active()->distinct()->pluck('experience_level')->filter()->values();
        $categories = JobListing::active()->distinct()->pluck('category')->filter()->values();
        $locations = JobListing::active()->distinct()->pluck('location')->filter()->values();

        return response()->json([
            'success' => true,
            'data' => [
                'job_types' => $jobTypes,
                'experience_levels' => $experienceLevels,
                'categories' => $categories,
                'locations' => $locations,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/jobs/stats",
     *     tags={"Jobs"},
     *     summary="Get job statistics",
     *     description="Get job and company statistics for public display",
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_active_jobs", type="integer", example=450),
     *                 @OA\Property(property="total_companies", type="integer", example=120)
     *             )
     *         )
     *     )
     * )
     *
     * Get job statistics for public display
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $totalJobs = JobListing::active()->count();
        $totalCompanies = JobListing::active()
            ->join('companies', 'job_listings.company_id', '=', 'companies.id')
            ->distinct('companies.id')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_active_jobs' => $totalJobs,
                'total_companies' => $totalCompanies,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/jobs/recent",
     *     tags={"Jobs"},
     *     summary="Get recent jobs",
     *     description="Get recent job listings for homepage display",
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of jobs to return (max 10)",
     *         required=false,
     *         @OA\Schema(type="integer", example=4, maximum=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recent jobs retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="jobs", type="array", @OA\Items())
     *             )
     *         )
     *     )
     * )
     *
     * Get recent jobs for homepage display
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recent(Request $request)
    {
        $limit = min($request->get('limit', 4), 10); // Max 10 recent jobs
        
        $jobs = JobListing::active()
            ->with(['company:id,company_name,logo_path'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'jobs' => $jobs
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/employer/jobs",
     *     tags={"Employer"},
     *     summary="Get Employer's Job Listings",
     *     description="Get all job listings posted by the authenticated employer's company",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by job status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "active", "declined", "closed"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in job title and description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 50)",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employer jobs retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="jobs", type="array", @OA\Items()),
     *                 @OA\Property(
     *                     property="pagination",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied - Employer role required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Only employers can access job listings")
     *         )
     *     )
     * )
     *
     * Get jobs for the authenticated employer
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function employerJobs(Request $request)
    {
        $user = $request->user();

        // Only employers can access their jobs
        if (!$user->isEmployer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only employers can access job listings'
            ], 403);
        }

        // Get the employer's company
        $company = $user->company;
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company profile not found'
            ], 400);
        }

        $query = JobListing::where('company_id', $company->id)
            ->with(['company:id,company_name,logo_path'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->search . '%');
            });
        }

        $perPage = min($request->get('per_page', 15), 50);
        $jobs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'jobs' => $jobs->items(),
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                    'from' => $jobs->firstItem(),
                    'to' => $jobs->lastItem(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/jobs",
     *     tags={"Jobs"},
     *     summary="Create a new job listing",
     *     description="Create a new job listing (employers only)",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"title", "description", "requirements", "location", "job_type"},
     *             @OA\Property(property="title", type="string", example="Senior Software Developer"),
     *             @OA\Property(property="description", type="string", example="We are looking for a passionate developer..."),
     *             @OA\Property(property="requirements", type="string", example="5+ years experience with React..."),
     *             @OA\Property(property="location", type="string", example="Riyadh, Saudi Arabia"),
     *             @OA\Property(property="job_type", type="string", enum={"full-time", "part-time", "contract", "internship"}),
     *             @OA\Property(property="experience_level", type="string", enum={"entry", "mid", "senior", "executive"}),
     *             @OA\Property(property="salary_min", type="number", example=5000),
     *             @OA\Property(property="salary_max", type="number", example=8000),
     *             @OA\Property(property="category", type="string", example="Technology"),
     *             @OA\Property(property="application_deadline", type="string", format="date"),
     *             @OA\Property(property="positions_available", type="integer", example=1),
     *             @OA\Property(property="skills", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="status", type="string", enum={"draft", "pending"}, example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Job created successfully"
     *     )
     * )
     *
     * Create a new job listing
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Only employers can create jobs
        if (!$user->isEmployer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only employers can create job listings'
            ], 403);
        }

        // Get the employer's company
        $company = $user->company;
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company profile not found. Please complete your company profile first.'
            ], 400);
        }

        $validator = \Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'requirements' => 'required|string|max:5000',
            'location' => 'required|string|max:255',
            'job_type' => 'required|in:full-time,part-time,contract,internship,remote',
            'experience_level' => 'nullable|in:entry,mid,senior,executive',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'category' => 'nullable|string|max:100',
            'application_deadline' => 'nullable|date|after:today',
            'positions_available' => 'nullable|integer|min:1',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:50',
            'status' => 'nullable|in:draft,pending'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jobData = $request->only([
                'title', 'description', 'requirements', 'location', 'job_type',
                'experience_level', 'salary_min', 'salary_max', 'category',
                'application_deadline', 'positions_available', 'skills', 'status'
            ]);

            // Set company_id and default values
            $jobData['company_id'] = $company->id;
            $jobData['salary_currency'] = 'SAR'; // Default currency
            $jobData['status'] = $jobData['status'] ?? 'pending';
            $jobData['experience_level'] = $jobData['experience_level'] ?? 'entry';
            $jobData['positions_available'] = $jobData['positions_available'] ?? 1;

            $job = JobListing::create($jobData);

            // Load company relationship for response
            $job->load('company:id,company_name,logo_path');

            return response()->json([
                'success' => true,
                'message' => 'Job listing created successfully',
                'data' => [
                    'job' => $job
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Job creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create job listing. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing job listing
     *
     * @param Request $request
     * @param JobListing $job
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, JobListing $job)
    {
        $user = $request->user();

        // Only employers can update jobs
        if (!$user->isEmployer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only employers can update job listings'
            ], 403);
        }

        // Check if the job belongs to the employer's company
        if ($job->company_id !== $user->company->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update your own job listings'
            ], 403);
        }

        $validator = \Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:5000',
            'requirements' => 'sometimes|required|string|max:5000',
            'location' => 'sometimes|required|string|max:255',
            'job_type' => 'sometimes|required|in:full-time,part-time,contract,internship,remote',
            'experience_level' => 'nullable|in:entry,mid,senior,executive',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'category' => 'nullable|string|max:100',
            'application_deadline' => 'nullable|date|after:today',
            'positions_available' => 'nullable|integer|min:1',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:50',
            'status' => 'nullable|in:draft,pending,active,closed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jobData = $request->only([
                'title', 'description', 'requirements', 'location', 'job_type',
                'experience_level', 'salary_min', 'salary_max', 'category',
                'application_deadline', 'positions_available', 'skills', 'status'
            ]);

            $job->update($jobData);
            $job->load('company:id,company_name,logo_path');

            return response()->json([
                'success' => true,
                'message' => 'Job listing updated successfully',
                'data' => [
                    'job' => $job
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Job update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update job listing. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a job listing
     *
     * @param JobListing $job
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(JobListing $job)
    {
        $user = request()->user();

        // Only employers can delete jobs
        if (!$user->isEmployer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only employers can delete job listings'
            ], 403);
        }

        // Check if the job belongs to the employer's company
        if ($job->company_id !== $user->company->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own job listings'
            ], 403);
        }

        try {
            $job->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job listing deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Job deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete job listing. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
