<?php
header('Content-Type: application/json');

$configFile = 'config.php';
$config = include $configFile;

$logFile = $config['gateway_log_file'];
$maxlines = $config['maxlines'] ?? 20;

$processedEntries = [];

// Track new entries to return
$newEntries = [];

// Read log file
$lines = file($logFile, FILE_IGNORE_NEW_LINES);

// Temporary storage for voice start entries
$voiceStarts = [];

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

    // Special handling for RF type entries
    if ($entry['type'] === 'RF') {
        $displayEntry = [
            'time' => date('Y-m-d H:i:s', strtotime($entry['time'])),
            'timestamp' => strtotime($entry['time']),
            'src' => trim($entry['src']),
            'dst' => $entry['dst'],
            'type' => $entry['type'],
            'can' => $entry['can'],
            'mer' => number_format((float)$entry['mer'], 2, '.', '') ?? NULL,
            'duration' => "", // RF entries don't have duration
            'hash' => $entryHash
        ];

        $newEntries[] = $displayEntry;
        $processedEntries[] = $displayEntry;
        continue;
    }

    // Handle Internet entries
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

            // Prepare entry for display
            $displayEntry = [
                'time' => date('Y-m-d H:i:s', strtotime($entry['time'])),
                'timestamp' => strtotime($entry['time']), // Add timestamp for sorting
                'src' => trim($entry['src']),
                'dst' => $entry['dst'],
                'type' => $entry['type'],
                'can' => $entry['can'],
                'mer' => $entry['mer'] ?? null,
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

