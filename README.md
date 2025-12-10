# E-Learning Platform Backend Documentation

## Overview
This backend provides a comprehensive RESTful API for the Smart E-Learning Platform, supporting multiple user roles (Student, Instructor, Admin, Course Team, Department Head, Data Analyst, Office Manager).

## Database Setup

### 1. Create Database
Run `database.sql` in your MySQL client:
```sql
mysql -u root -p < database.sql
```

Or manually import through phpMyAdmin.

### 2. Database Structure
The system includes the following tables:
- **users** - User accounts with role-based access
- **courses** - Course information and details
- **enrollments** - Student enrollments and progress tracking
- **certificates** - Course completion certificates
- **submissions** - Course approval submissions
- **analytics** - Event logging and analytics data
- **registrations** - Student registrations and payments
- **department_faculty** - Department faculty relationships

## Configuration

### Database Connection (config.php)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'e-learning platform');
```

Update credentials if different from defaults.

## API Endpoints

### Authentication (/api/auth.php)

#### Login
- **POST** `/api/auth.php?action=login`
- **Body**: `{ "email": "student@demo", "password": "demo", "role": "student" }`
- **Response**: `{ "success": true, "data": { "user_id": 1, "email": "...", "role": "...", "name": "..." } }`

#### Register
- **POST** `/api/auth.php?action=register`
- **Body**: `{ "name": "John Doe", "email": "john@example.com", "password": "pass123", "role": "student" }`

#### Check Session
- **GET** `/api/auth.php?action=check-session`
- **Returns**: Current session info if logged in

#### Logout
- **POST** `/api/auth.php?action=logout`

### Users (/api/users.php)

#### List All Users
- **GET** `/api/users.php?action=list` (Admin only)

#### Get User by ID
- **GET** `/api/users.php?action=get&id=1`

#### Get Users by Role
- **GET** `/api/users.php?action=by-role&role=student`

#### Create User
- **POST** `/api/users.php?action=create` (Admin only)
- **Body**: `{ "name": "...", "email": "...", "password": "...", "role": "..." }`

#### Update User
- **PUT** `/api/users.php?action=update&id=1`
- **Body**: `{ "name": "...", "phone": "...", "address": "..." }`

#### Delete User
- **DELETE** `/api/users.php?action=delete&id=1` (Admin only)

### Courses (/api/courses.php)

#### List All Courses
- **GET** `/api/courses.php?action=list`

#### Get Course Details
- **GET** `/api/courses.php?action=get&id=1`

#### Get Instructor's Courses
- **GET** `/api/courses.php?action=by-instructor&id=2`

#### Get User's Enrolled Courses
- **GET** `/api/courses.php?action=enrolled&id=1`

#### Get Courses by Category
- **GET** `/api/courses.php?action=by-category&category=IT`

#### Get Pending Submissions
- **GET** `/api/courses.php?action=pending-submissions` (Course Team/Admin)

#### Create Course
- **POST** `/api/courses.php?action=create` (Instructor/Admin)
- **Body**: `{ "title": "...", "description": "...", "category": "..." }`

#### Enroll in Course
- **POST** `/api/courses.php?action=enroll`
- **Body**: `{ "course_id": 1 }`

#### Submit Course for Approval
- **POST** `/api/courses.php?action=submit-for-approval` (Instructor/Admin)
- **Body**: `{ "course_id": 1 }`

#### Approve Course Submission
- **POST** `/api/courses.php?action=approve-submission` (Course Team/Admin)
- **Body**: `{ "submission_id": 1, "notes": "Approved" }`

#### Reject Course Submission
- **POST** `/api/courses.php?action=reject-submission` (Course Team/Admin)
- **Body**: `{ "submission_id": 1, "notes": "Needs revision" }`

#### Update Course
- **PUT** `/api/courses.php?action=update&id=1` (Instructor/Admin)
- **Body**: `{ "title": "...", "description": "...", "status": "..." }`

#### Update Course Progress
- **PUT** `/api/courses.php?action=update-progress&id=1`
- **Body**: `{ "progress": 75 }`

### Analytics (/api/analytics.php)

#### Get Platform Statistics
- **GET** `/api/analytics.php?action=platform-stats` (Admin/Analyst)

#### Get Active Learners
- **GET** `/api/analytics.php?action=active-learners&days=30` (Admin/Analyst)

#### Get Course Popularity
- **GET** `/api/analytics.php?action=course-popularity` (Admin/Analyst)

#### Get Instructor Performance
- **GET** `/api/analytics.php?action=instructor-performance` (Admin/Dept Head/Analyst)

#### Get Enrollment Trends
- **GET** `/api/analytics.php?action=enrollment-trends` (Admin/Analyst)

#### Get User Engagement
- **GET** `/api/analytics.php?action=user-engagement` (Admin/Analyst)

#### Log Event
- **POST** `/api/analytics.php?action=log-event`
- **Body**: `{ "event_type": "course_viewed", "course_id": 1, "event_data": {...} }`

### Registrations (/api/registrations.php)

#### Get Pending Registrations
- **GET** `/api/registrations.php?action=pending` (Office Manager/Admin)

#### Get All Registrations
- **GET** `/api/registrations.php?action=all` (Office Manager/Admin)

#### Get Registration Details
- **GET** `/api/registrations.php?action=get&id=1` (Office Manager/Admin)

#### Get Payment Summary
- **GET** `/api/registrations.php?action=payment-summary` (Office Manager/Admin)

#### Create Registration
- **POST** `/api/registrations.php?action=create-registration`
- **Body**: `{ "course_id": 1, "amount": 99.99 }`

#### Approve Registration
- **POST** `/api/registrations.php?action=approve-registration` (Office Manager/Admin)
- **Body**: `{ "registration_id": 1 }`

#### Record Payment
- **POST** `/api/registrations.php?action=record-payment` (Office Manager/Admin)
- **Body**: `{ "registration_id": 1 }`

#### Bulk Process Registrations
- **POST** `/api/registrations.php?action=bulk-process` (Office Manager/Admin)
- **Body**: `{ "registration_ids": [1, 2, 3] }`

## Demo Credentials

Login with these credentials for testing:

| Role | Email | Password |
|------|-------|----------|
| Student | student@demo | demo |
| Instructor | instructor@demo | demo |
| Admin | admin@demo | demo |
| Course Team | course@demo | demo |
| Dept Head | head@demo | demo |
| Data Analyst | analyst@demo | demo |
| Office Manager | office@demo | demo |

Or use any `@demo` email with password `demo`.

## Installation Steps

1. **Extract files** to your web server directory (e.g., `htdocs/New Elp/`)
2. **Import database** using `database.sql`
3. **Update config.php** if needed with your database credentials
4. **Access the platform**:
   - Frontend: `http://localhost/New Elp/index.php`
   - API Docs: `http://localhost/New Elp/api/docs`

## File Structure

```
New Elp/
├── index.php                 (Frontend)
├── config.php               (Database configuration)
├── database.sql             (Database schema)
├── README.md                (This file)
└── api/
    ├── index.php            (API router & docs)
    ├── helpers.php          (Utility functions)
    ├── auth.php             (Authentication endpoints)
    ├── users.php            (User management)
    ├── courses.php          (Course management)
    ├── analytics.php        (Analytics endpoints)
    └── registrations.php    (Registration management)
```

## Security Notes

- Passwords should be hashed using `password_hash()` with BCRYPT
- Always validate and sanitize user input
- Use prepared statements for SQL queries (recommended for production)
- Session-based authentication with PHP sessions
- Role-based access control on all protected endpoints

## Development Tips

1. **Test API endpoints** using Postman or similar tools
2. **Check browser console** for frontend errors
3. **Enable error logging** for debugging
4. **Monitor database** for optimization opportunities
5. **Keep sessions secure** - use HTTPS in production

## Future Enhancements

- JWT token authentication
- PDF certificate generation
- Email notifications
- Video content streaming
- Advanced search and filtering
- Mobile app API version
- Third-party integrations

---

For API documentation, visit: `http://localhost/New Elp/api/`
