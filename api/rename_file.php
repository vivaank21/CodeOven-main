<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$project_name = trim($_POST['project'] ?? '');
$old_name = trim($_POST['old_name'] ?? '');
$new_name = trim($_POST['new_name'] ?? '');

if (!is_safe_filename($new_name)) json_fail('Please enter a valid file name.');

$project = find_project($pdo, $user_id, $project_name);
if (!$project) json_fail('Project not found.', 404);

$stmt = $pdo->prepare('SELECT 1 FROM tbl_files WHERE project_id = ? AND file_name = ? LIMIT 1');
$stmt->execute([$project['project_id'], $new_name]);
if ($stmt->fetch()) json_fail('A file with that name already exists in this project.');

$language = detect_language($new_name);
$stmt = $pdo->prepare('UPDATE tbl_files SET file_name = ?, language = ? WHERE project_id = ? AND file_name = ?');
$stmt->execute([$new_name, $language, $project['project_id'], $old_name]);
json_ok(['file_name' => $new_name, 'language' => $language]);
