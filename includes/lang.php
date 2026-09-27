<?php
/**
 * includes/lang.php
 * Single source of truth for "extension -> language" detection on the
 * server side. Keep in sync with EXT_INFO in js/dashboard.js.
 */

const LANGUAGE_BY_EXT = [
    'html' => 'html', 'htm' => 'html',
    'css'  => 'css',
    'js'   => 'javascript', 'mjs' => 'javascript',
    'json' => 'json',
    'php'  => 'php', 'phtml' => 'php',
    'py'   => 'python', 'pyw' => 'python',
    'cpp'  => 'cpp', 'cxx' => 'cpp', 'cc' => 'cpp',
    'c'    => 'c',
    'h'    => 'cpp', 'hpp' => 'cpp',
    'java' => 'java',
    'xml'  => 'xml',
    'sql'  => 'sql',
    'md'   => 'markdown', 'markdown' => 'markdown',
    'sh'   => 'shell',
    'txt'  => 'plaintext',
];

/** Detect a language identifier from a file name's extension. */
function detect_language($file_name) {
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    return LANGUAGE_BY_EXT[$ext] ?? 'plaintext';
}

/** Which of our languages support server-side compile/run. */
const RUNNABLE_LANGUAGES = ['python', 'php', 'cpp', 'c', 'java'];

function is_runnable_language($language) {
    return in_array($language, RUNNABLE_LANGUAGES, true);
}

/** Basic filename safety check shared by every endpoint that touches disk/DB. */
function is_safe_filename($name) {
    if ($name === '' || strlen($name) > 255) return false;
    // Disallow path traversal / separators / null bytes / control chars.
    if (preg_match('/[\\/\\\\\\x00-\\x1f]/', $name)) return false;
    if ($name === '.' || $name === '..') return false;
    return true;
}

function is_safe_project_name($name) {
    return is_safe_filename($name);
}
