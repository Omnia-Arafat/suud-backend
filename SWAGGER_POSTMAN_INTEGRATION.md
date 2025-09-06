# SU'UD API - Swagger & Postman Integration Guide

This guide explains how to use both Swagger UI and Postman for comprehensive API testing and documentation of the SU'UD project backend.

## 🚀 Quick Start

### Start the Laravel Server
```bash
php artisan serve
```
The API will be available at: `http://localhost:8000`

### Access Documentation

#### Swagger UI (Interactive Documentation)
- **Main URL**: http://localhost:8000/docs
- **Alternative URLs**: 
  - http://localhost:8000/documentation  
  - http://localhost:8000/api-docs

#### API Endpoints for Documentation
- **JSON**: http://localhost:8000/docs/json
- **YAML**: http://localhost:8000/docs/yaml

## 📖 Swagger Integration

### Features
- ✅ Interactive API documentation with OpenAPI 3.0
- ✅ Try-it-out functionality for all endpoints
- ✅ Bearer token authentication support
- ✅ Request/response examples
- ✅ Schema definitions for all models
- ✅ Comprehensive error response documentation

### Using Swagger UI

1. **Access the Interface**: Navigate to http://localhost:8000/docs
2. **Authenticate**: 
   - First, register a user using the `/api/auth/register` endpoint
   - Copy the token from the response
   - Click the "Authorize" button at the top
   - Enter: `Bearer {your-token}` (replace with actual token)
   - Click "Authorize"
3. **Test Endpoints**: All endpoints now have "Try it out" buttons
4. **View Responses**: See real API responses with proper formatting

### Swagger Endpoints Available

#### System Endpoints
- `GET /api/health` - Health check
- `GET /api/public/info` - API information

#### Authentication Endpoints  
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user
- `GET /api/auth/me` - Get current user (requires auth)
- `POST /api/auth/refresh` - Refresh token (requires auth)
- `POST /api/auth/logout` - Logout (requires auth)

#### User Management Endpoints (requires auth)
- `GET /api/users` - List users (paginated)
- `POST /api/users` - Create user
- `GET /api/users/{id}` - Show specific user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

## 📨 Postman Integration

### Import Collection & Environment

#### Method 1: Import Files
1. **Import Collection**: 
   - Open Postman → Import → Select `postman/SU'UD-API-Collection.postman_collection.json`
2. **Import Environment**: 
   - Open Postman → Environments → Import → Select `postman/SU'UD-Development.postman_environment.json`

#### Method 2: Import via URL (if hosted)
- Collection URL: `{your-repo-url}/postman/SU'UD-API-Collection.postman_collection.json`
- Environment URL: `{your-repo-url}/postman/SU'UD-Development.postman_environment.json`

### Postman Collection Features

#### 🔧 **Environment Variables** (Auto-managed)
- `base_url`: `http://localhost:8000`
- `api_base`: `http://localhost:8000/api`
- `auth_token`: Auto-populated after login/register
- `current_user_id`: Auto-populated with user ID
- `created_user_id`: Auto-populated for CRUD testing
- `test_user_*`: Predefined test data

#### 🧪 **Automated Tests**
Every request includes automated tests that verify:
- ✅ Response time < 2000ms
- ✅ Correct Content-Type headers
- ✅ Response structure validation
- ✅ Business logic validation
- ✅ Token management (auto-save/clear)

#### 📊 **Collection Organization**
- **System**: Health checks and info endpoints
- **Authentication**: Complete auth flow with token management  
- **User Management**: Full CRUD operations with validation

#### 🔄 **Smart Token Management**
- Tokens automatically saved after registration/login
- Tokens automatically cleared after logout
- Token refresh functionality included
- All protected requests use auto-managed tokens

### Testing Workflow with Postman

#### Quick Test Sequence
1. **Health Check**: Verify API is running
2. **Register User**: Create account and get token (auto-saved)
3. **Get Current User**: Verify authentication
4. **List Users**: Test protected endpoint
5. **Create/Update/Delete User**: Test CRUD operations
6. **Logout**: Clear tokens

#### Running Collection Tests
- **Individual Request**: Click request → Send → View tests in "Test Results"
- **Full Collection**: Collection → Run → View test summary
- **Environment Setup**: Select "SU'UD Development Environment" before testing

## 🔀 Integration Workflow

### Development Workflow
1. **Code**: Develop API endpoints in Laravel
2. **Update Swagger**: Modify SwaggerController definitions
3. **Test with Swagger**: Use interactive UI for manual testing
4. **Update Postman**: Add new requests to collection with tests
5. **Automate**: Run Postman collection for regression testing

### Documentation Workflow
1. **API Changes**: When you modify API endpoints
2. **Update Swagger Schema**: Edit the `generateSwaggerJson()` method
3. **Update Postman Collection**: Add/modify requests and tests
4. **Export Collection**: Export updated collection for sharing
5. **Commit**: Version control both Swagger and Postman files

## 🎯 Advanced Features

### Swagger Advanced Usage

#### Custom Authentication Testing
```javascript
// In Swagger UI, use the Authorize button with:
Bearer 1|your-actual-token-here
```

#### Downloading OpenAPI Spec
- **JSON Format**: http://localhost:8000/docs/json
- **YAML Format**: http://localhost:8000/docs/yaml

### Postman Advanced Usage

#### Collection Variables vs Environment Variables
- **Collection Variables**: Fixed values used across requests
- **Environment Variables**: Environment-specific values (dev, staging, prod)

#### Pre-request Scripts (Already Included)
```javascript
// Automatic timestamp setting
pm.environment.set('timestamp', new Date().toISOString());

// Request logging
console.log('Starting request to:', pm.request.url.toString());
```

#### Test Scripts (Already Included)
```javascript
// Automatic token management
if (pm.response.code === 200) {
    const response = pm.response.json();
    pm.environment.set('auth_token', response.data.token);
}

// Response validation
pm.test('Response has correct structure', function () {
    const response = pm.response.json();
    pm.expect(response.success).to.exist;
    pm.expect(response.message).to.exist;
});
```

## 🛠️ Troubleshooting

### Common Issues

#### Swagger UI Not Loading
- ✅ Check Laravel server is running: `php artisan serve`
- ✅ Verify route exists: `php artisan route:list | findstr docs`
- ✅ Clear cache: `php artisan optimize:clear`

#### Postman Authentication Issues  
- ✅ Ensure environment is selected: "SU'UD Development Environment"
- ✅ Check token format: Should be just the token, Bearer prefix added automatically
- ✅ Verify token not expired: Re-login to get fresh token

#### CORS Issues
- ✅ Verify CORS config: Check `config/cors.php`
- ✅ Ensure frontend URL allowed: `http://localhost:3000` should be in allowed origins

#### Database Issues
- ✅ Run migrations: `php artisan migrate`
- ✅ Check SQLite file exists: `database/database.sqlite`

### API Testing Checklist

#### Before Testing
- [ ] Laravel server running (`php artisan serve`)
- [ ] Database migrated (`php artisan migrate`)
- [ ] Environment configured (`.env` file)
- [ ] Postman environment selected

#### Testing Steps
- [ ] Health check endpoint works
- [ ] User registration works and returns token
- [ ] Authentication endpoints work with token
- [ ] CRUD operations work for users
- [ ] Error responses are properly formatted
- [ ] All Postman tests pass

## 🔄 Maintenance

### Updating Documentation

#### Adding New Endpoints
1. **Add to Laravel Routes**: Update `routes/api.php`
2. **Add to Swagger**: Update `SwaggerController::generateSwaggerJson()`
3. **Add to Postman**: Create new request with tests
4. **Test Both**: Verify in Swagger UI and Postman

#### Modifying Existing Endpoints
1. **Update Controller**: Modify Laravel controller logic
2. **Update Swagger Schema**: Reflect changes in OpenAPI spec
3. **Update Postman Request**: Modify request structure and tests
4. **Regression Test**: Run full Postman collection

## 📊 Monitoring & Analytics

### Performance Testing
- Use Postman collection runner for load testing
- Monitor response times in test results
- Set up alerts for response time thresholds

### API Usage Analytics
- Monitor endpoint usage through Laravel logs
- Track authentication success/failure rates
- Monitor error response patterns

## 🎉 Benefits of This Integration

### For Developers
- ✅ **Interactive Testing**: Swagger UI for manual exploration
- ✅ **Automated Testing**: Postman for regression testing  
- ✅ **Documentation**: Always up-to-date API docs
- ✅ **Token Management**: Automatic authentication handling
- ✅ **Environment Management**: Easy switching between environments

### For API Consumers
- ✅ **Self-Service Documentation**: Complete API reference
- ✅ **Try Before Integrate**: Test API without coding
- ✅ **Example Requests**: Copy-paste ready code
- ✅ **Error Reference**: Understanding error responses

### For Teams
- ✅ **Collaboration**: Shared collections and environments
- ✅ **Version Control**: Track API changes over time
- ✅ **Quality Assurance**: Automated test coverage
- ✅ **Onboarding**: New team members can understand API quickly

---

**🔗 Quick Links:**
- 📖 Swagger UI: http://localhost:8000/docs
- 📨 Postman Collection: `postman/SU'UD-API-Collection.postman_collection.json`  
- 🌍 Environment File: `postman/SU'UD-Development.postman_environment.json`
- 🚀 API Base: http://localhost:8000/api
