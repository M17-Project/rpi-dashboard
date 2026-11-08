<?php
// Shared bootstrap for every page and endpoint. It loads the dashboard
// configuration and provides helpers. It does not start a session: only the
// admin pages need one (see auth.php), and keeping sessions out of the
// polling endpoints means they never block each other on the session lock.

chdir(__DIR__);

define('DASHBOARD_VERSION', trim(@file_get_contents(__DIR__ . '/VERSION')) ?: 'dev');

$configFile = __DIR__ . '/config.php';

$defaultConfig = [
    'gateway_log_file' => 'files/dashboard.log',
    'gateway_config_file' => 'files/m17-gateway.ini',
    'hostfile' => 'files/M17Hosts.txt',
    'override_hostfile' => 'files/OverrideHosts.txt',
    'maxlines' => '15',
    'sms_max' => '20',
    'timezone' => 'UTC',
    'unit_system' => 'metric',
    'map_marker_ttl' => '43200',    // minutes
    'admin_password_hash' => '',
];

// Only these paths may be used for the gateway files. A path from the
// configuration is used only if it is one of these, so the admin form cannot
// point the dashboard at arbitrary files to read or overwrite.
const ALLOWED_GATEWAY_FILES = [
    'gateway_log_file' => ['files/dashboard.log', '/opt/m17/m17-gateway/dashboard.log'],
    'gateway_config_file' => ['files/m17-gateway.ini', '/etc/m17-gateway.ini'],
];

function saveConfig($config) {
    global $configFile;
    $ok = file_put_contents($configFile, "<?php\nreturn " . var_export($config, true) . ";\n", LOCK_EX) !== false;
    if ($ok && function_exists('opcache_invalidate')) opcache_invalidate($configFile, true);
    return $ok;
}

$config = file_exists($configFile) ? (include $configFile) : null;
if (!is_array($config)) {
    // Missing or empty config.php (e.g. pre-created by the installer)
    $config = $defaultConfig;
    saveConfig($config);
} else {
    // Add missing keys with default values
    $updated = false;
    foreach ($defaultConfig as $key => $value) {
        if (!array_key_exists($key, $config)) {
            $config[$key] = $value;
            $updated = true;
        }
    }
    if ($updated) saveConfig($config);
}

// Fall back to the default for any gateway path that is not allowed, so an
// old or tampered config.php cannot be used either.
foreach (ALLOWED_GATEWAY_FILES as $key => $allowed) {
    if (!in_array($config[$key], $allowed, true)) {
        $config[$key] = $defaultConfig[$key];
    }
}

$config['maxlines'] = max(1, min(500, (int)$config['maxlines']));
$config['sms_max'] = max(1, min(500, (int)$config['sms_max']));
$config['map_marker_ttl'] = max(1, (int)$config['map_marker_ttl']);
if (!in_array($config['timezone'], DateTimeZone::listIdentifiers(), true)) $config['timezone'] = 'UTC';
if (!in_array($config['unit_system'], ['metric', 'imperial'], true)) $config['unit_system'] = 'metric';

date_default_timezone_set($config['timezone']);

// HTML escaping shorthand
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Parse a log timestamp and convert it to the configured timezone. The
// gateway writes RFC 3339 times with its own UTC offset, which DateTime keeps
// unless told otherwise.
function logTime($s) {
    try {
        $dt = new DateTime((string)$s);
    } catch (Exception $e) {
        return null;
    }
    $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
    return $dt;
}

// Yield the lines of a file from last to first, reading backwards in chunks,
// so the whole file is never loaded. Stops after $maxBytes have been read.
function reverseLines($filePath, $maxBytes = PHP_INT_MAX) {
    $f = @fopen($filePath, 'r');
    if (!$f) return;
    $size = fstat($f)['size'];
    $pos = $size;
    $read = 0;
    $rest = '';
    $chunkSize = 8192;
    $first = true;

    while ($pos > 0 && $read < $maxBytes) {
        $len = min($chunkSize, $pos);
        $pos -= $len;
        $read += $len;
        fseek($f, $pos);
        $data = fread($f, $len);
        if ($data === false) break;

        $parts = explode("\n", $data . $rest);
        // The first element may be the tail of an earlier line
        $rest = array_shift($parts);
        if ($first) {
            // Drop the empty element after a trailing newline
            if (end($parts) === '') array_pop($parts);
            $first = false;
        }
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            yield $parts[$i];
        }
    }
    // $rest is the first line of the file, if we got that far
    if ($pos === 0 && $size > 0) yield $rest;
    fclose($f);
}

// Return the last $lines lines of a file, oldest first
function tailFile($filePath, $lines = 50) {
    $out = [];
    foreach (reverseLines($filePath) as $line) {
        $out[] = $line;
        if (count($out) >= $lines) break;
    }
    return array_reverse($out);
}

// Decode the last $lines entries of the gateway log, oldest first
function readLogEntries($logFile, $lines) {
    $entries = [];
    foreach (tailFile($logFile, $lines) as $line) {
        $e = json_decode($line, true);
        if (is_array($e) && isset($e['time'], $e['type'], $e['subtype'])) {
            $entries[] = $e;
        }
    }
    return $entries;
}

// List the system's ALSA PCM devices from /proc/asound, which needs no special
// permissions. Each entry has the card and device numbers, the device name
// (what m17-gateway's ALSACaptureDevice / ALSAPlaybackDevice settings match),
// the card's name, and whether it can play and record.
function alsaDevices($pcmFile = '/proc/asound/pcm', $cardsFile = '/proc/asound/cards') {
    $cardNames = [];
    foreach (@file($cardsFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        // " 1 [GenericStereoA]: simple-card - GenericStereoAudioCodec"
        if (preg_match('/^\s*(\d+)\s+\[[^\]]*\]:\s*.*? - (.*)$/', $line, $m)) {
            $cardNames[(int)$m[1]] = trim($m[2]);
        }
    }
    $devices = [];
    foreach (@file($pcmFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        // "01-00: 3f203000.i2s-dir-hifi dir-hifi-0 : 3f203000.i2s-dir-hifi dir-hifi-0 : capture 1"
        $parts = explode(' : ', $line);
        if (count($parts) < 3 || !preg_match('/^(\d+)-(\d+):/', $parts[0], $m)) continue;
        $streams = implode(' ', array_slice($parts, 2));
        $card = (int)$m[1];
        $devices[] = [
            'card' => $card,
            'device' => (int)$m[2],
            'name' => trim($parts[1]),
            'card_name' => $cardNames[$card] ?? "card $card",
            'play' => strpos($streams, 'playback') !== false,
            'record' => strpos($streams, 'capture') !== false,
        ];
    }
    return $devices;
}

// The modem from the newest "Gateway" "Start" event in the gateway log:
// ['modem' => type from the gateway configuration, 'ident' => what the modem
// reported, possibly several lines]. Both are '' if there is no such event
// (an older gateway) and the ident is '' if the modem didn't answer.
//
// The event is written once per gateway start, so it can be far back in a
// big log. The cache remembers the result and how far the log has been read,
// so each call reads only the lines added since; the whole log is read again
// only if it was replaced or truncated.
function modemIdent($logFile) {
    $cacheFile = __DIR__ . '/files/modem_ident.cache';
    clearstatcache(true, $logFile);
    $st = @stat($logFile);
    if (!$st) return ['modem' => '', 'ident' => ''];

    $cache = @json_decode((string)@file_get_contents($cacheFile), true);
    $resume = is_array($cache)
        && ($cache['inode'] ?? null) === $st['ino']
        && is_int($cache['pos'] ?? null)
        && $cache['pos'] <= $st['size']
        && is_string($cache['modem'] ?? null);   // caches from before 'modem' was kept are read again
    $modem = $resume ? $cache['modem'] : '';
    $ident = $resume ? (string)($cache['ident'] ?? '') : '';
    $pos = $resume ? $cache['pos'] : 0;

    if ($pos < $st['size'] && ($f = @fopen($logFile, 'r'))) {
        fseek($f, $pos);
        while (($line = fgets($f)) !== false) {
            // A line still being written has no newline yet; read it next time
            if (substr($line, -1) !== "\n") break;
            $pos += strlen($line);
            if (strpos($line, '"Gateway"') === false) continue;
            $e = json_decode($line, true);
            if (is_array($e) && ($e['type'] ?? '') === 'Gateway' && ($e['subtype'] ?? '') === 'Start') {
                $modem = (string)($e['modem'] ?? '');
                $ident = (string)($e['modemIdent'] ?? '');
            }
        }
        fclose($f);
        @file_put_contents($cacheFile, json_encode(['inode' => $st['ino'], 'pos' => $pos, 'modem' => $modem, 'ident' => $ident]), LOCK_EX);
    }

    return ['modem' => $modem, 'ident' => trim($ident)];
}

// A modem ident on one line:
// "CC1200-HAT 420-450 MHz\nFW v2.3 by ..." -> "CC1200-HAT 420-450 MHz · FW v2.3 by ..."
function identOneLine($ident) {
    return (string)preg_replace('/\s*\R\s*/u', ' · ', trim($ident));
}

// The part of a modem ident that says which board it is and which firmware
// version it runs, for places with little room. Formats this doesn't know
// are returned whole, on one line.
//   cc1200: "CC1200-HAT 420-450 MHz\nFW v2.3 by Wojciech SP5WWP" -> "CC1200-HAT 420-450 MHz · FW v2.3"
//   mmdvm:  "MMDVM_HS_Dual_Hat-v1.6.1 20251011 14.7456MHz dual ADF7021 FW by CA6JAU GitID #403e47c"
//           -> "MMDVM_HS_Dual_Hat-v1.6.1"
//           "MMDVM 20220623 TCXO (D-Star/DMR/.../M17 GitID #...)" -> "MMDVM 20220623"
function identShort($modem, $ident) {
    $ident = trim($ident);
    $lines = preg_split('/\R/u', $ident);
    if ($modem === 'cc1200' || stripos($ident, 'CC1200') === 0) {
        // First line names the board; the firmware version follows "FW"
        if (preg_match('/\bFW\s+v?\d[\w.]*/u', $ident, $m)) {
            return trim($lines[0]) === trim($m[0]) ? $m[0] : trim($lines[0]) . ' · ' . $m[0];
        }
        return trim($lines[0]);
    }
    if ($modem === 'mmdvm') {
        // The first word is the board, usually with the version in it
        // (MMDVM_HS, ZUMspot, ...); repeater boards say "MMDVM" and put the
        // version, a date, in the second word
        $words = preg_split('/\s+/u', $ident);
        if (!preg_match('/\d/', $words[0]) && isset($words[1]) && preg_match('/^\d{8}$/', $words[1])) {
            return $words[0] . ' ' . $words[1];
        }
        return $words[0];
    }
    return identOneLine($ident);
}

// m17-gateway package version, cached until dpkg's database changes
function gatewayVersion() {
    $cache = __DIR__ . '/files/gateway_version.cache';
    $status = '/var/lib/dpkg/status';
    if (is_file($cache) && @filemtime($cache) >= @filemtime($status)) {
        return trim(file_get_contents($cache));
    }
    $v = trim((string)shell_exec("dpkg-query -W -f='\${Version}' m17-gateway 2>/dev/null"));
    @file_put_contents($cache, $v);
    return $v;
}
