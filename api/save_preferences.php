<?php
require_once __DIR__ . '/_bootstrap.php';
$user_id = require_auth_or_fail();

$layout = in_array($_POST['layout'] ?? '', ['horizontal', 'vertical'], true) ? $_POST['layout'] : 'vertical';
$theme = in_array($_POST['theme'] ?? '', ['light', 'dark'], true) ? $_POST['theme'] : 'light';
$word_wrap = isset($_POST['word_wrap']) ? (int)!!$_POST['word_wrap'] : 1;
$show_line_numbers = isset($_POST['show_line_numbers']) ? (int)!!$_POST['show_line_numbers'] : 1;
$auto_save = isset($_POST['auto_save']) ? (int)!!$_POST['auto_save'] : 1;
$font_size = max(10, min(32, (int)($_POST['font_size'] ?? 14)));
$last_project = isset($_POST['last_project']) ? trim($_POST['last_project']) : null;

$stmt = $pdo->prepare('
    INSERT INTO tbl_preferences (user_id, layout, theme, word_wrap, show_line_numbers, auto_save, font_size, last_project)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE layout = VALUES(layout), theme = VALUES(theme), word_wrap = VALUES(word_wrap),
        show_line_numbers = VALUES(show_line_numbers), auto_save = VALUES(auto_save),
        font_size = VALUES(font_size), last_project = COALESCE(VALUES(last_project), last_project), updated_at = NOW()
');
$stmt->execute([$user_id, $layout, $theme, $word_wrap, $show_line_numbers, $auto_save, $font_size, $last_project]);
json_ok();
