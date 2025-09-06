<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class HandleApiExceptions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * Handle the exception and return appropriate JSON response
     */
    private function handleException(Request $request, Throwable $e)
    {
        // Only handle API requests
        if (!$request->is('api/*')) {
            throw $e;
        }

        // Validation Exception
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Model Not Found Exception
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Authentication Exception
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Authorization Exception
        if ($e instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], Response::HTTP_FORBIDDEN);
        }

        // Not Found HTTP Exception
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Method Not Allowed Exception
        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Method not allowed'
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        // Database Connection Exception
        if (str_contains($e->getMessage(), 'could not find driver')) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // General Server Error
        return response()->json([
            'success' => false,
            'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            'error_type' => get_class($e),
            'file' => config('app.debug') ? $e->getFile() : null,
            'line' => config('app.debug') ? $e->getLine() : null,
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
