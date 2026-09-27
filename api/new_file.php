<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$project_name = trim($_POST['project'] ?? '');
$file_name = trim($_POST['file_name'] ?? '');
$content = $_POST['content'] ?? '';

if (!is_safe_filename($file_name)) json_fail('Please enter a valid file name.');

$project = find_project($pdo, $user_id, $project_name);
if (!$project) json_fail('Project not found.', 404);

$stmt = $pdo->prepare('SELECT file_id FROM tbl_files WHERE project_id = ? AND file_name = ? LIMIT 1');
$stmt->execute([$project['project_id'], $file_name]);
if ($stmt->fetch()) json_fail('A file with that name already exists in this project.');

$language = detect_language($file_name);
$stmt = $pdo->prepare('INSERT INTO tbl_files (project_id, user_id, file_name, language, file_content) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$project['project_id'], $user_id, $file_name, $language, $content]);
touch_project($pdo, $project['project_id']);

json_ok(['file_name' => $file_name, 'language' => $language]);
