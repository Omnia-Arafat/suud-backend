# SU'UD Platform - Test Accounts

## 🧪 Complete Test Account Collection

All test accounts have been successfully created and are ready for testing the SU'UD job portal platform. Each account represents a different role with specific permissions and access levels.

---

## 👨‍💼 ADMIN ACCOUNTS

### Primary Admin Account
- **Email:** `admin@suud.com`
- **Password:** `admin123456`
- **Role:** `admin`
- **Name:** SU'UD Admin
- **Location:** Riyadh, Saudi Arabia
- **Phone:** +966501234567

**Admin Capabilities:**
- ✅ User management (view, edit, delete all users)
- ✅ Company registration approval/rejection
- ✅ Job posting approval/rejection
- ✅ View all applications and statistics
- ✅ Platform oversight and system administration
- ✅ Contact form submissions management
- ✅ Generate reports and analytics
- ✅ Mail configuration and system settings

---

## 👨‍💻 EMPLOYEE ACCOUNTS

### Employee Account #1
- **Email:** `employee@suud.com`
- **Password:** `employee123`
- **Role:** `employee`
- **Name:** Ahmed Al-Rashid
- **Specialization:** Software Development
- **University:** King Saud University
- **Location:** Riyadh, Saudi Arabia
- **Phone:** +966502345678
- **Profile Summary:** Passionate software developer with 2 years of experience in web development. Skilled in React, Laravel, and modern web technologies. Looking for opportunities to grow and contribute to innovative projects.

### Employee Account #2
- **Email:** `fatima@suud.com`
- **Password:** `fatima123`
- **Role:** `employee`
- **Name:** Fatima Al-Zahra
- **Specialization:** Data Science
- **University:** King Abdulaziz University
- **Location:** Jeddah, Saudi Arabia
- **Phone:** +966504567890
- **Profile Summary:** Data science enthusiast with expertise in machine learning and data analysis. Graduate with honors from KAU, seeking opportunities in AI and data-driven solutions.

**Employee Capabilities:**
- ✅ Browse and search available job listings
- ✅ Apply for job positions (quick apply and detailed applications)
- ✅ Track application statuses and progress
- ✅ Manage personal profile and upload CV
- ✅ View application statistics and success rates
- ✅ Withdraw applications when in pending status
- ✅ Receive job recommendations based on specialization

---

## 🏢 EMPLOYER ACCOUNTS

### Employer Account #1
- **Email:** `employer@suud.com`
- **Password:** `employer123`
- **Role:** `employer`
- **Name:** Sara Al-Mansouri
- **Location:** Jeddah, Saudi Arabia
- **Phone:** +966503456789
- **Profile Summary:** HR Manager at TechNova Solutions, responsible for talent acquisition and employee development.

**Associated Company:**
- **Company Name:** TechNova Solutions
- **Industry:** Information Technology
- **Website:** https://technova.sa
- **Location:** Jeddah, Saudi Arabia
- **Company Size:** 50-100 employees
- **Founded:** 2018
- **Description:** Leading technology company specializing in innovative software solutions for businesses across the Middle East. We focus on digital transformation and cutting-edge technology implementations.

### Employer Account #2
- **Email:** `mohammed@suud.com`
- **Password:** `mohammed123`
- **Role:** `employer`
- **Name:** Mohammed Al-Otaibi
- **Location:** Dammam, Saudi Arabia
- **Phone:** +966505678901
- **Profile Summary:** Senior Recruitment Specialist at Digital Horizon, focused on finding top talent for technology companies in the Kingdom.

**Associated Company:**
- **Company Name:** Digital Horizon
- **Industry:** Digital Services
- **Website:** https://digitalhorizon.sa
- **Location:** Dammam, Saudi Arabia
- **Company Size:** 20-50 employees
- **Founded:** 2020
- **Description:** A forward-thinking digital agency that helps businesses transform their operations through technology. We specialize in web development, mobile apps, and digital marketing.

**Employer Capabilities:**
- ✅ Post new job listings
- ✅ Manage existing job postings (edit, pause, activate, delete)
- ✅ Review and manage job applications
- ✅ Accept or reject candidate applications
- ✅ Browse and search candidate profiles
- ✅ View application analytics and statistics
- ✅ Manage company profile and information
- ✅ Track hiring pipeline and success rates

---

## 🔐 Login Instructions

### Using the Web Interface
1. Navigate to the login page
2. Select appropriate role (Admin/Employee/Employer)
3. Enter email and password from the accounts above
4. Access role-specific dashboard and features

### Using API Authentication

**Login Endpoint:** `POST /api/auth/login`

**Request Body:**
```json
{
    "email": "employee@suud.com",
    "password": "employee123"
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 6,
        "name": "Ahmed Al-Rashid",
        "email": "employee@suud.com",
        "role": "employee",
        "is_active": true,
        "specialization": "Software Development",
        "university": "King Saud University"
    },
    "token": "6|xyz123..."
}
```

---

## 🧪 Testing Scenarios

### Admin Testing
1. **User Management:** View all users, edit profiles, activate/deactivate accounts
2. **Approval Workflows:** Test company registration and job posting approvals
3. **Analytics:** Generate reports and view platform statistics
4. **System Administration:** Manage platform settings and configurations

### Employee Testing
1. **Job Search:** Browse jobs, apply filters, search by keywords
2. **Application Management:** Apply for jobs, track status, withdraw applications
3. **Profile Management:** Update personal information, upload CV and avatar
4. **Dashboard Features:** View recommendations, track statistics

### Employer Testing
1. **Job Management:** Create, edit, pause, and delete job postings
2. **Application Review:** Review candidates, accept/reject applications
3. **Company Profile:** Update company information and branding
4. **Candidate Search:** Browse employee profiles and contact candidates

---

## 🔄 Regenerating Test Data

To recreate or refresh test accounts, run:

```bash
php artisan db:seed --class=TestAccountsSeeder
```

**Note:** This command will create accounts if they don't exist or skip existing ones without modification.

---

## 🛡️ Security Notes

1. **Change Passwords:** These are test passwords - change them in production
2. **Account Status:** All accounts are set to `is_active: true`
3. **Verification:** Company accounts are pre-verified for testing
4. **Real Data:** Use realistic but fake data for testing purposes

---

## 📊 Database Statistics

After seeding, your database will contain:
- **1 Admin account** with full platform access
- **2 Employee accounts** with different specializations
- **2 Employer accounts** with associated company profiles
- **2 Company profiles** in different industries

---

## ✅ Account Verification

### Verify Accounts in Database
```bash
php artisan tinker
```

```php
// Check all users
User::all(['id', 'name', 'email', 'role', 'is_active']);

// Check companies
Company::with('user')->get(['id', 'company_name', 'user_id', 'industry']);

// Count by role
User::selectRaw('role, count(*) as count')->groupBy('role')->get();
```

---

## 🚀 Ready for Testing!

All accounts are now set up and ready for comprehensive testing of the SU'UD platform. Each role has been configured with appropriate permissions, realistic profiles, and associated data for thorough testing of all platform features.

**Happy Testing! 🎉**

---

**Created:** $(date)  
**Platform:** SU'UD Job Portal  
**Environment:** Development/Testing
