<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    /**
     * @OpenApi\Get(
     *     path="/api/applications",
     *     tags={"Applications"},
     *     summary="Get user's applications",
     *     description="Get applications based on user role - employees get their applications, employers get applications for their jobs",
     *     security={"sanctum": {}},
     *     @OpenApi\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by application status",
     *         required=false,
     *         @OpenApi\Schema(type="string", enum={"pending", "reviewing", "interview", "rejected", "accepted"})
     *     ),
     *     @OpenApi\Parameter(
     *         name="job_id",
     *         in="query",
     *         description="Filter by job ID (employers only)",
     *         required=false,
     *         @OpenApi\Schema(type="integer")
     *     ),
     *     @OpenApi\Response(
     *         response=200,
     *         description="Applications retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isEmployee()) {
            // Get applications submitted by the employee
            $query = Application::where('user_id', $user->id)
                ->with(['jobListing.company']);
        } elseif ($user->isEmployer()) {
            // Get applications for jobs posted by the employer
            $companyJobIds = $user->company->jobListings()->pluck('id');
            $query = Application::whereIn('job_listing_id', $companyJobIds)
                ->with(['user', 'jobListing']);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by job (employers only)
        if ($request->has('job_id') && $request->job_id && $user->isEmployer()) {
            $query->where('job_listing_id', $request->job_id);
        }

        $applications = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    /**
     * @OpenApi\Post(
     *     path="/api/applications",
     *     tags={"Applications"},
     *     summary="Apply to a job",
     *     description="Submit an application to a job listing (employees only)",
     *     security={"sanctum": {}},
     *     @OpenApi\RequestBody(
     *         required=true,
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 required={"job_listing_id"},
     *                 @OpenApi\Property(property="job_listing_id", type="integer", example=1),
     *                 @OpenApi\Property(property="cover_letter", type="string", example="I am excited to apply for this position...")
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=201,
     *         description="Application submitted successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isEmployee()) {
            return response()->json([
                'success' => false,
                'message' => 'Only employees can submit job applications'
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'job_listing_id' => 'required|exists:job_listings,id',
            'cover_letter' => 'nullable|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $jobListing = JobListing::find($request->job_listing_id);

        // Check if job is active
        if ($jobListing->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This job listing is no longer active'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if user already applied
        $existingApplication = Application::where('user_id', $user->id)
            ->where('job_listing_id', $request->job_listing_id)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied to this job'
            ], Response::HTTP_CONFLICT);
        }

        $application = Application::create([
            'user_id' => $user->id,
            'job_listing_id' => $request->job_listing_id,
            'cover_letter' => $request->cover_letter,
            'status' => 'pending'
        ]);

        $application->load(['user', 'jobListing.company']);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully',
            'data' => $application
        ], Response::HTTP_CREATED);
    }

    /**
     * @OpenApi\Get(
     *     path="/api/applications/{id}",
     *     tags={"Applications"},
     *     summary="Get application details",
     *     description="Get detailed information about a specific application",
     *     security={"sanctum": {}},
     *     @OpenApi\Parameter(
     *         name="id",
     *         in="path",
     *         description="Application ID",
     *         required=true,
     *         @OpenApi\Schema(type="integer")
     *     ),
     *     @OpenApi\Response(
     *         response=200,
     *         description="Application retrieved successfully"
     *     )
     * )
     */
    public function show(Application $application)
    {
        $user = Auth::user();

        // Check if user has permission to view this application
        if ($user->isEmployee() && $application->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        } elseif ($user->isEmployer() && $application->jobListing->company_id !== $user->company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        $application->load(['user', 'jobListing.company']);

        return response()->json([
            'success' => true,
            'data' => $application
        ]);
    }

    /**
     * @OpenApi\Put(
     *     path="/api/applications/{id}",
     *     tags={"Applications"},
     *     summary="Update application status",
     *     description="Update the status of a job application (employers only)",
     *     security={"sanctum": {}},
     *     @OpenApi\Parameter(
     *         name="id",
     *         in="path",
     *         description="Application ID",
     *         required=true,
     *         @OpenApi\Schema(type="integer")
     *     ),
     *     @OpenApi\RequestBody(
     *         required=true,
     *         @OpenApi\MediaType(
     *             mediaType="application/json",
     *             @OpenApi\Schema(
     *                 type="object",
     *                 required={"status"},
     *                 @OpenApi\Property(property="status", type="string", enum={"pending", "reviewing", "interview", "rejected", "accepted"}, example="reviewing"),
     *                 @OpenApi\Property(property="notes", type="string", example="Candidate looks promising")
     *             )
     *         )
     *     ),
     *     @OpenApi\Response(
     *         response=200,
     *         description="Application status updated successfully"
     *     )
     * )
     */
    public function update(Request $request, Application $application)
    {
        $user = Auth::user();

        if (!$user->isEmployer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only employers can update application status'
            ], Response::HTTP_FORBIDDEN);
        }

        // Check if the application belongs to employer's company
        if ($application->jobListing->company_id !== $user->company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,reviewing,interview,rejected,accepted',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $application->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'reviewed_at' => now()
        ]);

        $application->load(['user', 'jobListing.company']);

        return response()->json([
            'success' => true,
            'message' => 'Application status updated successfully',
            'data' => $application
        ]);
    }

    /**
     * @OpenApi\Delete(
     *     path="/api/applications/{id}",
     *     tags={"Applications"},
     *     summary="Withdraw application",
     *     description="Withdraw a job application (employees only, only for pending applications)",
     *     security={"sanctum": {}},
     *     @OpenApi\Parameter(
     *         name="id",
     *         in="path",
     *         description="Application ID",
     *         required=true,
     *         @OpenApi\Schema(type="integer")
     *     ),
     *     @OpenApi\Response(
     *         response=200,
     *         description="Application withdrawn successfully"
     *     )
     * )
     */
    public function destroy(Application $application)
    {
        $user = Auth::user();

        if (!$user->isEmployee() || $application->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        // Only allow withdrawal of pending applications
        if ($application->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'You can only withdraw pending applications'
            ], Response::HTTP_BAD_REQUEST);
        }

        $application->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application withdrawn successfully'
        ]);
    }

    /**
     * @OpenApi\Get(
     *     path="/api/applications/stats",
     *     tags={"Applications"},
     *     summary="Get application statistics",
     *     description="Get statistics about user's applications (role-based)",
     *     security={"sanctum": {}},
     *     @OpenApi\Response(
     *         response=200,
     *         description="Statistics retrieved successfully"
     *     )
     * )
     */
    public function stats()
    {
        $user = Auth::user();

        if ($user->isEmployee()) {
            $stats = [
                'total' => $user->applications()->count(),
                'pending' => $user->applications()->where('status', 'pending')->count(),
                'reviewing' => $user->applications()->where('status', 'reviewing')->count(),
                'interview' => $user->applications()->where('status', 'interview')->count(),
                'accepted' => $user->applications()->where('status', 'accepted')->count(),
                'rejected' => $user->applications()->where('status', 'rejected')->count(),
            ];
        } elseif ($user->isEmployer()) {
            $companyJobIds = $user->company->jobListings()->pluck('id');
            $applicationsQuery = Application::whereIn('job_listing_id', $companyJobIds);
            
            $stats = [
                'total' => $applicationsQuery->count(),
                'pending' => $applicationsQuery->where('status', 'pending')->count(),
                'reviewing' => $applicationsQuery->where('status', 'reviewing')->count(),
                'interview' => $applicationsQuery->where('status', 'interview')->count(),
                'accepted' => $applicationsQuery->where('status', 'accepted')->count(),
                'rejected' => $applicationsQuery->where('status', 'rejected')->count(),
                'active_jobs' => $user->company->jobListings()->where('status', 'active')->count(),
                'total_jobs' => $user->company->jobListings()->count(),
            ];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
