<?php
/**
 * Analytics API
 * Handles data analytics and insights
 */

header('Content-Type: application/json');
require_once './cors.php';

require_once '../config.php';
require_once './helpers.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($request_method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($request_method === 'GET') {
    if ($action === 'platform-stats') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['admin', 'data_analyst']);

        // Total users by role
        $users_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
        $users_result = mysqli_query($conn, $users_query);
        $users_by_role = [];
        while ($row = mysqli_fetch_assoc($users_result)) {
            $users_by_role[$row['role']] = $row['count'];
        }

        // Total courses
        $courses_query = "SELECT COUNT(*) as count FROM courses";
        $courses_result = mysqli_query($conn, $courses_query);
        $courses_row = mysqli_fetch_assoc($courses_result);
        $total_courses = $courses_row['count'];

        // Total enrollments
        $enrollments_query = "SELECT COUNT(*) as count FROM enrollments";
        $enrollments_result = mysqli_query($conn, $enrollments_query);
        $enrollments_row = mysqli_fetch_assoc($enrollments_result);
        $total_enrollments = $enrollments_row['count'];

        // Completion rate
        $completed_query = "SELECT COUNT(*) as count FROM enrollments WHERE status = 'Completed'";
        $completed_result = mysqli_query($conn, $completed_query);
        $completed_row = mysqli_fetch_assoc($completed_result);
        $completion_rate = $total_enrollments > 0 ? round(($completed_row['count'] / $total_enrollments) * 100, 2) : 0;

        sendResponse(true, 'Platform statistics retrieved', [
            'users_by_role' => $users_by_role,
            'total_courses' => $total_courses,
            'total_enrollments' => $total_enrollments,
            'completion_rate' => $completion_rate . '%'
        ]);
    }

    if ($action === 'active-learners') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['admin', 'data_analyst']);

        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

        $query = "SELECT COUNT(DISTINCT e.user_id) as active_learners 
                  FROM enrollments e 
                  WHERE e.enrolled_at >= DATE_SUB(NOW(), INTERVAL $days DAY)";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);

        sendResponse(true, 'Active learners retrieved', [
            'active_learners' => $row['active_learners'],
            'period_days' => $days
        ]);
    }

    if ($action === 'course-popularity') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['admin', 'data_analyst']);

        $query = "SELECT c.id, c.title, COUNT(e.id) as enrollment_count 
                  FROM courses c 
                  LEFT JOIN enrollments e ON c.id = e.course_id 
                  GROUP BY c.id, c.title 
                  ORDER BY enrollment_count DESC 
                  LIMIT 10";
        $result = mysqli_query($conn, $query);

        $courses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }

        sendResponse(true, 'Popular courses retrieved', $courses);
    }

    if ($action === 'instructor-performance') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['admin', 'dept_head', 'data_analyst']);

        $query = "SELECT u.id, u.name, COUNT(c.id) as courses_count, 
                         AVG(COALESCE((SELECT progress FROM enrollments WHERE course_id = c.id LIMIT 1), 0)) as avg_progress
                  FROM users u 
                  LEFT JOIN courses c ON u.id = c.instructor_id 
                  WHERE u.role = 'instructor' 
                  GROUP BY u.id, u.name 
                  ORDER BY courses_count DESC";
        $result = mysqli_query($conn, $query);

        $instructors = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['avg_progress'] = round($row['avg_progress'], 2);
            $instructors[] = $row;
        }

        sendResponse(true, 'Instructor performance retrieved', $instructors);
    }

    if ($action === 'enrollment-trends') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['admin', 'data_analyst']);

        $query = "SELECT DATE(enrolled_at) as date, COUNT(*) as count 
                  FROM enrollments 
                  WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 90 DAY) 
                  GROUP BY DATE(enrolled_at) 
                  ORDER BY date DESC";
        $result = mysqli_query($conn, $query);

        $trends = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $trends[] = $row;
        }

        sendResponse(true, 'Enrollment trends retrieved', $trends);
    }

    if ($action === 'user-engagement') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['admin', 'data_analyst']);

        // Active users this week
        $week_query = "SELECT COUNT(DISTINCT user_id) as count FROM enrollments WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $week_result = mysqli_query($conn, $week_query);
        $week_row = mysqli_fetch_assoc($week_result);

        // Active users this month
        $month_query = "SELECT COUNT(DISTINCT user_id) as count FROM enrollments WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $month_result = mysqli_query($conn, $month_query);
        $month_row = mysqli_fetch_assoc($month_result);

        sendResponse(true, 'User engagement retrieved', [
            'active_this_week' => $week_row['count'],
            'active_this_month' => $month_row['count']
        ]);
    }
}

if ($request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'log-event') {
        $current_user = getCurrentUser();

        if (!isset($input['event_type'])) {
            sendResponse(false, 'Event type required', null, 400);
        }

        $event_type = mysqli_real_escape_string($conn, trim($input['event_type']));
        $event_data = isset($input['event_data']) ? json_encode($input['event_data']) : '{}';
        $course_id = isset($input['course_id']) ? (int)$input['course_id'] : 'NULL';

        $query = "INSERT INTO analytics (user_id, course_id, event_type, event_data) 
                  VALUES (" . $current_user['user_id'] . ", $course_id, '$event_type', '$event_data')";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Event logged', ['event_id' => mysqli_insert_id($conn)]);
        } else {
            sendResponse(false, 'Event logging failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

sendResponse(false, 'Invalid action', null, 400);

?>
