<?php
/**
 * Registrations & Office Manager API
 * Handles student registrations, payments, and administrative tasks
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
    if ($action === 'pending') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['office_manager', 'admin']);

        $query = "SELECT r.*, u.name as student_name, u.email, c.title as course_title 
                  FROM registrations r 
                  JOIN users u ON r.user_id = u.id 
                  JOIN courses c ON r.course_id = c.id 
                  WHERE r.status = 'Pending' 
                  ORDER BY r.registered_at ASC";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $registrations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $registrations[] = $row;
        }

        sendResponse(true, 'Pending registrations retrieved', $registrations);
    }

    if ($action === 'all') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['office_manager', 'admin']);

        $query = "SELECT r.*, u.name as student_name, c.title as course_title 
                  FROM registrations r 
                  JOIN users u ON r.user_id = u.id 
                  JOIN courses c ON r.course_id = c.id 
                  ORDER BY r.registered_at DESC";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $registrations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $registrations[] = $row;
        }

        sendResponse(true, 'All registrations retrieved', $registrations);
    }

    if ($action === 'get' && $id > 0) {
        $current_user = getCurrentUser();
        requireRole($current_user, ['office_manager', 'admin']);

        $query = "SELECT r.*, u.name as student_name, c.title as course_title 
                  FROM registrations r 
                  JOIN users u ON r.user_id = u.id 
                  JOIN courses c ON r.course_id = c.id 
                  WHERE r.id = $id";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $registration = mysqli_fetch_assoc($result);

        if (!$registration) {
            sendResponse(false, 'Registration not found', null, 404);
        }

        sendResponse(true, 'Registration retrieved', $registration);
    }

    if ($action === 'payment-summary') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['office_manager', 'admin']);

        $query = "SELECT 
                    COUNT(*) as total_registrations,
                    SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN payment_status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_count,
                    SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END) as total_received,
                    SUM(amount) as total_due
                  FROM registrations";
        $result = mysqli_query($conn, $query);
        $summary = mysqli_fetch_assoc($result);

        sendResponse(true, 'Payment summary retrieved', $summary);
    }
}

if ($request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create-registration') {
        $current_user = getCurrentUser();

        if (!isset($input['course_id'])) {
            sendResponse(false, 'Course ID required', null, 400);
        }

        $course_id = (int)$input['course_id'];
        $user_id = $current_user['user_id'];
        $amount = isset($input['amount']) ? (float)$input['amount'] : 0;

        // Check if registration already exists
        $check = "SELECT id FROM registrations WHERE user_id = $user_id AND course_id = $course_id";
        $result = mysqli_query($conn, $check);
        
        if (mysqli_num_rows($result) > 0) {
            sendResponse(false, 'Already registered for this course', null, 409);
        }

        $query = "INSERT INTO registrations (user_id, course_id, amount, status, payment_status) 
                  VALUES ($user_id, $course_id, $amount, 'Pending', 'Unpaid')";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Registration created', ['registration_id' => mysqli_insert_id($conn)]);
        } else {
            sendResponse(false, 'Registration failed: ' . mysqli_error($conn), null, 500);
        }
    }

    if ($action === 'approve-registration') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['office_manager', 'admin']);

        if (!isset($input['registration_id'])) {
            sendResponse(false, 'Registration ID required', null, 400);
        }

        $registration_id = (int)$input['registration_id'];

        // Get registration details
        $reg_query = "SELECT user_id, course_id FROM registrations WHERE id = $registration_id";
        $reg_result = mysqli_query($conn, $reg_query);
        $reg = mysqli_fetch_assoc($reg_result);

        if (!$reg) {
            sendResponse(false, 'Registration not found', null, 404);
        }

        // Update registration status
        $query = "UPDATE registrations SET status = 'Approved', processed_at = NOW() WHERE id = $registration_id";

        if (mysqli_query($conn, $query)) {
            // Auto-enroll in course
            $check = "SELECT id FROM enrollments WHERE user_id = " . $reg['user_id'] . " AND course_id = " . $reg['course_id'];
            $check_result = mysqli_query($conn, $check);
            
            if (mysqli_num_rows($check_result) === 0) {
                mysqli_query($conn, "INSERT INTO enrollments (user_id, course_id, status) VALUES (" . $reg['user_id'] . ", " . $reg['course_id'] . ", 'Active')");
            }

            sendResponse(true, 'Registration approved');
        } else {
            sendResponse(false, 'Approval failed: ' . mysqli_error($conn), null, 500);
        }
    }

    if ($action === 'record-payment') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['office_manager', 'admin']);

        if (!isset($input['registration_id'])) {
            sendResponse(false, 'Registration ID required', null, 400);
        }

        $registration_id = (int)$input['registration_id'];

        $query = "UPDATE registrations SET payment_status = 'Paid' WHERE id = $registration_id";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Payment recorded');
        } else {
            sendResponse(false, 'Payment recording failed: ' . mysqli_error($conn), null, 500);
        }
    }

    if ($action === 'bulk-process') {
        $current_user = getCurrentUser();
        requireRole($current_user, ['office_manager', 'admin']);

        if (!isset($input['registration_ids']) || !is_array($input['registration_ids'])) {
            sendResponse(false, 'Registration IDs array required', null, 400);
        }

        $ids = array_map('intval', $input['registration_ids']);
        $ids_str = implode(',', $ids);

        $query = "UPDATE registrations SET status = 'Approved', processed_at = NOW() WHERE id IN ($ids_str)";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Bulk processing completed', ['count' => count($ids)]);
        } else {
            sendResponse(false, 'Bulk processing failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

if ($request_method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update' && $id > 0) {
        $current_user = getCurrentUser();
        requireRole($current_user, ['office_manager', 'admin']);

        $updates = [];

        if (isset($input['status'])) {
            $status = mysqli_real_escape_string($conn, trim($input['status']));
            $updates[] = "status = '$status'";
        }

        if (isset($input['payment_status'])) {
            $payment_status = mysqli_real_escape_string($conn, trim($input['payment_status']));
            $updates[] = "payment_status = '$payment_status'";
        }

        if (empty($updates)) {
            sendResponse(false, 'No fields to update', null, 400);
        }

        $query = "UPDATE registrations SET " . implode(', ', $updates) . " WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Registration updated');
        } else {
            sendResponse(false, 'Update failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

if ($request_method === 'DELETE') {
    if ($action === 'delete' && $id > 0) {
        $current_user = getCurrentUser();
        requireRole($current_user, ['office_manager', 'admin']);

        $query = "DELETE FROM registrations WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'Registration deleted');
        } else {
            sendResponse(false, 'Delete failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

sendResponse(false, 'Invalid action', null, 400);

?>
