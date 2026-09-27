<?php
/**
 * includes/project.php
 * Shared helpers for working with projects/files, used by the api/* endpoints.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lang.php';

const STARTER_FILES = [
    'index.html' => "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <title>My Project</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <h1>Hello, CodeOven!</h1>\n  <script src=\"script.js\"></script>\n</body>\n</html>\n",
    'style.css' => "body {\n  font-family: Arial, sans-serif;\n  margin: 40px;\n}\n",
    'script.js' => "console.log('Hello from JavaScript!');\n",
];

/** Get a project row (project_id, project_name) for a user, or null. */
function find_project($pdo, $user_id, $project_name) {
    $stmt = $pdo->prepare('SELECT project_id, project_name FROM tbl_projects WHERE user_id = ? AND project_name = ? LIMIT 1');
    $stmt->execute([$user_id, $project_name]);
    return $stmt->fetch() ?: null;
}

/** Create a project (and, optionally, starter files) for a user. Returns project_id. */
function create_project($pdo, $user_id, $project_name, $with_starter_files = true) {
    $stmt = $pdo->prepare('INSERT INTO tbl_projects (user_id, project_name) VALUES (?, ?)');
    $stmt->execute([$user_id, $project_name]);
    $project_id = (int)$pdo->lastInsertId();

    if ($with_starter_files) {
        $insert = $pdo->prepare('INSERT INTO tbl_files (project_id, user_id, file_name, language, file_content) VALUES (?, ?, ?, ?, ?)');
        foreach (STARTER_FILES as $fname => $content) {
            $insert->execute([$project_id, $user_id, $fname, detect_language($fname), $content]);
        }
    }
    return $project_id;
}

/** Ensure the user has at least one project; return its name. Used on first dashboard load. */
function ensure_default_project($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT project_name FROM tbl_projects WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) return $row['project_name'];

    create_project($pdo, $user_id, 'My First Project', true);
    return 'My First Project';
}

function touch_project($pdo, $project_id) {
    $stmt = $pdo->prepare('UPDATE tbl_projects SET updated_at = NOW() WHERE project_id = ?');
    $stmt->execute([$project_id]);
}
