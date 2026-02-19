<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Function to authenticate user
function login($username, $password) {
    global $pdo;

    // Fetch user with role
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.username = :username OR u.email = :username
    ");

    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Set Session Variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['radio_code'] = $user['radio_code']; // Used for simulation
        $_SESSION['radio_model'] = $user['radio_model']; // Used for simulation

        return true;
    }
    return false;
}

// Function to log out
function logout() {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Middleware: Require user to be logged in
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

// Middleware: Require specific role
function require_role($allowed_roles) {
    require_login();

    // Allow single string or array of roles
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    if (!in_array($_SESSION['role'], $allowed_roles)) {
        http_response_code(403);
        die("Access Denied: Insufficient permissions.");
    }
}

// Helper: Get Current User ID
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}
?>
