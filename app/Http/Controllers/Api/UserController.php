<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $users = User::paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     * 
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'university' => 'nullable|string|max:255',
            'profile_summary' => 'nullable|string|max:1000',
            'company_name' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update($request->only([
            'name', 'email', 'phone', 'location', 'specialization', 
            'university', 'profile_summary', 'is_active'
        ]));

        // Update company profile for employers
        if ($user->role === 'employer' && $request->has('company_name')) {
            if ($user->company) {
                $user->company->update([
                    'company_name' => $request->company_name,
                    'location' => $request->location ?? $user->location,
                ]);
            } else {
                $user->company()->create([
                    'company_name' => $request->company_name,
                    'location' => $request->location ?? $user->location,
                ]);
            }
        }

        $user->load('company');

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
