<?php
/**
 * API Documentation and Router
 * Entry point for all API requests
 */

header('Content-Type: application/json');
require_once './cors.php';

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Simple router
if (strpos($request_uri, '/api/auth') !== false) {
    require 'auth.php';
} elseif (strpos($request_uri, '/api/users') !== false) {
    require 'users.php';
} elseif (strpos($request_uri, '/api/courses') !== false) {
    require 'courses.php';
} elseif (strpos($request_uri, '/api/analytics') !== false) {
    require 'analytics.php';
} elseif (strpos($request_uri, '/api/registrations') !== false) {
    require 'registrations.php';
} elseif (strpos($request_uri, '/api/docs') !== false || strpos($request_uri, '/api') === strlen($request_uri) - 4) {
    // API Documentation
    http_response_code(200);
    ?>
{
  "name": "Smart E-Learning Platform API",
  "version": "1.0.0",
  "description": "RESTful API for E-Learning Platform",
  "base_url": "http://localhost/New Elp/api",
  "endpoints": {
    "auth": {
      "login": {
        "method": "POST",
        "url": "/api/auth.php?action=login",
        "description": "Authenticate user and create session",
        "body": {
          "email": "user@example.com",
          "password": "password",
          "role": "student"
        }
      },
      "register": {
        "method": "POST",
        "url": "/api/auth.php?action=register",
        "description": "Create new user account",
        "body": {
          "name": "John Doe",
          "email": "john@example.com",
          "password": "password123",
          "role": "student"
        }
      },
      "logout": {
        "method": "POST",
        "url": "/api/auth.php?action=logout",
        "description": "End user session"
      },
      "check_session": {
        "method": "GET",
        "url": "/api/auth.php?action=check-session",
        "description": "Verify current session status"
      }
    },
    "users": {
      "list": {
        "method": "GET",
        "url": "/api/users.php?action=list",
        "description": "Get all users (admin only)",
        "requires_auth": true,
        "requires_role": ["admin"]
      },
      "get": {
        "method": "GET",
        "url": "/api/users.php?action=get&id=1",
        "description": "Get user by ID",
        "requires_auth": true
      },
      "by_role": {
        "method": "GET",
        "url": "/api/users.php?action=by-role&role=student",
        "description": "Get users by role"
      },
      "create": {
        "method": "POST",
        "url": "/api/users.php?action=create",
        "description": "Create new user (admin only)",
        "requires_auth": true,
        "requires_role": ["admin"],
        "body": {
          "name": "Jane Doe",
          "email": "jane@example.com",
          "password": "password123",
          "role": "instructor"
        }
      },
      "update": {
        "method": "PUT",
        "url": "/api/users.php?action=update&id=1",
        "description": "Update user profile",
        "requires_auth": true,
        "body": {
          "name": "Jane Smith",
          "phone": "123-456-7890",
          "address": "123 Main St"
        }
      },
      "delete": {
        "method": "DELETE",
        "url": "/api/users.php?action=delete&id=1",
        "description": "Delete user (admin only)",
        "requires_auth": true,
        "requires_role": ["admin"]
      }
    },
    "courses": {
      "list": {
        "method": "GET",
        "url": "/api/courses.php?action=list",
        "description": "Get all courses"
      },
      "get": {
        "method": "GET",
        "url": "/api/courses.php?action=get&id=1",
        "description": "Get course details"
      },
      "by_instructor": {
        "method": "GET",
        "url": "/api/courses.php?action=by-instructor&id=2",
        "description": "Get courses by instructor"
      },
      "enrolled": {
        "method": "GET",
        "url": "/api/courses.php?action=enrolled&id=1",
        "description": "Get enrolled courses for user",
        "requires_auth": true
      },
      "by_category": {
        "method": "GET",
        "url": "/api/courses.php?action=by-category&category=IT",
        "description": "Get courses by category"
      },
      "pending_submissions": {
        "method": "GET",
        "url": "/api/courses.php?action=pending-submissions",
        "description": "Get pending course submissions",
        "requires_auth": true,
        "requires_role": ["course_team", "admin"]
      },
      "create": {
        "method": "POST",
        "url": "/api/courses.php?action=create",
        "description": "Create new course",
        "requires_auth": true,
        "requires_role": ["instructor", "admin"],
        "body": {
          "title": "Advanced Python",
          "description": "Learn advanced Python concepts",
          "category": "Programming"
        }
      },
      "enroll": {
        "method": "POST",
        "url": "/api/courses.php?action=enroll",
        "description": "Enroll in course",
        "requires_auth": true,
        "body": {
          "course_id": 1
        }
      },
      "submit_for_approval": {
        "method": "POST",
        "url": "/api/courses.php?action=submit-for-approval",
        "description": "Submit course for approval",
        "requires_auth": true,
        "requires_role": ["instructor", "admin"],
        "body": {
          "course_id": 1
        }
      },
      "approve_submission": {
        "method": "POST",
        "url": "/api/courses.php?action=approve-submission",
        "description": "Approve course submission",
        "requires_auth": true,
        "requires_role": ["course_team", "admin"],
        "body": {
          "submission_id": 1,
          "notes": "Approved"
        }
      },
      "reject_submission": {
        "method": "POST",
        "url": "/api/courses.php?action=reject-submission",
        "description": "Reject course submission",
        "requires_auth": true,
        "requires_role": ["course_team", "admin"],
        "body": {
          "submission_id": 1,
          "notes": "Needs revision"
        }
      },
      "update": {
        "method": "PUT",
        "url": "/api/courses.php?action=update&id=1",
        "description": "Update course",
        "requires_auth": true,
        "body": {
          "title": "Updated Course Title",
          "description": "Updated description",
          "status": "Ongoing"
        }
      },
      "update_progress": {
        "method": "PUT",
        "url": "/api/courses.php?action=update-progress&id=1",
        "description": "Update course progress",
        "requires_auth": true,
        "body": {
          "progress": 75
        }
      }
    },
    "analytics": {
      "platform_stats": {
        "method": "GET",
        "url": "/api/analytics.php?action=platform-stats",
        "description": "Get overall platform statistics",
        "requires_auth": true,
        "requires_role": ["admin", "data_analyst"]
      },
      "active_learners": {
        "method": "GET",
        "url": "/api/analytics.php?action=active-learners&days=30",
        "description": "Get active learners count",
        "requires_auth": true,
        "requires_role": ["admin", "data_analyst"]
      },
      "course_popularity": {
        "method": "GET",
        "url": "/api/analytics.php?action=course-popularity",
        "description": "Get most popular courses",
        "requires_auth": true,
        "requires_role": ["admin", "data_analyst"]
      },
      "instructor_performance": {
        "method": "GET",
        "url": "/api/analytics.php?action=instructor-performance",
        "description": "Get instructor performance metrics",
        "requires_auth": true,
        "requires_role": ["admin", "dept_head", "data_analyst"]
      },
      "enrollment_trends": {
        "method": "GET",
        "url": "/api/analytics.php?action=enrollment-trends",
        "description": "Get enrollment trends over time",
        "requires_auth": true,
        "requires_role": ["admin", "data_analyst"]
      },
      "user_engagement": {
        "method": "GET",
        "url": "/api/analytics.php?action=user-engagement",
        "description": "Get user engagement metrics",
        "requires_auth": true,
        "requires_role": ["admin", "data_analyst"]
      },
      "log_event": {
        "method": "POST",
        "url": "/api/analytics.php?action=log-event",
        "description": "Log user event",
        "requires_auth": true,
        "body": {
          "event_type": "course_viewed",
          "course_id": 1,
          "event_data": {}
        }
      }
    },
    "registrations": {
      "pending": {
        "method": "GET",
        "url": "/api/registrations.php?action=pending",
        "description": "Get pending registrations",
        "requires_auth": true,
        "requires_role": ["office_manager", "admin"]
      },
      "all": {
        "method": "GET",
        "url": "/api/registrations.php?action=all",
        "description": "Get all registrations",
        "requires_auth": true,
        "requires_role": ["office_manager", "admin"]
      },
      "get": {
        "method": "GET",
        "url": "/api/registrations.php?action=get&id=1",
        "description": "Get registration details",
        "requires_auth": true,
        "requires_role": ["office_manager", "admin"]
      },
      "payment_summary": {
        "method": "GET",
        "url": "/api/registrations.php?action=payment-summary",
        "description": "Get payment summary",
        "requires_auth": true,
        "requires_role": ["office_manager", "admin"]
      },
      "create": {
        "method": "POST",
        "url": "/api/registrations.php?action=create-registration",
        "description": "Create registration",
        "requires_auth": true,
        "body": {
          "course_id": 1,
          "amount": 99.99
        }
      },
      "approve": {
        "method": "POST",
        "url": "/api/registrations.php?action=approve-registration",
        "description": "Approve registration",
        "requires_auth": true,
        "requires_role": ["office_manager", "admin"],
        "body": {
          "registration_id": 1
        }
      },
      "record_payment": {
        "method": "POST",
        "url": "/api/registrations.php?action=record-payment",
        "description": "Record payment",
        "requires_auth": true,
        "requires_role": ["office_manager", "admin"],
        "body": {
          "registration_id": 1
        }
      },
      "bulk_process": {
        "method": "POST",
        "url": "/api/registrations.php?action=bulk-process",
        "description": "Process multiple registrations",
        "requires_auth": true,
        "requires_role": ["office_manager", "admin"],
        "body": {
          "registration_ids": [1, 2, 3]
        }
      }
    }
  },
  "demo_credentials": {
    "student": {
      "email": "student@demo",
      "password": "demo",
      "role": "student"
    },
    "instructor": {
      "email": "instructor@demo",
      "password": "demo",
      "role": "instructor"
    },
    "admin": {
      "email": "admin@demo",
      "password": "demo",
      "role": "admin"
    }
  }
}
    <?php
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
}

?>
