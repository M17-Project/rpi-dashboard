<?php
header('Content-Type: application/json');
include 'config_include.php';

$logFile = $config['gateway_log_file'];
$maxlines = $config['maxlines'] ?? 20;
$timezone = $config['timezone'] ?? 'UTC';

$processedEntries = [];

// Track new entries to return
$newEntries = [];

// Read log file
$lines = file($logFile, FILE_IGNORE_NEW_LINES);

// Temporary storage for voice start entries
$voiceStarts = [];

date_default_timezone_set($timezone);

foreach ($lines as $line) {
    $entry = json_decode($line, true);
    
    // Skip if entry is invalid
    if (!$entry) continue;
    
    // Skip if already processed
    $entryHash = md5($line);

    //$dateTime = new DateTime($entry['time'], new DateTimeZone($config['timezone']));
    
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
    $_SESSION['radio_status'] = "Listening";
    // Special handling for RF type entries
    if ($entry['type'] === 'RF') {
        if ($entry['subtype'] === 'Voice Start') {
	    $_SESSION['radio_status'] = "TX: ".trim($entry['src']);
	}
    } else if ($entry['type'] != 'RF' && $entry['subtype'] === 'Voice Start') {
        $_SESSION['radio_status'] = "RX: ". trim($entry['src']);
    } else if ($entry['type'] != 'RF' && $entry['subtype'] === 'Voice End') {
        $_SESSION['radio_status'] = "Listening";
    }

    // Handle packet log lines
    if ($entry['subtype'] === 'Packet') {
        if ($startEntry) {

	    if ($entry['type'] === 'RF') {
		$mer = number_format((float)$entry['mer'], 2, '.', '') ?? NULL;
	    } else {
                $mer = "-";
            }
            // Prepare entry for display
            $displayEntry = [
                'time' => date('Y-m-d H:i:s', strtotime($entry['time'])),
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
            $duration = $startTime->diff($endTime)->s;
	    if ($entry['type'] === 'RF') {
		$mer = number_format((float)$startEntry['mer'], 2, '.', '') ?? NULL;
	    } else {
                $mer = "-";
            }

            // Prepare entry for display
            $displayEntry = [
                'time' => date('Y-m-d H:i:s', strtotime($entry['time'])),
                'timestamp' => strtotime($entry['time']), // Add timestamp for sorting
                'src' => trim($entry['src']),
                'dst' => $entry['dst'],
                'type' => $entry['type'],
                'subtype' => "Voice",
                'can' => $entry['can'],
                'mer' => $mer,
                'duration' => $duration." sec",
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

