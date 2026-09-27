<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$project_name = trim($_POST['project'] ?? '');
$file_name = trim($_POST['file_name'] ?? '');
$content = $_POST['content'] ?? '';

if (!is_safe_filename($file_name)) json_fail('Please enter a valid file name.');

// Auto-create the project on first save so "guest -> logged in" and
// brand-new-project flows never fail with a confusing 404.
$project = find_project($pdo, $user_id, $project_name);
if (!$project) {
    $project_id = create_project($pdo, $user_id, $project_name, false);
    $project = ['project_id' => $project_id, 'project_name' => $project_name];
}

$language = detect_language($file_name);

try {
    $stmt = $pdo->prepare('SELECT file_id FROM tbl_files WHERE project_id = ? AND file_name = ? LIMIT 1');
    $stmt->execute([$project['project_id'], $file_name]);
    $row = $stmt->fetch();

    if ($row) {
        $stmt2 = $pdo->prepare('UPDATE tbl_files SET file_content = ?, language = ?, updated_at = NOW() WHERE file_id = ?');
        $stmt2->execute([$content, $language, $row['file_id']]);
    } else {
        $stmt2 = $pdo->prepare('INSERT INTO tbl_files (project_id, user_id, file_name, language, file_content) VALUES (?, ?, ?, ?, ?)');
        $stmt2->execute([$project['project_id'], $user_id, $file_name, $language, $content]);
    }
    touch_project($pdo, $project['project_id']);
    json_ok(['message' => 'Saved', 'language' => $language]);
} catch (Exception $e) {
    json_fail('Error saving file: ' . $e->getMessage(), 500);
}
