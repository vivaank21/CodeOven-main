<?php
/**
 * api/run.php
 * Compiles (if needed) and executes the given project's entry file for
 * Python, PHP, C/C++, or Java, and returns stdout/stderr/exit code as JSON.
 *
 * Supports both logged-in database projects and guest (localStorage) files.
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_process_runner.php';

if (!CODE_EXECUTION_ENABLED) {
    json_fail('Code execution is disabled on this server (see includes/config.php).', 403);
}

$user_id = $_SESSION['user_id'] ?? null;
$project_name = trim($_POST['project'] ?? '');
$entry_file = trim($_POST['entry_file'] ?? '');
$stdin = $_POST['stdin'] ?? '';
$guest_files_json = $_POST['guest_files'] ?? '';

$files = [];

// 1. If logged in and project name is given, load files from database
if ($pdo && $user_id !== null && $project_name !== '') {
    $project = find_project($pdo, (int)$user_id, $project_name);
    if ($project) {
        $stmt = $pdo->prepare('SELECT file_name, language, file_content FROM tbl_files WHERE project_id = ?');
        $stmt->execute([$project['project_id']]);
        $files = $stmt->fetchAll();
    }
}

// 2. If no DB files found, check if guest files were supplied in POST
if (empty($files) && !empty($guest_files_json)) {
    $decoded = is_array($guest_files_json) ? $guest_files_json : json_decode($guest_files_json, true);
    if (!is_array($decoded) && is_string($guest_files_json)) {
        $decoded = json_decode(stripslashes($guest_files_json), true);
    }
    if (is_array($decoded)) {
        foreach ($decoded as $fname => $data) {
            if (!is_safe_filename($fname)) continue;
            $content = is_array($data) ? ($data['content'] ?? '') : (string)$data;
            $files[] = [
                'file_name' => $fname,
                'language' => detect_language($fname),
                'file_content' => $content,
            ];
        }
    }
}

if (empty($files)) {
    json_fail('This project has no files to run.');
}

$byName = [];
foreach ($files as $f) {
    $byName[$f['file_name']] = $f;
}

if ($entry_file === '' || !isset($byName[$entry_file])) {
    // Fall back to the first runnable file in the project.
    $entry_file = null;
    foreach ($files as $f) {
        if (is_runnable_language($f['language'])) {
            $entry_file = $f['file_name'];
            break;
        }
    }
    if ($entry_file === null) {
        json_fail('No runnable Python, PHP, C/C++, or Java file was found in this project.');
    }
}

$language = $byName[$entry_file]['language'];
if (!is_runnable_language($language)) {
    json_fail("\"$entry_file\" is a $language file. Only Python, PHP, C/C++, and Java files can be run in the terminal — HTML/CSS/JS files use Live Preview instead.");
}

// --- Helper to verify that compiler / interpreter binary exists ---
function assert_binary_exists(string $bin, string $name, string $installHint = '') {
    // If it's a file path, verify with file_exists
    if (@file_exists($bin) && !@is_dir($bin)) {
        return;
    }
    // Check with where / which
    $isWin = stripos(PHP_OS, 'WIN') !== false;
    $lookup = $isWin ? @shell_exec('where ' . escapeshellarg($bin) . ' 2>NUL') : @shell_exec('which ' . escapeshellarg($bin) . ' 2>/dev/null');
    if ($lookup && trim($lookup) !== '') {
        return;
    }

    $msg = "Runtime / compiler not found: \"$name\" ($bin) is not installed or not in the server PATH.";
    if ($installHint) {
        $msg .= " " . $installHint;
    }
    json_fail($msg, 500);
}

// --- Set up an isolated temp working directory with all project files ---
$workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'codeoven_run_' . bin2hex(random_bytes(8));
if (!mkdir($workDir, 0700, true)) {
    json_fail('Could not create a temporary execution directory.', 500);
}

$result = null;
$errorMessage = null;

try {
    foreach ($files as $f) {
        if (!is_safe_filename($f['file_name'])) continue;
        file_put_contents($workDir . DIRECTORY_SEPARATOR . $f['file_name'], $f['file_content']);
    }

    if ($language === 'python') {
        assert_binary_exists(PYTHON_BIN, 'Python', 'Please install Python from python.org and add it to PATH.');
        $result = run_process_with_timeout(
            [PYTHON_BIN, $entry_file],
            $workDir,
            RUN_TIMEOUT_SECONDS,
            $stdin
        );
    } elseif ($language === 'php') {
        assert_binary_exists(PHP_BIN, 'PHP CLI', 'Please ensure PHP CLI is available or configure PHP_BIN in includes/config.php.');
        $result = run_process_with_timeout(
            [PHP_BIN, $entry_file],
            $workDir,
            RUN_TIMEOUT_SECONDS,
            $stdin
        );
    } elseif ($language === 'cpp' || $language === 'c') {
        $isC = ($language === 'c');
        $compilerBin = $isC ? GCC_BIN : GPP_BIN;
        $compilerName = $isC ? 'C Compiler (gcc)' : 'C++ Compiler (g++)';
        assert_binary_exists($compilerBin, $compilerName, 'Please install MinGW-w64 or MSYS2 and add g++/gcc to PATH.');

        $stdFlag = $isC ? '-std=c11' : '-std=c++17';
        $isWindows = stripos(PHP_OS, 'WIN') !== false;
        $exeName = 'codeoven_program' . ($isWindows ? '.exe' : '');

        $compile = run_process_with_timeout(
            [$compilerBin, '-O2', $stdFlag, '-o', $exeName, $entry_file],
            $workDir,
            COMPILE_TIMEOUT_SECONDS
        );

        if ($compile['exit_code'] !== 0) {
            $result = [
                'stdout' => '',
                'stderr' => $compile['stderr'] ?: $compile['stdout'],
                'exit_code' => $compile['exit_code'],
                'timed_out' => $compile['timed_out'],
                'duration_ms' => $compile['duration_ms'],
                'stage' => 'compile',
            ];
        } else {
            $result = run_process_with_timeout(
                [$workDir . DIRECTORY_SEPARATOR . $exeName],
                $workDir,
                RUN_TIMEOUT_SECONDS,
                $stdin
            );
        }
    } elseif ($language === 'java') {
        assert_binary_exists(JAVAC_BIN, 'Java Compiler (javac)', 'Please install a JDK (e.g. Eclipse Adoptium or Oracle JDK) and set JAVA_HOME/PATH.');
        assert_binary_exists(JAVA_BIN, 'Java Runtime (java)', 'Please install a JRE/JDK and set JAVA_HOME/PATH.');

        $sources = glob($workDir . DIRECTORY_SEPARATOR . '*.java');
        $sourceNames = array_map('basename', $sources) ?: [$entry_file];

        $compile = run_process_with_timeout(
            array_merge([JAVAC_BIN], $sourceNames),
            $workDir,
            COMPILE_TIMEOUT_SECONDS
        );

        if ($compile['exit_code'] !== 0) {
            $result = [
                'stdout' => '',
                'stderr' => $compile['stderr'] ?: $compile['stdout'],
                'exit_code' => $compile['exit_code'],
                'timed_out' => $compile['timed_out'],
                'duration_ms' => $compile['duration_ms'],
                'stage' => 'compile',
            ];
        } else {
            $entryContent = $byName[$entry_file]['file_content'] ?? '';
            $className = null;
            if (preg_match('/\bpublic\s+class\s+([A-Za-z_$][A-Za-z0-9_$]*)/', $entryContent, $m)) {
                $className = $m[1];
            } elseif (preg_match('/\bclass\s+([A-Za-z_$][A-Za-z0-9_$]*)/', $entryContent, $m)) {
                $className = $m[1];
            } else {
                $className = pathinfo($entry_file, PATHINFO_FILENAME);
            }

            $result = run_process_with_timeout(
                [JAVA_BIN, '-cp', $workDir, $className],
                $workDir,
                RUN_TIMEOUT_SECONDS,
                $stdin
            );
        }
    }

} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
} finally {
    recursive_rmdir($workDir);
}

if ($errorMessage !== null) {
    json_fail('Execution error: ' . $errorMessage, 500);
}

json_ok([
    'entry_file' => $entry_file,
    'language' => $language,
    'stage' => $result['stage'] ?? 'run',
    'stdout' => truncate_output($result['stdout'] ?? ''),
    'stderr' => truncate_output($result['stderr'] ?? ''),
    'exit_code' => $result['exit_code'] ?? 0,
    'timed_out' => $result['timed_out'] ?? false,
    'duration_ms' => $result['duration_ms'] ?? 0,
]);
