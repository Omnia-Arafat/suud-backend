# SU'UD API Documentation - Updated

## 🚀 Quick Start
- **Base URL**: `http://localhost:8000/api`
- **Swagger UI**: `http://localhost:8000/docs`
- **Database**: SQLite (development)

## 📋 New Features Added

### ✅ Enhanced Job Management System
- Advanced filtering and pagination for job listings
- Real-time job application system
- Admin job approval/decline workflow
- Client-side filtering capabilities

### ✅ Improved Admin Dashboard
- Comprehensive job statistics by status
- Interactive navigation to management pages
- Real-time updates for pending jobs

### ✅ Better User Experience
- PrimeReact Toast notifications instead of alerts
- Enhanced error handling and fallback mechanisms
- Professional UI with animations and transitions

## 📍 API Endpoints

### System Health
```
GET /api/health
```
**Response:**
```json
{
  "success": true,
  "message": "SU'UD API is running successfully!",
  "timestamp": "2025-10-11T23:57:37Z",
  "version": "1.0.0"
}
```

### Authentication
```
POST /api/auth/register    # Register new user
POST /api/auth/login       # Login user  
GET  /api/auth/me          # Get current user (requires auth)
POST /api/auth/refresh     # Refresh token
POST /api/auth/logout      # Logout user
```

### Public Job Listings (Enhanced)
```
GET /api/jobs              # Get all active jobs with advanced filtering
GET /api/jobs/{id}         # Get job details by ID
GET /api/jobs/filters      # Get available filter options
GET /api/jobs/recent       # Get recent jobs
GET /api/jobs/stats        # Get job statistics
```

**Enhanced Job Filtering Parameters:**
- `search` - Search in title, description, company name
- `location` - Filter by job location
- `job_type` - Filter by employment type
- `category` - Filter by job category
- `experience_level` - Filter by required experience
- `salary_min` - Minimum salary filter
- `page` - Page number for pagination
- `per_page` - Items per page (max 50)
- `sort_by` - Sort by field (created_at, title, salary_min, views_count)
- `sort_order` - Sort direction (asc, desc)

### Job Applications System
```
POST /api/applications             # Submit job application
GET  /api/applications             # Get user's applications
GET  /api/applications/{id}        # Get application details
PUT  /api/applications/{id}        # Update application status (employers)
DELETE /api/applications/{id}      # Withdraw application (employees)
GET  /api/applications/stats       # Get application statistics
```

**Application Status Flow:**
1. `pending` - Initial application status
2. `reviewing` - Under employer review
3. `interview` - Invited for interview
4. `accepted` - Application accepted
5. `rejected` - Application rejected

### Employer Job Management
```
GET  /api/jobs/my-jobs             # Get employer's job listings
POST /api/jobs                     # Create new job listing
PUT  /api/jobs/{id}                # Update job listing
DELETE /api/jobs/{id}              # Delete job listing
```

### Admin Dashboard (Enhanced)
```
GET  /api/admin/dashboard          # Enhanced dashboard with accurate stats
GET  /api/admin/users              # User management with filters
PATCH /api/admin/users/{id}/status # Update user status
GET  /api/admin/companies          # Company verification queue
PATCH /api/admin/companies/{id}/verification # Verify company
```

### Admin Job Management (New)
```
GET  /api/admin/jobs               # Get all jobs for admin review
GET  /api/admin/jobs/{id}/details  # Get detailed job information
PATCH /api/admin/jobs/{id}/approve # Approve pending job
PATCH /api/admin/jobs/{id}/decline # Decline job with reason
```

**Job Status Management:**
- `pending` - Awaiting admin approval
- `active` - Approved and live on platform
- `declined` - Rejected by admin (with reason)
- `closed` - Job no longer accepting applications

### Admin Application Oversight
```
GET /api/admin/applications        # Monitor all applications
GET /api/admin/analytics          # Platform analytics and reports
```

## 🎯 Response Format

All API responses follow a consistent structure:

**Success Response:**
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data here
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    // Validation errors (if applicable)
  }
}
```

**Paginated Response:**
```json
{
  "success": true,
  "data": {
    "jobs": [...],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 67,
      "from": 1,
      "to": 15
    }
  }
}
```

## 🔐 Authentication

### Bearer Token Authentication
Include the token in the Authorization header:
```
Authorization: Bearer {your-token-here}
```

### Role-Based Access Control
- **Employee**: Can apply to jobs, view own applications
- **Employer**: Can post jobs, manage applications for their jobs
- **Admin**: Can manage all platform content and users

## 📊 New Frontend Features

### Enhanced Job Listings Page
- **Real API Integration**: Fetches live data from backend
- **Advanced Filtering**: Location, job type, category, experience level
- **Pagination Controls**: Navigate through job pages
- **Search Functionality**: Search across job titles, descriptions, companies
- **Application Modal**: Seamless job application process
- **Responsive Design**: Works on all device sizes

### Professional Application Flow
- **Drag & Drop Resume Upload**: Support for PDF, DOC, DOCX files
- **Cover Letter Editor**: Rich text editor for personalized applications
- **File Validation**: Size and type checking for uploads
- **Application Tips**: Guided help for better applications
- **Toast Notifications**: Professional feedback instead of alerts

### Admin Management Interface
- **Job Review System**: Approve/decline pending job postings
- **Application Oversight**: Monitor all job applications
- **User Management**: Handle user accounts and verifications
- **Interactive Dashboard**: Navigate to specific management pages
- **Real-time Statistics**: Accurate counts and metrics

## 🛠️ Technical Improvements

### Client-Side Filtering
- Unified API endpoints reduce server requests
- Faster filtering and search experience
- Cached data for better performance
- Fallback mechanisms for reliability

### Enhanced Error Handling
- Graceful fallback to mock data in development
- Informative error messages with suggested actions
- Network error recovery mechanisms
- User-friendly error displays

### UI/UX Enhancements
- PrimeReact Toast notifications for all feedback
- Smooth animations and transitions
- Consistent design language across all pages
- Loading states and progress indicators
- Hover effects and interactive elements

## 🧪 Testing Endpoints

### Health Check Test
```bash
curl -X GET "http://localhost:8000/api/health"
```

### Get Jobs with Filters
```bash
curl -X GET "http://localhost:8000/api/jobs?job_type=full-time&location=Riyadh&page=1&per_page=10"
```

### Submit Job Application (with auth)
```bash
curl -X POST "http://localhost:8000/api/applications" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "job_listing_id": 1,
    "cover_letter": "I am very interested in this position..."
  }'
```

### Admin Approve Job (with auth)
```bash
curl -X PATCH "http://localhost:8000/api/admin/jobs/1/approve" \
  -H "Authorization: Bearer {admin-token}"
```

## 🚀 Getting Started

1. **Start Backend:**
   ```bash
   cd suud-backend
   php artisan serve
   ```

2. **Start Frontend:**
   ```bash
   cd suud-frontend
   npm run dev
   ```

3. **Access Applications:**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000/api
   - Swagger Docs: http://localhost:8000/docs
   - Admin Dashboard: http://localhost:3000/admin

4. **Test Features:**
   - Browse jobs at `/jobs`
   - Apply to jobs (requires employee account)
   - Manage jobs (requires employer account)
   - Review applications (requires admin account)

## 📈 Performance Optimizations

- **Caching Strategy**: Jobs data cached for 5 minutes
- **Pagination**: Efficient data loading with configurable page sizes
- **Client-Side Filtering**: Reduced API calls through smart caching
- **Lazy Loading**: Load job details only when needed
- **Optimized Queries**: Efficient database queries with proper indexing

---

**🔗 Quick Access Links:**
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api
- **Swagger UI**: http://localhost:8000/docs
- **Admin Panel**: http://localhost:3000/admin
- **Job Listings**: http://localhost:3000/jobs

This documentation reflects all the enhancements made to the SU'UD platform, including the comprehensive job management system, improved admin interface, and enhanced user experience features.