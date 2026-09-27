<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$project_name = trim($_POST['project_name'] ?? '');
$template = $_POST['template'] ?? 'web'; // web | blank

if (!is_safe_project_name($project_name)) json_fail('Please enter a valid project name.');
if (find_project($pdo, $user_id, $project_name)) json_fail('A project with that name already exists.');

create_project($pdo, $user_id, $project_name, $template === 'web');
json_ok(['project_name' => $project_name]);
