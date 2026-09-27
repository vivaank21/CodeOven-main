<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$project_name = trim($_POST['project'] ?? '');
$file_name = trim($_POST['file_name'] ?? '');

$project = find_project($pdo, $user_id, $project_name);
if (!$project) json_fail('Project not found.', 404);

$stmt = $pdo->prepare('DELETE FROM tbl_files WHERE project_id = ? AND file_name = ?');
$stmt->execute([$project['project_id'], $file_name]);
json_ok();
