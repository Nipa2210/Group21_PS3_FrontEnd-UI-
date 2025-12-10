<?php
/**
 * Courses API
 * Handles course management, enrollment, and submissions
 */

header('Content-Type: application/json');
require_once './cors.php';

require_once '../config.php';
require_once './helpers.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($request_method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($request_method === 'GET') {
    if ($action === 'list') {
        $query = "SELECT c.*, u.name as instructor_name FROM courses c 
                  JOIN users u ON c.instructor_id = u.id 
                  ORDER BY c.created_at DESC";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $courses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }

        sendResponse(true, 'Courses retrieved', $courses);
    }

    if ($action === 'get' && $id > 0) {
        $query = "SELECT c.*, u.name as instructor_name FROM courses c 
                  JOIN users u ON c.instructor_id = u.id 
                  WHERE c.id = $id";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $course = mysqli_fetch_assoc($result);

        if (!$course) {
            sendResponse(false, 'Course not found', null, 404);
        }

        sendResponse(true, 'Course retrieved', $course);
    }

    if ($action === 'by-instructor' && $id > 0) {
        $query = "SELECT * FROM courses WHERE instructor_id = $id ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $courses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }

        sendResponse(true, 'Instructor courses retrieved', $courses);
    }

    if ($action === 'enrolled' && $id > 0) {
        $query = "SELECT c.*, e.progress, e.status FROM enrollments e 
                  JOIN courses c ON e.course_id = c.id 
                  WHERE e.user_id = $id 
                  ORDER BY e.enrolled_at DESC";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $courses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }

        sendResponse(true, 'Enrolled courses retrieved', $courses);
    }

    if ($action === 'by-category') {
        $category = isset($_GET['category']) ? trim($_GET['category']) : '';

        if (empty($category)) {
            sendResponse(false, 'Category required', null, 400);
        }

        $category = mysqli_real_escape_string($conn, $category);
        $query = "SELECT * FROM courses WHERE category = '$category' ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $courses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }

        sendResponse(true, 'Courses retrieved', $courses);
    }

    if ($action === 'pending-submissions') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['course_team', 'admin']);

        $query = "SELECT s.*, c.title as course_title, u.name as submitted_by_name 
                  FROM submissions s 
                  JOIN courses c ON s.course_id = c.id 
                  JOIN users u ON s.submitted_by = u.id 
                  WHERE s.status IN ('Pending', 'Under Review') 
                  ORDER BY s.submitted_at ASC";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $submissions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $submissions[] = $row;
        }

        sendResponse(true, 'Pending submissions retrieved', $submissions);
    }
}

if ($request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['instructor', 'admin']);

        if (!isset($input['title']) || !isset($input['category'])) {
            sendResponse(false, 'Missing required fields', null, 400);
        }

        $title = mysqli_real_escape_string($conn, trim($input['title']));
        $description = isset($input['description']) ? mysqli_real_escape_string($conn, trim($input['description'])) : '';
        $category = mysqli_real_escape_string($conn, trim($input['category']));
        $instructor_id = $current_user['role'] === 'admin' && isset($input['instructor_id']) ? (int)$input['instructor_id'] : $current_user['user_id'];

        $query = "INSERT INTO courses (title, description, instructor_id, category, status) 
                  VALUES ('$title', '$description', '$instructor_id', '$category', 'Draft')";

        if (mysqli_query($conn, $query)) {
            $course_id = mysqli_insert_id($conn);
            sendResponse(true, 'Course created', ['course_id' => $course_id]);
        } else {
            sendResponse(false, 'Creation failed: ' . mysqli_error($conn), null, 500);
        }
    }

    if ($action === 'enroll') {
        $current_user = getCurrentUser();
        apiLog('ENROLL: Current user: ' . json_encode($current_user));

        if (!isset($input['course_id'])) {
            sendResponse(false, 'Course ID required', null, 400);
        }

        $course_id = (int)$input['course_id'];
        $user_id = $current_user['user_id'];
        apiLog("ENROLL: Initial user_id={$user_id}, course_id={$course_id}");

        // If user_id is not numeric (demo fallback using an email), try to resolve it to a numeric id
        if (!is_numeric($user_id) && !empty($current_user['email'])) {
            apiLog("ENROLL: user_id is not numeric, attempting to resolve from email: " . $current_user['email']);
            $u = getUserByEmail($conn, $current_user['email']);
            if ($u && isset($u['id'])) {
                $user_id = (int)$u['id'];
                apiLog("ENROLL: Resolved user_id to: {$user_id}");
            }
        }

        // ensure numeric user id for SQL
        $user_id = (int)$user_id;
        apiLog("ENROLL: Final user_id={$user_id}");

        // Check if already enrolled
        $check = "SELECT id FROM enrollments WHERE user_id = $user_id AND course_id = $course_id";
        $result = mysqli_query($conn, $check);
        apiLog("ENROLL: Check query result rows: " . mysqli_num_rows($result));
        
        if (mysqli_num_rows($result) > 0) {
            apiLog("ENROLL: User already enrolled");
            sendResponse(false, 'Already enrolled in this course', null, 409);
        }

        // Enroll user
        $query = "INSERT INTO enrollments (user_id, course_id, status) VALUES ($user_id, $course_id, 'Active')";
        apiLog("ENROLL: Running query: {$query}");

        if (mysqli_query($conn, $query)) {
            $enrollment_id = mysqli_insert_id($conn);
            apiLog("ENROLL: Success! Enrollment ID: {$enrollment_id}");
            sendResponse(true, 'Enrollment successful', ['enrollment_id' => $enrollment_id]);
        } else {
            $error = mysqli_error($conn);
            apiLog("ENROLL: Failed! Error: {$error}");
            sendResponse(false, 'Enrollment failed: ' . $error, null, 500);
        }
    }

    if ($action === 'submit-for-approval') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['instructor', 'admin']);

        if (!isset($input['course_id'])) {
            sendResponse(false, 'Course ID required', null, 400);
        }

        $course_id = (int)$input['course_id'];

        $query = "INSERT INTO submissions (course_id, submitted_by, status) 
                  VALUES ($course_id, " . $current_user['user_id'] . ", 'Pending')";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Course submitted for approval', ['submission_id' => mysqli_insert_id($conn)]);
        } else {
            sendResponse(false, 'Submission failed: ' . mysqli_error($conn), null, 500);
        }
    }

    if ($action === 'approve-submission') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['course_team', 'admin']);

        if (!isset($input['submission_id'])) {
            sendResponse(false, 'Submission ID required', null, 400);
        }

        $submission_id = (int)$input['submission_id'];
        $notes = isset($input['notes']) ? mysqli_real_escape_string($conn, trim($input['notes'])) : '';
        $reviewer_id = $current_user['user_id'];

        $query = "UPDATE submissions SET status = 'Approved', reviewed_at = NOW(), reviewed_by = $reviewer_id, notes = '$notes' 
                  WHERE id = $submission_id";

        if (mysqli_query($conn, $query)) {
            // Update course status to Ongoing
            $get_course = "SELECT course_id FROM submissions WHERE id = $submission_id";
            $result = mysqli_query($conn, $get_course);
            $row = mysqli_fetch_assoc($result);
            
            if ($row) {
                $course_id = $row['course_id'];
                mysqli_query($conn, "UPDATE courses SET status = 'Ongoing' WHERE id = $course_id");
            }

            sendResponse(true, 'Submission approved');
        } else {
            sendResponse(false, 'Approval failed: ' . mysqli_error($conn), null, 500);
        }
    }

    if ($action === 'reject-submission') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['course_team', 'admin']);

        if (!isset($input['submission_id'])) {
            sendResponse(false, 'Submission ID required', null, 400);
        }

        $submission_id = (int)$input['submission_id'];
        $notes = isset($input['notes']) ? mysqli_real_escape_string($conn, trim($input['notes'])) : '';
        $reviewer_id = $current_user['user_id'];

        $query = "UPDATE submissions SET status = 'Rejected', reviewed_at = NOW(), reviewed_by = $reviewer_id, notes = '$notes' 
                  WHERE id = $submission_id";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Submission rejected');
        } else {
            sendResponse(false, 'Rejection failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

if ($request_method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update' && $id > 0) {
        $current_user = getCurrentUser();

        // Verify ownership or admin
        $course = "SELECT instructor_id FROM courses WHERE id = $id";
        $result = mysqli_query($conn, $course);
        $row = mysqli_fetch_assoc($result);

        if (!$row) {
            sendResponse(false, 'Course not found', null, 404);
        }

        if ($row['instructor_id'] != $current_user['user_id'] && $current_user['role'] !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }

        $updates = [];

        if (isset($input['title'])) {
            $title = mysqli_real_escape_string($conn, trim($input['title']));
            $updates[] = "title = '$title'";
        }

        if (isset($input['description'])) {
            $description = mysqli_real_escape_string($conn, trim($input['description']));
            $updates[] = "description = '$description'";
        }

        if (isset($input['category'])) {
            $category = mysqli_real_escape_string($conn, trim($input['category']));
            $updates[] = "category = '$category'";
        }

        if (isset($input['status'])) {
            $status = mysqli_real_escape_string($conn, trim($input['status']));
            $updates[] = "status = '$status'";
        }

        if (empty($updates)) {
            sendResponse(false, 'No fields to update', null, 400);
        }

        $query = "UPDATE courses SET " . implode(', ', $updates) . " WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Course updated');
        } else {
            sendResponse(false, 'Update failed: ' . mysqli_error($conn), null, 500);
        }
    }

    if ($action === 'update-progress' && $id > 0) {
        $current_user = getCurrentUser();

        if (!isset($input['progress']) || $input['progress'] < 0 || $input['progress'] > 100) {
            sendResponse(false, 'Progress must be between 0 and 100', null, 400);
        }

        $progress = (int)$input['progress'];
        $user_id = $current_user['user_id'];

        $query = "UPDATE enrollments SET progress = $progress WHERE user_id = $user_id AND course_id = $id";

        if (mysqli_query($conn, $query)) {
            if ($progress === 100) {
                // Generate certificate
                $cert_number = 'CERT-' . $user_id . '-' . $id . '-' . date('YmdHis');
                mysqli_query($conn, "INSERT INTO certificates (user_id, course_id, certificate_number) VALUES ($user_id, $id, '$cert_number')");
                mysqli_query($conn, "UPDATE enrollments SET status = 'Completed' WHERE user_id = $user_id AND course_id = $id");
            }
            sendResponse(true, 'Progress updated');
        } else {
            sendResponse(false, 'Update failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

if ($request_method === 'DELETE') {
    if ($action === 'delete' && $id > 0) {
        $current_user = getCurrentUser();
        requireRole($current_user, 'admin');

        $query = "DELETE FROM courses WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Course deleted');
        } else {
            sendResponse(false, 'Delete failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

sendResponse(false, 'Invalid action', null, 400);

?>
