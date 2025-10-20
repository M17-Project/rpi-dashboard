<?php
session_start();

$configFile = 'config.php';

$defaultConfig = [
    'gateway_log_file' => 'files/dashboard.log',
    'gateway_config_file' => 'files/m17-gateway.ini',
    'hostfile' => 'files/M17Hosts.txt',
    'override_hostfile' => 'files/OverrideHosts.txt',
    'maxlines' => '15',
    'timezone' => 'UTC',
    'unit_system' => 'metric',
    'map_marker_ttl' => '43200',
];

if (!$_SESSION['radio_status']) {
    $_SESSION['radio_status'] = "-";
}

// Create the config file if it doesn't exist
if (!file_exists($configFile)) {
    file_put_contents($configFile, "<?php\nreturn " . var_export($defaultConfig, true) . ";\n");
    $config = $defaultConfig;
} else {
    $config = include $configFile;

    // Add missing keys with default values
    $updated = false;
    foreach ($defaultConfig as $key => $value) {
        if (!array_key_exists($key, $config)) {
            $config[$key] = $value;
            $updated = true;
        }
    }

    // If updates were made, rewrite the config file
    if ($updated) {
        file_put_contents($configFile, "<?php\nreturn " . var_export($config, true) . ";\n");
    }
}

// just PHP cURL it, yo.
function update_hostfile_with_retries(
    string $url,
    string $destPath,
    string $userAgent = 'rpi-dashboard Hostfile Updater',
    int $maxRetries = 5,
    int $timeout = 20,
    int $connectTimeout = 10,
    int $maxSleepCap = 30
): array {
    $attempt = 0;
    $tmpPath = $destPath . '.tmp';

    while ($attempt <= $maxRetries) {
        $attempt++;

        $headers = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HEADERFUNCTION => function($ch, $header) use (&$headers) {
                $len = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            },
        ]);

        $body   = curl_exec($ch);
        $error  = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return [false, "cURL error: $error", $status ?: 0, $headers, $attempt];
        }

        if ($status === 200) {
            if ($body === false || $body === '') {
                return [false, "Empty response body from server.", $status, $headers, $attempt];
            }
            if (file_put_contents($tmpPath, $body) === false) {
                return [false, "Failed writing temp file.", $status, $headers, $attempt];
            }
            if (!@rename($tmpPath, $destPath)) {
                @unlink($tmpPath);
                return [false, "Failed moving temp file into place.", $status, $headers, $attempt];
            }
            $bytes = strlen($body);
            return [true, "Hostfile updated successfully ($bytes bytes).", $status, $headers, $attempt];
        }

        if ($status === 429) {
            // Respect Retry-After
            $retryAfter = 0;
            if (isset($headers['retry-after'])) {
                $ra = $headers['retry-after'];
                if (ctype_digit($ra)) {
                    $retryAfter = (int)$ra;
                } else {
                    $ts = strtotime($ra);
                    if ($ts !== false) {
                        $retryAfter = max(0, $ts - time());
                    }
                }
            }
            // Exponential backoff + jitter; cap so the UI doesn’t hang forever
            $backoff = max($retryAfter, pow(2, $attempt)) + (random_int(0, 1000) / 1000.0);
            $sleepFor = (int)ceil(min($backoff, $maxSleepCap));
            if ($attempt > $maxRetries) {
                return [false, "Rate limited (HTTP 429). Max retries exceeded.", $status, $headers, $attempt];
            }
            sleep($sleepFor);
            continue;
        }

        return [false, "HTTP $status returned by server.", $status, $headers, $attempt];
    }

    return [false, "Unreachable code path hit, which is impressive.", 0, [], $attempt];
}


// just read the latest 50 lines from the logfile
// while not touching the rest of the file
function tailFile($filePath, $lines = 50) {
    $f = fopen($filePath, "r");
    if (!$f) return false;

    $buffer = '';
    $chunkSize = 4096; // Read 4KB at a time
    $pos = -1;
    $lineCount = 0;
    $fileSize = filesize($filePath);

    if ($fileSize === 0) {
        fclose($f);
        return [];
    }

    fseek($f, 0, SEEK_END);

    while (ftell($f) > 0 && $lineCount <= $lines) {
        $readSize = ($fileSize - abs($pos) < $chunkSize) ? $fileSize - abs($pos) : $chunkSize;

        // Ensure readSize is never zero or negative
        if ($readSize <= 0) {
            break;
        }

        $pos -= $readSize;
        fseek($f, $pos, SEEK_END);
        $data = fread($f, $readSize);

        if ($data === false) {
            break;  // Stop on fread failure
        }

        $buffer = $data . $buffer;
        $lineCount = substr_count($buffer, "\n");

        if (abs($pos) >= $fileSize) {
            break;  // Stop if we've reached the beginning of the file
        }
    }

    fclose($f);

    $linesArray = explode("\n", $buffer);
    return array_slice($linesArray, -$lines);
}
