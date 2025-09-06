# SU'UD Frontend Integration Guide

## 🚀 API Documentation

Your **Swagger API Documentation** is available at:
```
http://localhost:8000/api-docs
```

This interactive documentation shows all available endpoints, request/response formats, and allows you to test the API directly from the browser.

## 📋 Required Frontend Changes

### 1. Add Company Name Field for Employer Registration

In your employer registration form, you **MUST** include the `company_name` field:

```javascript
// Employee Registration Form Fields
{
  name: "John Doe",
  email: "john@example.com", 
  password: "password123",
  password_confirmation: "password123",
  role: "employee",
  // Optional fields:
  specialization: "Software Engineering",
  university: "Cairo University",
  phone: "+201234567890",
  location: "Cairo, Egypt"
}

// Employer Registration Form Fields (ADD company_name!)
{
  name: "Jane Smith",
  email: "jane@company.com",
  password: "password123", 
  password_confirmation: "password123",
  role: "employer",
  company_name: "Tech Solutions Inc.", // ← REQUIRED for employers!
  // Optional fields:
  phone: "+201234567890",
  location: "Cairo, Egypt"
}
```

### 2. Frontend Form Validation

Add client-side validation to show/hide the company name field based on role selection:

```javascript
// Example React component
const RegistrationForm = () => {
  const [role, setRole] = useState('');
  const [formData, setFormData] = useState({});

  return (
    <form>
      <input name="name" placeholder="Full Name" required />
      <input name="email" type="email" placeholder="Email" required />
      <input name="password" type="password" placeholder="Password" required />
      <input name="password_confirmation" type="password" placeholder="Confirm Password" required />
      
      <select name="role" onChange={(e) => setRole(e.target.value)} required>
        <option value="">Select Role</option>
        <option value="employee">Employee</option>
        <option value="employer">Employer</option>
      </select>

      {/* Show company name field only for employers */}
      {role === 'employer' && (
        <input 
          name="company_name" 
          placeholder="Company Name" 
          required 
        />
      )}

      {/* Optional fields for employees */}
      {role === 'employee' && (
        <>
          <input name="specialization" placeholder="Specialization (optional)" />
          <input name="university" placeholder="University (optional)" />
        </>
      )}

      <input name="phone" placeholder="Phone (optional)" />
      <input name="location" placeholder="Location (optional)" />
      
      <button type="submit">Register</button>
    </form>
  );
};
```

### 3. API Error Handling

The API now returns consistent error messages. Handle them like this:

```javascript
const handleRegistration = async (formData) => {
  try {
    const response = await fetch('http://localhost:8000/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    });

    const data = await response.json();

    if (data.success) {
      // Registration successful
      localStorage.setItem('auth_token', data.data.token);
      // Redirect to dashboard
    } else {
      // Handle validation errors
      if (data.errors) {
        Object.keys(data.errors).forEach(field => {
          const errors = data.errors[field];
          console.log(`${field}: ${errors.join(', ')}`);
          // Show errors in your form UI
        });
      }
    }
  } catch (error) {
    console.error('Registration failed:', error);
  }
};
```

## 🔐 Authentication Flow

1. **Registration**: POST `/api/auth/register`
2. **Login**: POST `/api/auth/login`  
3. **Get User Info**: GET `/api/auth/me` (requires Bearer token)
4. **Logout**: POST `/api/auth/logout` (requires Bearer token)

## 📊 Available API Endpoints

- **Jobs**: GET `/api/jobs` - Browse job listings
- **Job Details**: GET `/api/jobs/{id}` - Get specific job
- **Job Filters**: GET `/api/jobs/filters` - Get filter options
- **Job Stats**: GET `/api/jobs/stats` - Get platform statistics

## 🛠️ Missing Pages You Need to Complete

Based on typical job portal requirements, you likely need:

### 1. User Dashboard Pages
- Employee Dashboard
- Employer Dashboard
- Profile Management

### 2. Job Management Pages (Employer)
- Create Job Listing
- Manage Job Listings 
- View Applications

### 3. Application Pages (Employee)
- Browse Jobs
- Job Details
- Apply to Jobs
- My Applications

### 4. Additional Features
- User Profile Settings
- Company Profile (for employers)
- Application Management
- Notifications

## 🎯 Next Steps

1. **✅ Done**: API is working with proper error handling
2. **✅ Done**: Swagger documentation is available
3. **⏳ Todo**: Add company_name field to employer registration form
4. **⏳ Todo**: Implement proper error handling in frontend
5. **⏳ Todo**: Complete missing dashboard pages
6. **⏳ Todo**: Add job listing and application features

## 📱 Testing Your API

Visit: `http://localhost:8000/api-docs`

You can test all endpoints directly from the Swagger interface. Use the "Authorize" button to add your Bearer token for protected endpoints.

## 🆘 Common Issues

### Issue: "Company name is required for employers"
**Solution**: Make sure your frontend sends `company_name` field when `role: "employer"`

### Issue: "Validation errors" 
**Solution**: Check the `errors` object in the response for specific field validation messages

### Issue: "Unauthenticated"
**Solution**: Include `Authorization: Bearer YOUR_TOKEN` header in protected requests

---

**Your SU'UD API is now ready for frontend integration! 🎉**
