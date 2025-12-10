<?php
/**
 * Users API
 * Handles user management and profile operations
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
        $query = "SELECT id, name, email, role, created_at, is_active FROM users ORDER BY id DESC";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        sendResponse(true, 'Users retrieved', $users);
    }

    if ($action === 'get' && $id > 0) {
        $user = getUserById($conn, $id);

        if (!$user) {
            sendResponse(false, 'User not found', null, 404);
        }

        sendResponse(true, 'User retrieved', sanitizeUser($user));
    }

    if ($action === 'by-role') {
        $role = isset($_GET['role']) ? trim($_GET['role']) : '';

        if (!isValidRole($role)) {
            sendResponse(false, 'Invalid role', null, 400);
        }

        $role = mysqli_real_escape_string($conn, $role);
        $query = "SELECT id, name, email, role, created_at FROM users WHERE role = '$role'";
        $result = mysqli_query($conn, $query);

        if (!$result) {
            sendResponse(false, 'Query failed: ' . mysqli_error($conn), null, 500);
        }

        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        sendResponse(true, 'Users retrieved', $users);
    }
}

if ($request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        // Require admin role
        $current_user = getCurrentUser();
        requireRole($current_user, 'admin');

        // Validate input
        if (!isset($input['email']) || !isset($input['password']) || !isset($input['name']) || !isset($input['role'])) {
            apiLog('create_user: missing fields - ' . json_encode($input));
            sendResponse(false, 'Missing required fields', null, 400);
        }

        $email = trim($input['email']);
        $password = $input['password'];
        $name = trim($input['name']);
        $role = trim($input['role']);

        if (!isValidEmail($email)) {
            sendResponse(false, 'Invalid email format', null, 400);
        }

        if (!isValidRole($role)) {
            sendResponse(false, 'Invalid role', null, 400);
        }

        // Check if user already exists
        $existing = getUserByEmail($conn, $email);
        if ($existing) {
            apiLog('create_user: email exists - ' . $email);
            sendResponse(false, 'Email already exists', null, 409);
        }

        // Insert new user
        $password_hash = hashPassword($password);
        $email = mysqli_real_escape_string($conn, $email);
        $name = mysqli_real_escape_string($conn, $name);

        $query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password_hash', '$role')";

        if (mysqli_query($conn, $query)) {
            $user_id = mysqli_insert_id($conn);
            apiLog('create_user: success - id=' . $user_id . ' email=' . $email . ' created_by=' . ($current_user['email'] ?? 'unknown'));
            sendResponse(true, 'User created', [
                'user_id' => $user_id,
                'email' => $email,
                'name' => $name,
                'role' => $role
            ]);
        } else {
            apiLog('create_user: failed - ' . mysqli_error($conn) . ' query=' . $query);
            sendResponse(false, 'Creation failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

if ($request_method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update' && $id > 0) {
        $current_user = getCurrentUser();
        
        // User can only update their own profile, unless they're admin
        if ($current_user['user_id'] != $id && $current_user['role'] !== 'admin') {
            sendResponse(false, 'Unauthorized', null, 403);
        }

        $updates = [];
        $params = [];

        if (isset($input['name'])) {
            $name = mysqli_real_escape_string($conn, trim($input['name']));
            $updates[] = "name = '$name'";
        }

        if (isset($input['phone'])) {
            $phone = mysqli_real_escape_string($conn, trim($input['phone']));
            $updates[] = "phone = '$phone'";
        }

        if (isset($input['address'])) {
            $address = mysqli_real_escape_string($conn, trim($input['address']));
            $updates[] = "address = '$address'";
        }

        if (empty($updates)) {
            sendResponse(false, 'No fields to update', null, 400);
        }

        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'User updated');
        } else {
            sendResponse(false, 'Update failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

if ($request_method === 'DELETE') {
    if ($action === 'delete' && $id > 0) {
        $current_user = getCurrentUser();
        requireRole($current_user, 'admin');

        $query = "DELETE FROM users WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            sendResponse(true, 'User deleted');
        } else {
            sendResponse(false, 'Delete failed: ' . mysqli_error($conn), null, 500);
        }
    }
}

sendResponse(false, 'Invalid action', null, 400);

?>
