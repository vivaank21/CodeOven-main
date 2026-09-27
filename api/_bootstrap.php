<?php
/**
 * api/_bootstrap.php
 * Common bootstrap for every JSON API endpoint: starts the session, loads
 * helpers, sets the JSON content type, and figures out which user_id to use
 * (a real logged-in user, or null for guest/localStorage-only mode).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/project.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_POST)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $json = json_decode($rawInput, true);
        if (is_array($json)) {
            $_POST = $json;
        } else {
            parse_str($rawInput, $_POST);
        }
    }
}

function require_auth_or_fail() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized', 'guest' => true]);
        exit();
    }
    return (int)$_SESSION['user_id'];
}

function json_fail($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function json_ok($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit();
}
