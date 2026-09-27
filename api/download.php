<?php
require_once __DIR__ . '/_bootstrap.php';
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo 'Unauthorized'; exit(); }
$user_id = (int)$_SESSION['user_id'];

$project_name = trim($_GET['project'] ?? '');
$project = find_project($pdo, $user_id, $project_name);
if (!$project) { http_response_code(404); echo 'Project not found'; exit(); }

$stmt = $pdo->prepare('SELECT file_name, file_content FROM tbl_files WHERE project_id = ?');
$stmt->execute([$project['project_id']]);
$files = $stmt->fetchAll();
if (!$files) { http_response_code(404); echo 'Project has no files'; exit(); }

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo "The PHP zip extension is not enabled on this server.\n" .
         "In XAMPP: open php.ini, uncomment (remove the leading ;) the line\n" .
         "\"extension=zip\", then restart Apache.";
    exit();
}

$zipPath = sys_get_temp_dir() . '/codeoven_' . $project['project_id'] . '_' . time() . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo 'Could not create zip file';
    exit();
}
foreach ($files as $f) {
    $zip->addFromString($f['file_name'], $f['file_content']);
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $project_name) . '.zip"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
@unlink($zipPath);
