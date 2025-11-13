<?php
header('Content-Type: application/json');
include 'functions.php';

$logFile = $config['gateway_log_file'];
$maxlines = $config['maxlines'] ?? 20;
$timezone = $config['timezone'] ?? 'UTC';

$processedEntries = [];

// Track new entries to return
$newEntries = [];

$unit_system = $config['unit_system'];

// Read log file
$lines = tailFile($logFile, 100);

// Temporary storage for voice start entries
$voiceStarts = [];

date_default_timezone_set($timezone);

$calls_with_gnss = [];

foreach ($lines as $line) {
    $entry = json_decode($line, true);

    // Skip if entry is invalid
    if (!$entry) continue;

    // Skip if already processed
    $entryHash = md5($line);

    // Safely check for processed entries
    $isProcessed = false;
    if (is_array($processedEntries)) {
        foreach ($processedEntries as $processedEntry) {
            if (isset($processedEntry['hash']) && $processedEntry['hash'] === $entryHash) {
                $isProcessed = true;
                break;
            }
        }
    }

    if ($isProcessed) {
        continue;
    }

    // Handle reflector connect/disconnect events
    if ($entry['type'] === 'Reflector') {
        if ($entry['subtype'] === 'Connect') {
            $_SESSION['connected_ref'] = $entry['name'];
            $_SESSION['connected_mod'] = $entry['module'];
        } else if ($entry['subtype'] === 'Disconnect') {
            $_SESSION['connected_ref'] = "Disconnected";
            $_SESSION['connected_mod'] = "-";
        }
        continue;
    }

    // Construct status string for Radio Status box
    if ($entry['subtype'] === 'Voice Start' || $entry['subtype'] === 'Voice End') {
        $_SESSION['radio_status'] = "Listening";
        // Special handling for RF type entries
        if ($entry['type'] === 'RF' && $entry['subtype'] === 'Voice Start') {
                $_SESSION['radio_status'] = "RX: ".trim($entry['src']);
        } else if ($entry['type'] != 'RF' && $entry['subtype'] === 'Voice Start') {
            $_SESSION['radio_status'] = "TX: ". trim($entry['src']);
        }
    }

    $dt = new DateTime($entry['time']);
    if ($unit_system == "metric") {
        $time = $dt->format('d.m.y H:i');
    } else {
        $time = $dt->format('m/d/y h:i A');
    }


    // Add every call sign which sent GNSS data to an array
    if ($entry['subtype'] === 'GNSS') {
        $calls_with_gnss[] = $entry['src'];
    }

    // Handle packet log lines
    if ($entry['subtype'] === 'Packet') {
        if ($startEntry) {
            if ($entry['type'] === 'RF') {
                $mer = number_format((float)$entry['mer'], 1, '.', '')." %" ?? NULL;
            } else {
                $mer = "-";
            }
            // Prepare entry for display
            $displayEntry = [
                'time' => $time,
                'timestamp' => strtotime($entry['time']), // Add timestamp for sorting
                'src' => trim($entry['src']),
                'dst' => $entry['dst'],
                'type' => $entry['type'],
                'subtype' => $entry['subtype'],
                'can' => $entry['can'],
                'mer' => $mer,
                'duration' => "",
                'smsMessage' => $entry['smsMessage'],
                'hash' => $entryHash
            ];

            $newEntries[] = $displayEntry;
            $processedEntries[] = $displayEntry;
        }
        continue;
    }

    // Handle Voice Start
    if ($entry['subtype'] === 'Voice Start') {
        $voiceStarts[$entry['src']] = $entry;
        continue;
    }

    // Handle Voice End
    if ($entry['subtype'] === 'Voice End') {
        $startEntry = $voiceStarts[$entry['src']] ?? null;

        if ($startEntry) {
            // Calculate duration
            $startTime = new DateTime($startEntry['time']);
            $endTime = new DateTime($entry['time']);
            $duration = $endTime->getTimestamp() - $startTime->getTimestamp();
            $hours = floor($duration / 3600);
            $minutes = floor(($duration % 3600) / 60);
            $seconds = $duration % 60;

            if ($hours > 0) {
                $formattedDuration = sprintf("%d h %d m %d s", $hours, $minutes, $seconds);
            } else if ($minutes > 0) {
                $formattedDuration = sprintf("%d m %d s", $minutes, $seconds);
            } else {
                $formattedDuration = sprintf("%d s", $seconds);
            }
            
            if ($entry['type'] === 'RF') {
                $mer = number_format((float)$startEntry['mer'], 1, '.', '')." %" ?? NULL;
            } else {
                $mer = "&ndash;&nbsp;&nbsp;&nbsp;";
            }

            // Check if this call sign sent GNSS data and if,
            // then add a small SAT next to the call sign
            $call = trim($entry['src']);
            if (in_array($entry['src'], $calls_with_gnss)) {
                $call = trim($entry['src'])." &#128752;";
            }

            // Prepare entry for display
            $displayEntry = [
                'time' => $time,
                'timestamp' => strtotime($entry['time']), // Add timestamp for sorting
                'src' => $call,
                'dst' => $entry['dst'],
                'type' => $entry['type'],
                'subtype' => "Voice",
                'can' => $entry['can'],
                'mer' => $mer,
                'duration' => $formattedDuration,
                'hash' => $entryHash
            ];

            $newEntries[] = $displayEntry;
            $processedEntries[] = $displayEntry;

            // Remove processed start entry
            unset($voiceStarts[$entry['src']]);
        }
    }
}

// Sort entries by timestamp in descending order
usort($newEntries, function($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

// Limit to configured number of entries
$newEntries = array_slice($newEntries, 0, $maxlines);

// Remove timestamp from output
$outputEntries = array_map(function($entry) {
    unset($entry['timestamp']);
    return $entry;
}, $newEntries);

// Limit processed entries to prevent file growth
$processedEntries = array_slice($processedEntries, -100);

// Return new entries as JSON
echo json_encode($outputEntries);
exit;
?>
