<?php
/**
 * Certificates API
 * List certificates for current user or all certificates for admin
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
    if ($action === 'list') {
        // List certificates for currently logged in user
        $current_user = getCurrentUser();
        $user_id = $current_user['user_id'];

        // Resolve non-numeric session ids (email) if necessary
        if (!is_numeric($user_id) && !empty($current_user['email'])) {
            $u = getUserByEmail($conn, $current_user['email']);
            if ($u && isset($u['id'])) {
                $user_id = (int)$u['id'];
            }
        }

        $user_id = (int)$user_id;
        $query = "SELECT c.*, co.title as course_title FROM certificates c LEFT JOIN courses co ON c.course_id = co.id WHERE c.user_id = $user_id ORDER BY c.created_at DESC";
        $result = mysqli_query($conn, $query);
        if (!$result) sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);

        $rows = [];
        while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;

        sendResponse(true, 'Certificates retrieved', $rows);
    }

    if ($action === 'all') {
        // Admin: list all certificates
        $current_user = getCurrentUser();
        requireRole($current_user, 'admin');

        $query = "SELECT c.*, co.title as course_title, u.name as user_name, u.email as user_email FROM certificates c LEFT JOIN courses co ON c.course_id = co.id LEFT JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC";
        $result = mysqli_query($conn, $query);
        if (!$result) sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);

        $rows = [];
        while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;

        sendResponse(true, 'All certificates retrieved', $rows);
    }
}

sendResponse(false, 'Invalid action', null, 400);

?>