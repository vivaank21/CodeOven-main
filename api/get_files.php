<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$project_name = trim($_GET['project'] ?? '');
$project = find_project($pdo, $user_id, $project_name);
if (!$project) json_fail('Project not found.', 404);

$stmt = $pdo->prepare('SELECT file_name, language, updated_at FROM tbl_files WHERE project_id = ? ORDER BY file_name ASC');
$stmt->execute([$project['project_id']]);
json_ok(['files' => $stmt->fetchAll()]);
