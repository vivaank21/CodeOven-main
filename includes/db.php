<?php
// includes/db.php
require_once __DIR__ . '/config.php';

$pdo = null;
$db_error = null;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    $db_error = $e->getMessage();
    // In CLI or standalone run mode, we preserve $pdo as null so guest execution still works.
    // Web endpoints requiring DB can check $pdo or fail cleanly.
    if (php_sapi_name() !== 'cli' && !isset($_POST['guest_files'])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => "Database connection failed: " . $e->getMessage() . ". Check includes/config.php and make sure MySQL is running in WAMP/XAMPP."
        ]);
        exit();
    }
}
