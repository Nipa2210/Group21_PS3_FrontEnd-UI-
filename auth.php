<?php
/**
 * Authentication API
 * Handles login, logout, and session management
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

if ($request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'login') {
        // Validate input
        if (!isset($input['email']) || !isset($input['password']) || !isset($input['role'])) {
            sendResponse(false, 'Missing required fields', null, 400);
        }

        $email = trim($input['email']);
        $password = $input['password'];
        $role = trim($input['role']);

        if (!isValidEmail($email)) {
            sendResponse(false, 'Invalid email format', null, 400);
        }

        if (!isValidRole($role)) {
            sendResponse(false, 'Invalid role', null, 400);
        }

        // Check if demo user
        $demo_users = [
            'student@demo' => ['password' => 'demo', 'role' => 'student', 'name' => 'Student Demo'],
            'instructor@demo' => ['password' => 'demo', 'role' => 'instructor', 'name' => 'Instructor Demo'],
            'admin@demo' => ['password' => 'demo', 'role' => 'admin', 'name' => 'Admin Demo'],
            'course@demo' => ['password' => 'demo', 'role' => 'course_team', 'name' => 'Course Team'],
            'head@demo' => ['password' => 'demo', 'role' => 'dept_head', 'name' => 'Dept Head'],
            'analyst@demo' => ['password' => 'demo', 'role' => 'data_analyst', 'name' => 'Data Analyst'],
            'office@demo' => ['password' => 'demo', 'role' => 'office_manager', 'name' => 'Office Manager']
        ];

        // If demo login, prefer DB user record if present (so user_id is numeric)
        if (strpos($email, '@demo') !== false && $password === 'demo') {
            $user = getUserByEmail($conn, $email);
            startAPISession();
            if ($user) {
                // Use DB user id when available
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                sendResponse(true, 'Login successful', [
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'name' => $user['name']
                ]);
            } else {
                // Fallback: session uses email as id for pure-demo setup
                $_SESSION['user_id'] = $email;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                $_SESSION['name'] = explode('@', $email)[0] ?: 'Demo User';

                sendResponse(true, 'Login successful', [
                    'user_id' => $email,
                    'email' => $email,
                    'role' => $role,
                    'name' => $_SESSION['name']
                ]);
            }
        }

        // Try database login
        $user = getUserByEmail($conn, $email);

        if (!$user) {
            sendResponse(false, 'Invalid email or password', null, 401);
        }

        if ($user['role'] !== $role) {
            sendResponse(false, 'Role mismatch', null, 401);
        }

        // Verify password: support password_hash and legacy MD5 seed values
        $passwordOk = false;
        if (verifyPassword($password, $user['password'])) {
            $passwordOk = true;
        } else {
            // fallback to MD5 check for seeded demo users
            if (strlen($user['password']) === 32 && $user['password'] === md5($password)) {
                $passwordOk = true;
            }
            // also allow the explicit demo shortcut
            if (!$passwordOk && strpos($email, '@demo') !== false && $password === 'demo') {
                $passwordOk = true;
            }
        }

        if (!$passwordOk) {
            sendResponse(false, 'Invalid email or password', null, 401);
        }

        if (isset($user['is_active']) && !$user['is_active']) {
            sendResponse(false, 'User account is inactive', null, 403);
        }

        // Create session
        startAPISession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        sendResponse(true, 'Login successful', [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['name']
        ]);
    }

    if ($action === 'logout') {
        startAPISession();
        session_destroy();
        sendResponse(true, 'Logout successful');
    }

    if ($action === 'register') {
        // Validate input
        if (!isset($input['email']) || !isset($input['password']) || !isset($input['name']) || !isset($input['role'])) {
            sendResponse(false, 'Missing required fields', null, 400);
        }

        $email = trim($input['email']);
        $password = $input['password'];
        $name = trim($input['name']);
        $role = trim($input['role']);

        if (!isValidEmail($email)) {
            sendResponse(false, 'Invalid email format', null, 400);
        }

        if (strlen($password) < 6) {
            sendResponse(false, 'Password must be at least 6 characters', null, 400);
        }

        if (!isValidRole($role)) {
            sendResponse(false, 'Invalid role', null, 400);
        }

        // Check if user already exists
        $existing = getUserByEmail($conn, $email);
        if ($existing) {
            sendResponse(false, 'Email already registered', null, 409);
        }

        // Insert new user
        $password_hash = hashPassword($password);
        $email = mysqli_real_escape_string($conn, $email);
        $name = mysqli_real_escape_string($conn, $name);

        $query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password_hash', '$role')";
        
        if (mysqli_query($conn, $query)) {
            $user_id = mysqli_insert_id($conn);
            sendResponse(true, 'Registration successful', [
                'user_id' => $user_id,
                'email' => $email,
                'name' => $name,
                'role' => $role
            ]);
        } else {
            sendResponse(false, 'Registration failed: ' . mysqli_error($conn), null, 500);
        }
    }

} else if ($request_method === 'GET') {
    if ($action === 'check-session') {
        startAPISession();
        if (isset($_SESSION['user_id'])) {
            // If session contains a non-numeric user_id (demo fallback using email), try to resolve a numeric id
            $sessUserId = $_SESSION['user_id'];
            if (!is_numeric($sessUserId) && !empty($_SESSION['email'])) {
                // attempt DB lookup
                $u = getUserByEmail($conn, $_SESSION['email']);
                if ($u && isset($u['id'])) {
                    $_SESSION['user_id'] = (int)$u['id'];
                    $sessUserId = $_SESSION['user_id'];
                }
            }

            sendResponse(true, 'Session active', [
                'user_id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role'],
                'name' => $_SESSION['name']
            ]);
        } else {
            sendResponse(false, 'No active session', null, 401);
        }
    }
}

sendResponse(false, 'Invalid action', null, 400);

?>
