<?php
/**
 * includes/config.php
 *
 * Central configuration for CodeOven.
 * Adjust these values for your local XAMPP/WAMP environment.
 */

// ---- Database ----
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'editor_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---- Code Execution ----
// SECURITY WARNING:
// The /api/run.php endpoint compiles and executes whatever code the user has written,
// directly on this machine, using the same OS privileges as your web server (Apache).
// That is acceptable for a single-user local XAMPP/WAMP install used for learning/prototyping.
define('CODE_EXECUTION_ENABLED', true);

// Wall-clock timeout (seconds) applied to compilation and to the running
// program independently.
define('COMPILE_TIMEOUT_SECONDS', 15);
define('RUN_TIMEOUT_SECONDS', 10);

// Maximum characters of stdout/stderr returned to the browser (protects the
// UI from being frozen by runaway output, e.g. an infinite print loop).
define('MAX_OUTPUT_CHARS', 200000);

/**
 * Smart binary resolver: finds an executable by name, configured path,
 * or searching common Windows / Linux installation directories.
 */
function find_executable(string $defaultCmd, array $knownCandidatePaths = []): string {
    // 1. Direct path check
    if ($defaultCmd !== '' && @file_exists($defaultCmd) && !@is_dir($defaultCmd)) {
        return $defaultCmd;
    }

    // 2. Check candidate paths
    foreach ($knownCandidatePaths as $candidate) {
        if ($candidate !== '' && @file_exists($candidate) && !@is_dir($candidate)) {
            return $candidate;
        }
    }

    // 3. System lookup (where on Windows, which on Unix)
    $isWindows = (stripos(PHP_OS, 'WIN') !== false);
    if ($isWindows) {
        $out = @shell_exec('where ' . escapeshellarg($defaultCmd) . ' 2>NUL');
        if ($out) {
            $lines = array_filter(array_map('trim', explode("\n", $out)));
            foreach ($lines as $line) {
                if (@file_exists($line) && !@is_dir($line)) return $line;
            }
        }
    } else {
        $out = @shell_exec('which ' . escapeshellarg($defaultCmd) . ' 2>/dev/null');
        if ($out && @file_exists(trim($out))) {
            return trim($out);
        }
    }

    return $defaultCmd;
}

// Find Python
$pythonCandidates = [
    'C:\\Python314\\python.exe',
    'C:\\Python313\\python.exe',
    'C:\\Python312\\python.exe',
    'C:\\Python311\\python.exe',
    'C:\\Python310\\python.exe',
    'C:\\Windows\\py.exe',
    getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Programs\\Python\\Python312\\python.exe' : '',
    getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Programs\\Python\\Python311\\python.exe' : '',
    getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Programs\\Python\\Python310\\python.exe' : '',
];
define('PYTHON_BIN', find_executable(stripos(PHP_OS, 'WIN') !== false ? 'python' : 'python3', $pythonCandidates));

// Find PHP CLI
$phpCandidates = [
    PHP_BINARY,
    dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'php.exe',
    'C:\\wamp64\\bin\\php\\php8.4.15\\php.exe',
    'C:\\wamp64\\bin\\php\\php8.3.28\\php.exe',
    'C:\\wamp64\\bin\\php\\php8.2.29\\php.exe',
    'C:\\wamp64\\bin\\php\\php8.1.33\\php.exe',
    'C:\\wamp64\\bin\\php\\php8.0.30\\php.exe',
    'C:\\xampp\\php\\php.exe',
];
define('PHP_BIN', find_executable('php', $phpCandidates));

// Find C++ (g++) and C (gcc)
$gppCandidates = [
    'C:\\msys64\\mingw64\\bin\\g++.exe',
    'C:\\msys64\\ucrt64\\bin\\g++.exe',
    'C:\\MinGW\\bin\\g++.exe',
    'C:\\TDM-GCC-64\\bin\\g++.exe',
];
define('GPP_BIN', find_executable('g++', $gppCandidates));

$gccCandidates = [
    'C:\\msys64\\mingw64\\bin\\gcc.exe',
    'C:\\msys64\\ucrt64\\bin\\gcc.exe',
    'C:\\MinGW\\bin\\gcc.exe',
    'C:\\TDM-GCC-64\\bin\\gcc.exe',
];
define('GCC_BIN', find_executable('gcc', $gccCandidates));

// Find Java (javac compiler and java runtime)
$javaHome = getenv('JAVA_HOME') ?: '';
$javacCandidates = array_filter([
    $javaHome ? $javaHome . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'javac.exe' : '',
    'C:\\Program Files\\Java\\jdk-21\\bin\\javac.exe',
    'C:\\Program Files\\Java\\jdk-17\\bin\\javac.exe',
    'C:\\Program Files\\Eclipse Adoptium\\jdk-21\\bin\\javac.exe',
    'C:\\Program Files\\Eclipse Adoptium\\jdk-17\\bin\\javac.exe',
]);
define('JAVAC_BIN', find_executable('javac', $javacCandidates));

$javaCandidates = array_filter([
    $javaHome ? $javaHome . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'java.exe' : '',
    'C:\\Program Files\\Java\\jdk-21\\bin\\java.exe',
    'C:\\Program Files\\Java\\jdk-17\\bin\\java.exe',
    'C:\\Program Files\\Eclipse Adoptium\\jdk-21\\bin\\java.exe',
    'C:\\Program Files\\Eclipse Adoptium\\jdk-17\\bin\\java.exe',
]);
define('JAVA_BIN', find_executable('java', $javaCandidates));
