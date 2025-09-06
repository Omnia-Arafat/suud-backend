<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * @OpenApi\Get(
     *     path="/api/jobs",
     *     tags={"Jobs"},
     *     summary="Get all job listings",
     *     description="Retrieve a paginated list of active job listings with optional filtering",
     *     @OpenApi\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for job title or description",
     *         required=false,
     *         @OpenApi\Schema(type="string")
     *     ),
     *     @OpenApi\Parameter(
     *         name="location",
     *         in="query",
     *         description="Filter by job location",
     *         required=false,
     *         @OpenApi\Schema(type="string")
     *     ),
     *     @OpenApi\Parameter(
     *         name="job_type",
     *         in="query",
     *         description="Filter by job type",
     *         required=false,
     *         @OpenApi\Schema(type="string", enum={"full_time", "part_time", "contract", "internship"})
     *     ),
     *     @OpenApi\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 50)",
     *         required=false,
     *         @OpenApi\Schema(type="integer", example=15)
     *     ),
     *     @OpenApi\Response(
     *         response=200,
     *         description="Jobs retrieved successfully",
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 @OpenApi\Property(property="success", type="boolean", example=true),
     *                 @OpenApi\Property(property="data", type="object")
     *             )
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
}
