<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$project_name = trim($_POST['project_name'] ?? '');
$project = find_project($pdo, $user_id, $project_name);
if (!$project) json_fail('Project not found.', 404);

$stmt = $pdo->prepare('DELETE FROM tbl_projects WHERE project_id = ? AND user_id = ?');
$stmt->execute([$project['project_id'], $user_id]);
json_ok();
