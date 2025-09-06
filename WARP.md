# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

The **SU'UD Backend** is a Laravel 12.0 API backend built with:
- Laravel 12.0 with PHP 8.3+
- Laravel Sanctum for API authentication
- SQLite database for development
- RESTful API architecture
- CORS configured for Next.js frontend communication

This is part of a larger SU'UD project with separate frontend (Next.js) and backend (Laravel) repositories.

## Common Development Commands

### Development Server
```bash
php artisan serve          # Start development server on http://localhost:8000
```

### Database Operations
```bash
php artisan migrate         # Run database migrations
php artisan migrate:fresh   # Fresh migration (drops all tables)
php artisan migrate:rollback # Rollback last migration
php artisan db:seed         # Run database seeders
```

### Code Generation
```bash
php artisan make:controller ControllerName --resource  # Create resource controller
php artisan make:model ModelName -m                    # Create model with migration
php artisan make:middleware MiddlewareName              # Create middleware
php artisan make:request RequestName                    # Create form request
php artisan make:seeder SeederName                      # Create database seeder
```

### Cache & Configuration
```bash
php artisan config:cache    # Cache configuration
php artisan config:clear    # Clear configuration cache
php artisan route:cache     # Cache routes
php artisan route:clear     # Clear route cache
php artisan optimize        # Cache config, routes, and views
php artisan optimize:clear  # Clear all cached data
```

### Queue Operations
```bash
php artisan queue:work      # Process queue jobs
php artisan queue:listen    # Listen for queue jobs
```

### Testing
```bash
php artisan test           # Run PHPUnit tests
```

## Architecture & Key Patterns

### API Structure
- **Base URL**: `http://localhost:8000/api`
- **Authentication**: Laravel Sanctum with Bearer tokens
- **Response Format**: Consistent JSON responses with `success`, `message`, `data`, and `errors` fields
- **CORS**: Configured for `localhost:3000` (Next.js frontend)

### Directory Structure
- `app/Http/Controllers/Api/`: API controllers
- `routes/api.php`: API route definitions
- `config/cors.php`: CORS configuration
- `database/migrations/`: Database schema migrations
- `database/seeders/`: Database seeders

### Route Groups
- `/health`: Health check endpoint
- `/public/*`: Public endpoints (no auth required)
- `/auth/*`: Authentication endpoints
- Protected routes require `auth:sanctum` middleware

### Controller Patterns
- **AuthController**: Handles registration, login, logout, token refresh
- **UserController**: Resource controller for user CRUD operations
- Consistent response format with success/error states
- Proper HTTP status codes (200, 201, 401, 422, etc.)

### Authentication Flow
1. Register/Login → Receive Bearer token
2. Include token in `Authorization: Bearer {token}` header
3. Access protected routes
4. Logout → Token revocation

## Configuration Files

### Core Config
- `.env`: Environment configuration (app name, database, etc.)
- `config/cors.php`: CORS settings for frontend communication
- `bootstrap/app.php`: Application bootstrap with middleware configuration
- `config/sanctum.php`: Sanctum authentication configuration

### Database
- **Default**: SQLite (file-based, good for development)
- **Location**: `database/database.sqlite`
- **Migrations**: Auto-run on project creation

## API Documentation

### Interactive Testing
- **Postman Collection**: Available in `API_DOCUMENTATION.md`
- **Base URL**: `http://localhost:8000/api`
- **Authentication**: Bearer token in Authorization header

### Key Endpoints
- `GET /health` - Health check
- `POST /auth/register` - User registration
- `POST /auth/login` - User login
- `POST /auth/logout` - User logout (requires auth)
- `GET /auth/me` - Get current user (requires auth)
- `GET /users` - List users (requires auth)

### Response Format
```json
{
    "success": true|false,
    "message": "Response message",
    "data": {}, // Response data (optional)
    "errors": {} // Validation errors (optional)
}
```

## Development Notes

### Laravel 12.0 Features
- Uses modern Laravel application structure
- Middleware configured in `bootstrap/app.php`
- API routes auto-registered
- Sanctum for API authentication

### Database
- SQLite for development (no additional setup required)
- Migrations and seeders for schema management
- Eloquent ORM for database interactions

### CORS Configuration
- Configured for Next.js frontend (`localhost:3000`)
- Supports credentials for authentication
- All HTTP methods allowed for API endpoints

### Error Handling
- Validation errors return 422 with detailed error messages
- Authentication errors return 401
- Server errors return 500 with appropriate messages

## Development Workflow

1. **Start the server**: `php artisan serve`
2. **Run migrations**: `php artisan migrate` (if needed)
3. **Test endpoints**: Use Postman with provided collection
4. **Check logs**: `storage/logs/laravel.log` for debugging
5. **Clear cache**: `php artisan optimize:clear` when config changes

## Integration with Frontend

### CORS Setup
The backend is configured to accept requests from:
- `http://localhost:3000` (Next.js default)
- `http://127.0.0.1:3000`

### Authentication
- Frontend should store the Bearer token from login/register responses
- Include token in all authenticated requests
- Handle token expiration and refresh as needed

### API Communication
- All API endpoints return JSON
- Use appropriate HTTP methods (GET, POST, PUT, DELETE)
- Handle error responses appropriately in frontend
