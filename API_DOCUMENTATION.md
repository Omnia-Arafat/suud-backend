# SU'UD API Documentation

Welcome to the SU'UD Project API documentation. This document provides information about the available endpoints and how to use them.

## Base URL
```
http://localhost:8000/api
```

## Authentication
This API uses Laravel Sanctum for authentication. Include the Bearer token in the Authorization header for protected endpoints.

```
Authorization: Bearer {your-token-here}
```

## Response Format
All API responses follow this standard format:

```json
{
    "success": true|false,
    "message": "Response message",
    "data": {}, // Response data (when applicable)
    "errors": {} // Validation errors (when applicable)
}
```

## Endpoints

### Health Check
Check if the API is running.

- **URL:** `/health`
- **Method:** `GET`
- **Auth required:** No

**Response:**
```json
{
    "status": "OK",
    "message": "SU'UD API is running",
    "timestamp": "2025-01-04T16:00:00.000Z"
}
```

### Public Information
Get basic API information.

- **URL:** `/public/info`
- **Method:** `GET`
- **Auth required:** No

**Response:**
```json
{
    "app_name": "SU'UD Backend",
    "version": "1.0.0",
    "description": "SU'UD Project API"
}
```

## Authentication Endpoints

### Register
Register a new user account.

- **URL:** `/auth/register`
- **Method:** `POST`
- **Auth required:** No

**Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2025-01-04T16:00:00.000Z",
            "updated_at": "2025-01-04T16:00:00.000Z"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### Login
Authenticate a user and get access token.

- **URL:** `/auth/login`
- **Method:** `POST`
- **Auth required:** No

**Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2025-01-04T16:00:00.000Z",
            "updated_at": "2025-01-04T16:00:00.000Z"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### Logout
Logout the current user (revoke current token).

- **URL:** `/auth/logout`
- **Method:** `POST`
- **Auth required:** Yes

**Response:**
```json
{
    "success": true,
    "message": "Logout successful"
}
```

### Refresh Token
Refresh the current access token.

- **URL:** `/auth/refresh`
- **Method:** `POST`
- **Auth required:** Yes

**Response:**
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "token": "2|def456...",
        "token_type": "Bearer"
    }
}
```

### Get Current User
Get information about the authenticated user.

- **URL:** `/auth/me`
- **Method:** `GET`
- **Auth required:** Yes

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2025-01-04T16:00:00.000Z",
            "updated_at": "2025-01-04T16:00:00.000Z"
        }
    }
}
```

## User Management Endpoints

### List Users
Get a paginated list of users.

- **URL:** `/users`
- **Method:** `GET`
- **Auth required:** Yes

**Query Parameters:**
- `per_page` (optional): Number of users per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com",
                "created_at": "2025-01-04T16:00:00.000Z",
                "updated_at": "2025-01-04T16:00:00.000Z"
            }
        ],
        "first_page_url": "http://localhost:8000/api/users?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost:8000/api/users?page=1",
        "links": [],
        "next_page_url": null,
        "path": "http://localhost:8000/api/users",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

### Create User
Create a new user.

- **URL:** `/users`
- **Method:** `POST`
- **Auth required:** Yes

**Body:**
```json
{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "created_at": "2025-01-04T16:00:00.000Z",
        "updated_at": "2025-01-04T16:00:00.000Z"
    }
}
```

### Show User
Get details of a specific user.

- **URL:** `/users/{id}`
- **Method:** `GET`
- **Auth required:** Yes

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2025-01-04T16:00:00.000Z",
        "updated_at": "2025-01-04T16:00:00.000Z"
    }
}
```

### Update User
Update a specific user.

- **URL:** `/users/{id}`
- **Method:** `PUT/PATCH`
- **Auth required:** Yes

**Body:**
```json
{
    "name": "John Smith",
    "email": "johnsmith@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "User updated successfully",
    "data": {
        "id": 1,
        "name": "John Smith",
        "email": "johnsmith@example.com",
        "created_at": "2025-01-04T16:00:00.000Z",
        "updated_at": "2025-01-04T16:15:00.000Z"
    }
}
```

### Delete User
Delete a specific user.

- **URL:** `/users/{id}`
- **Method:** `DELETE`
- **Auth required:** Yes

**Response:**
```json
{
    "success": true,
    "message": "User deleted successfully"
}
```

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

### Unauthorized (401)
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

### Not Found (404)
```json
{
    "success": false,
    "message": "User not found"
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Internal server error"
}
```

## Postman Collection

You can import this collection into Postman to test all endpoints:

1. Open Postman
2. Click "Import" button
3. Copy and paste the JSON below or save it as a `.json` file and import

```json
{
    "info": {
        "name": "SU'UD API",
        "description": "API collection for SU'UD Project",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "auth": {
        "type": "bearer",
        "bearer": [
            {
                "key": "token",
                "value": "{{auth_token}}",
                "type": "string"
            }
        ]
    },
    "variable": [
        {
            "key": "base_url",
            "value": "http://localhost:8000/api"
        },
        {
            "key": "auth_token",
            "value": ""
        }
    ],
    "item": [
        {
            "name": "Health Check",
            "request": {
                "method": "GET",
                "header": [],
                "url": {
                    "raw": "{{base_url}}/health",
                    "host": ["{{base_url}}"],
                    "path": ["health"]
                }
            }
        },
        {
            "name": "Public Info",
            "request": {
                "method": "GET",
                "header": [],
                "url": {
                    "raw": "{{base_url}}/public/info",
                    "host": ["{{base_url}}"],
                    "path": ["public", "info"]
                }
            }
        },
        {
            "name": "Auth - Register",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"name\": \"Test User\",\n    \"email\": \"test@example.com\",\n    \"password\": \"password123\",\n    \"password_confirmation\": \"password123\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/auth/register",
                    "host": ["{{base_url}}"],
                    "path": ["auth", "register"]
                }
            }
        },
        {
            "name": "Auth - Login",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"email\": \"test@example.com\",\n    \"password\": \"password123\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/auth/login",
                    "host": ["{{base_url}}"],
                    "path": ["auth", "login"]
                }
            }
        },
        {
            "name": "Auth - Me",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{auth_token}}"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/auth/me",
                    "host": ["{{base_url}}"],
                    "path": ["auth", "me"]
                }
            }
        },
        {
            "name": "Users - List",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{auth_token}}"
                    }
                ],
                "url": {
                    "raw": "{{base_url}}/users",
                    "host": ["{{base_url}}"],
                    "path": ["users"]
                }
            }
        }
    ]
}
```

## Testing with Postman

1. **Import the collection** above into Postman
2. **Set environment variables:**
   - `base_url`: `http://localhost:8000/api`
   - `auth_token`: (will be set after login)
3. **Start your Laravel development server:** `php artisan serve`
4. **Test the endpoints** in this order:
   1. Health Check
   2. Public Info
   3. Auth - Register (save the token from response)
   4. Set the `auth_token` variable with the token from register/login
   5. Test other protected endpoints

## Development Setup

Make sure your Laravel application is running:
```bash
php artisan serve
```

The API will be available at: `http://localhost:8000/api`
