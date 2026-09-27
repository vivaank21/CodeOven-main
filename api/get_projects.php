<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

// Make sure the user always has at least one project to open.
ensure_default_project($pdo, $user_id);

$stmt = $pdo->prepare('SELECT project_name, updated_at FROM tbl_projects WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->execute([$user_id]);
json_ok(['projects' => $stmt->fetchAll()]);
