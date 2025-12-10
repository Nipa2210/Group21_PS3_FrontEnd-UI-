<?php
/**
 * Utility/Helper Functions
 */

// Response handler
function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}

// Validate email
function isValidEmail($email) {
    // Allow demo emails (e.g., student@demo, instructor@demo, etc.)
    if (strpos($email, '@demo') !== false) {
        return true;
    }
    // Standard email validation for real emails
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate role
function isValidRole($role) {
    $valid_roles = ['student', 'instructor', 'admin', 'course_team', 'dept_head', 'data_analyst', 'office_manager'];
    return in_array($role, $valid_roles);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Get user by email
function getUserByEmail($conn, $email) {
    $email = mysqli_real_escape_string($conn, $email);
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return null;
    }
    
    return mysqli_fetch_assoc($result);
}

// Get user by ID
function getUserById($conn, $id) {
    $id = (int)$id;
    $query = "SELECT * FROM users WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return null;
    }
    
    return mysqli_fetch_assoc($result);
}

// Check authorization based on role
function requireRole($user, $required_roles) {
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }
    
    if (!in_array($user['role'], $required_roles)) {
        sendResponse(false, 'Access denied: insufficient permissions', null, 403);
    }
}

// Start session for API
function startAPISession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Get current user from session
function getCurrentUser() {
    startAPISession();
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, 'Unauthorized: please login', null, 401);
    }
    return $_SESSION;
}

// Clear sensitive data from user array
function sanitizeUser($user) {
    unset($user['password']);
    return $user;
}

// Simple API logger for troubleshooting (appends to ../logs/api.log)
function apiLog($message){
    $logDir = __DIR__ . '/../logs';
    if(!is_dir($logDir)){
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/api.log';
    $ts = date('Y-m-d H:i:s');
    $entry = "[{$ts}] " . $message . "\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

?>
