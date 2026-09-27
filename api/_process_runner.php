<?php
/**
 * api/_process_runner.php
 * Small cross-platform helper for running an external command with a
 * wall-clock timeout and captured stdout/stderr. Used by api/run.php.
 */

/**
 * @param string[] $cmd Command + arguments (array form avoids shell parsing issues).
 * @param string $cwd Working directory to run the command in.
 * @param int $timeoutSeconds
 * @param string $stdin Text piped to the process's standard input.
 * @return array{stdout:string,stderr:string,exit_code:int,timed_out:bool,duration_ms:int}
 */
function run_process_with_timeout(array $cmd, string $cwd, int $timeoutSeconds, string $stdin = '') {
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $start = microtime(true);
    $process = @proc_open($cmd, $descriptorSpec, $pipes, $cwd);

    if (!is_resource($process)) {
        return [
            'stdout' => '', 'stderr' => 'Failed to start process: ' . implode(' ', $cmd),
            'exit_code' => -1, 'timed_out' => false, 'duration_ms' => 0,
        ];
    }

    if ($stdin !== '' && substr($stdin, -1) !== "\n") {
        $stdin .= "\n";
    }
    fwrite($pipes[0], $stdin);
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = '';
    $stderr = '';
    $timedOut = false;

    while (true) {
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        $status = proc_get_status($process);
        if (!$status['running']) break;

        if ((microtime(true) - $start) > $timeoutSeconds) {
            $timedOut = true;
            proc_terminate($process, 9);
            usleep(150000);
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            break;
        }
        usleep(40000);
    }

    // Drain any remaining buffered output after process termination
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $lastStatus = proc_get_status($process);
    $exitCode = $timedOut ? -1 : ((isset($lastStatus['exitcode']) && $lastStatus['exitcode'] !== -1) ? $lastStatus['exitcode'] : proc_close($process));
    if ($timedOut) { @proc_close($process); }

    return [
        'stdout' => $stdout,
        'stderr' => $timedOut ? ($stderr . "\n[Execution timed out after {$timeoutSeconds}s and was terminated.]") : $stderr,
        'exit_code' => $exitCode,
        'timed_out' => $timedOut,
        'duration_ms' => (int)round((microtime(true) - $start) * 1000),
    ];
}

function truncate_output(string $text) {
    if (strlen($text) <= MAX_OUTPUT_CHARS) return $text;
    return substr($text, 0, MAX_OUTPUT_CHARS) . "\n\n[...output truncated at " . MAX_OUTPUT_CHARS . " characters...]";
}

function recursive_rmdir($dir) {
    if (!is_dir($dir)) return;
    $items = @scandir($dir);
    if (!is_array($items)) return;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            recursive_rmdir($path);
        } else {
            for ($i = 0; $i < 3; $i++) {
                if (@unlink($path) || !file_exists($path)) break;
                usleep(50000);
            }
        }
    }
    for ($i = 0; $i < 3; $i++) {
        if (@rmdir($dir) || !is_dir($dir)) break;
        usleep(50000);
    }
}
