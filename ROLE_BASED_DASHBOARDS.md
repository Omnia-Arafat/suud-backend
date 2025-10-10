# SU'UD Platform - Role-Based Dashboard System

## Overview

The SU'UD platform now features three distinct dashboards, each tailored to specific user roles with their own functionality, pages, and permissions:

1. **Admin Dashboard** - Platform management and oversight
2. **Employee Dashboard** - Job searching and application management  
3. **Employer Dashboard** - Job posting and candidate management

## Backend Architecture

### Middleware Protection

Each dashboard is protected by role-specific middleware:

- `AdminMiddleware` - Ensures only users with `role = 'admin'` can access admin routes
- `EmployeeMiddleware` - Ensures only users with `role = 'employee'` can access employee routes  
- `EmployerMiddleware` - Ensures only users with `role = 'employer'` can access employer routes

### Controllers

Each role has its dedicated controller with specific business logic:

#### AdminDashboardController
**Responsibilities:**
- Platform-wide statistics and analytics
- User management (all users)
- Job oversight (all jobs)
- Application monitoring (all applications)
- Contact form management
- System analytics

**Key Methods:**
- `dashboard()` - Overview statistics
- `users()` - User management with filters
- `updateUserStatus()` - Activate/deactivate users
- `jobs()` - All job postings management
- `applications()` - All applications oversight
- `contacts()` - Contact form submissions
- `analytics()` - Platform analytics

#### EmployeeDashboardController
**Responsibilities:**
- Personal job application management
- Job searching and filtering
- Profile management
- Application statistics

**Key Methods:**
- `dashboard()` - Personal statistics and recommendations
- `myApplications()` - User's job applications
- `availableJobs()` - Job search with smart recommendations
- `updateProfile()` - Profile and CV management
- `changePassword()` - Security management
- `applicationStats()` - Personal application analytics

#### EmployerDashboardController
**Responsibilities:**
- Company job posting management
- Application review and hiring
- Company profile management
- Hiring analytics

**Key Methods:**
- `dashboard()` - Company hiring overview
- `myJobs()` - Company's job postings
- `jobApplications()` - Applications to company jobs
- `updateApplicationStatus()` - Accept/reject applications
- `updateCompany()` - Company profile management
- `analytics()` - Hiring and job performance analytics

## API Endpoints

### Admin Dashboard (`/api/admin/*`)
```
GET    /api/admin/dashboard           - Admin overview
GET    /api/admin/users               - User management
PATCH  /api/admin/users/{id}/status   - Update user status
GET    /api/admin/jobs                - All jobs management
GET    /api/admin/applications        - All applications
GET    /api/admin/contacts            - Contact submissions
GET    /api/admin/analytics           - Platform analytics
```

### Employee Dashboard (`/api/employee/*`)
```
GET    /api/employee/dashboard        - Employee overview
GET    /api/employee/applications     - My applications
GET    /api/employee/jobs             - Available jobs
GET    /api/employee/profile          - My profile
PATCH  /api/employee/profile          - Update profile
PATCH  /api/employee/password         - Change password
GET    /api/employee/stats            - Application statistics
```

### Employer Dashboard (`/api/employer/*`)
```
GET    /api/employer/dashboard              - Employer overview
GET    /api/employer/jobs                   - My job postings
GET    /api/employer/applications           - Job applications
PATCH  /api/employer/applications/{id}/status - Update application status
GET    /api/employer/company                - Company profile
PATCH  /api/employer/company               - Update company
PATCH  /api/employer/password              - Change password
GET    /api/employer/analytics             - Hiring analytics
```

## Dashboard Features by Role

### 🛡️ Admin Dashboard Features

**Main Dashboard:**
- Total users, jobs, applications, companies
- User role breakdown (admin/employee/employer)
- Job status overview (active/draft/closed)
- Application status tracking
- Recent activity feeds
- Monthly growth charts

**User Management:**
- Search and filter all users
- Activate/deactivate user accounts
- View user profiles and details
- Role-based user statistics
- User activity monitoring

**Job Management:**
- View all job postings across platform
- Filter by status, company, location
- Job posting analytics and trends
- Company job performance tracking

**Application Oversight:**
- Monitor all applications on platform
- Filter by status, job, user
- Application success rate tracking
- Hiring funnel analytics

**Analytics & Reporting:**
- User growth trends (12-month view)
- Job posting trends
- Application success rates
- Top job locations and companies
- Platform usage statistics

### 👤 Employee Dashboard Features

**Main Dashboard:**
- Personal application statistics
- Available jobs counter
- Profile completion percentage
- Recommended jobs based on specialization
- Recent applications status
- Latest job opportunities

**My Applications:**
- View all submitted applications
- Filter by status (pending/reviewed/accepted/rejected)
- Search applications by job/company
- Application timeline and status updates
- Application success tracking

**Job Search:**
- Smart job recommendations based on profile
- Advanced filtering (location, type, etc.)
- Search functionality
- Jobs sorted by relevance to specialization
- Exclude already applied jobs

**Profile Management:**
- Personal information updates
- Avatar and CV upload
- Profile completion tracking
- Skills and specialization management
- University and education details

**Statistics:**
- Monthly application trends (6 months)
- Application status breakdown
- Success rate tracking
- Profile views (future feature)

### 🏢 Employer Dashboard Features

**Main Dashboard:**
- Company job posting statistics
- Application metrics for company jobs
- Company profile completion
- Recent applications to jobs
- Top performing jobs
- Hiring pipeline overview

**Job Management:**
- View all company job postings
- Filter by status (active/draft/closed)
- Job performance metrics (application count)
- Create, edit, and manage job postings

**Application Review:**
- Applications to company jobs
- Filter by status, job, candidate
- Search candidates by skills/university
- Update application status with notes
- Candidate profile viewing

**Company Profile:**
- Company information management
- Logo upload and branding
- Industry and company details
- Employee count and founding year
- Company verification status

**Hiring Analytics:**
- Monthly application trends
- Application status breakdown
- Job performance comparison
- Top performing job postings
- Hiring funnel metrics

## Security & Permissions

### Role Verification
Each route is protected by:
1. **Authentication** - Must be logged in (`auth:sanctum`)
2. **Role Authorization** - Must have correct role (middleware)
3. **Data Isolation** - Users can only access their own data

### Data Access Rules

**Admin Access:**
- ✅ All users, jobs, applications, companies
- ✅ Platform-wide statistics and analytics
- ✅ System configuration and settings
- ✅ Contact form submissions

**Employee Access:**
- ✅ Own applications and profile
- ✅ Available job listings
- ✅ Own statistics and analytics
- ❌ Other users' data
- ❌ Company internal data

**Employer Access:**
- ✅ Own company jobs and applications
- ✅ Company profile and settings
- ✅ Applicants to company jobs
- ✅ Company hiring analytics
- ❌ Other companies' data
- ❌ Platform-wide statistics

## Frontend Implementation Guide

### Dashboard Routing
Each role should have protected routes:

```javascript
// Admin routes
/admin/dashboard
/admin/users
/admin/jobs
/admin/applications
/admin/analytics

// Employee routes  
/employee/dashboard
/employee/applications
/employee/jobs
/employee/profile

// Employer routes
/employer/dashboard
/employer/jobs
/employer/applications
/employer/company
```

### Navigation Structure

**Admin Navigation:**
- Dashboard, Users, Jobs, Applications, Analytics, System Settings

**Employee Navigation:**
- Dashboard, My Applications, Job Search, Profile, Statistics

**Employer Navigation:**
- Dashboard, My Jobs, Applications, Company Profile, Analytics

### Role-Based UI Components
Each dashboard should have:
- Role-appropriate color scheme
- Relevant statistics cards
- Role-specific navigation menus
- Appropriate action buttons and permissions
- Tailored data visualization

## Testing the System

### Test the Admin Dashboard:
1. Login with admin credentials (`admin@suud.com` / `admin123456`)
2. Access: `/api/admin/dashboard`
3. Verify admin-only data and functionality

### Test Employee Dashboard:
1. Register/login as employee
2. Access: `/api/employee/dashboard` 
3. Verify employee-specific features

### Test Employer Dashboard:
1. Register/login as employer
2. Ensure company profile exists
3. Access: `/api/employer/dashboard`
4. Verify employer-specific features

## Next Steps

1. **Frontend Implementation**: Build React components for each dashboard
2. **UI/UX Design**: Create distinct visual themes for each role
3. **Data Visualization**: Implement charts and analytics displays
4. **Real-time Updates**: Add WebSocket support for live updates
5. **Email Notifications**: Implement role-specific email alerts
6. **Mobile Responsiveness**: Ensure dashboards work on all devices

---

The role-based dashboard system ensures that each user type has access to exactly the tools and information they need, with proper security and data isolation throughout the platform.
