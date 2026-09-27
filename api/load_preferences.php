<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$stmt = $pdo->prepare('SELECT layout, theme, word_wrap, show_line_numbers, auto_save, font_size, last_project FROM tbl_preferences WHERE user_id = ? LIMIT 1');
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if (!$row) {
    $pdo->prepare('INSERT INTO tbl_preferences (user_id) VALUES (?)')->execute([$user_id]);
    $row = ['layout' => 'vertical', 'theme' => 'light', 'word_wrap' => 1, 'show_line_numbers' => 1, 'auto_save' => 1, 'font_size' => 14, 'last_project' => null];
}
json_ok(['preferences' => $row]);
