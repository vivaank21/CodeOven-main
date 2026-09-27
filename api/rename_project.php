<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$old_name = trim($_POST['old_name'] ?? '');
$new_name = trim($_POST['new_name'] ?? '');
if (!is_safe_project_name($new_name)) json_fail('Please enter a valid project name.');

$project = find_project($pdo, $user_id, $old_name);
if (!$project) json_fail('Project not found.', 404);
if (find_project($pdo, $user_id, $new_name)) json_fail('A project with that name already exists.');

$stmt = $pdo->prepare('UPDATE tbl_projects SET project_name = ? WHERE project_id = ? AND user_id = ?');
$stmt->execute([$new_name, $project['project_id'], $user_id]);
json_ok(['project_name' => $new_name]);
