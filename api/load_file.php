<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$project_name = trim($_GET['project'] ?? '');
$file_name = trim($_GET['file'] ?? '');

$project = find_project($pdo, $user_id, $project_name);
if (!$project) json_fail('Project not found.', 404);

$stmt = $pdo->prepare('SELECT file_name, language, file_content FROM tbl_files WHERE project_id = ? AND file_name = ? LIMIT 1');
$stmt->execute([$project['project_id'], $file_name]);
$file = $stmt->fetch();
if (!$file) json_fail('File not found.', 404);

json_ok(['file' => $file]);
