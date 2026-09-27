<?php
// includes/auth.php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return $_SESSION['username'] ?? null;
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function get_user_by_username($username) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT user_id, username, email FROM tbl_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function register_user($username, $email, $password) {
    global $pdo;
    // basic validation (you can extend this)
    if (trim($username) === '' || trim($email) === '' || trim($password) === '') {
        return ['success' => false, 'message' => 'All fields are required.'];
    }
    // check uniqueness
    $stmt = $pdo->prepare('SELECT user_id FROM tbl_users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username or email already exists.'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO tbl_users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);
    $id = $pdo->lastInsertId();
    // create default preferences row
    $stmt2 = $pdo->prepare('INSERT INTO tbl_preferences (user_id) VALUES (?)');
    $stmt2->execute([$id]);
    return ['success' => true, 'user_id' => $id];
}

function login_user($username_or_email, $password) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT user_id, username, password_hash FROM tbl_users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username_or_email, $username_or_email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}

function logout_user() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
