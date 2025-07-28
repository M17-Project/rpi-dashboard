const STORAGE_KEY = "gateway_log_entries";
const MAX_ENTRIES = 20;

function load_file(file_path) {
    var result = null;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", file_path, false);
    xmlhttp.send();
    if (xmlhttp.status == 200) {
        result = xmlhttp.responseText;
    }
    return result;
}

function escapeHtml(text) {
    if (typeof text === 'string' || text instanceof String) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    } else {
        return text;
    }
}

function formatTime(isoString) {
    try {
        var date = new Date(isoString);
        var day = String(date.getDate()).padStart(2, '0');
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var year = date.getFullYear();
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        var seconds = String(date.getSeconds()).padStart(2, '0');
        return `${day}.${month}.${year} ${hours}:${minutes}:${seconds}`;
    } catch (e) {
        return isoString;
    }
}

function calculateDuration(start, end) {
    try {
        let t1 = new Date(start);
        let t2 = new Date(end);
        let diffMs = t2 - t1;
        let seconds = Math.floor(diffMs / 1000);
        return seconds + "s";
    } catch (e) {
        return "";
    }
}

// Load entries from localStorage
function loadStoredEntries() {
    let entries = localStorage.getItem(STORAGE_KEY);
    return entries ? JSON.parse(entries) : [];
}

// Save entries to localStorage (only keep MAX_ENTRIES last)
function saveStoredEntries(entries) {
    if (entries.length > MAX_ENTRIES) {
        entries = entries.slice(entries.length - MAX_ENTRIES);
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(entries));
}

// Add a new parsed entry or pair to localStorage
// This handles combining voice start + voice end into one
function addEntriesToStorage(newLines) {
    let storedEntries = loadStoredEntries();

    // Convert storedEntries to a map to easily find unpaired voice starts if needed
    // But here we process lines in order, so just process sequentially:

    for (let i = 0; i < newLines.length; i++) {
        try {
            let entry = JSON.parse(newLines[i]);
            let nextEntry = (i + 1 < newLines.length) ? JSON.parse(newLines[i + 1]) : null;

            if (
                nextEntry &&
                entry.src === nextEntry.src &&
                entry.subtype === "Voice Start" &&
                nextEntry.subtype === "Voice End"
            ) {
                // Combine entries
                let combinedEntry = {
                    time: entry.time,
                    src: entry.src,
                    dst: entry.dst,
                    type: entry.type,
                    can: entry.can,
                    mer: entry.mer,
                    duration: calculateDuration(entry.time, nextEntry.time),
                    subtype: "Voice Call"
                };
                storedEntries.push(combinedEntry);
                i++; // skip next line
            } else {
                // Normal entry, push as is but remove subtype "Voice Start" or "Voice End" so UI stays clean
                let e = Object.assign({}, entry);
                if (e.subtype === "Voice Start" || e.subtype === "Voice End") {
                    e.subtype = "Voice Event";
                }
                e.duration = ""; // no duration for single events
                storedEntries.push(e);
            }
        } catch (ex) {
            console.error("Error parsing line for storage:", newLines[i], ex);
        }
    }

    saveStoredEntries(storedEntries);
    //populateTableFromStorage();
}

function populateTableFromStorage() {
    const table = document.getElementById("lastheard");
    if (!table) return;

    // Clear all rows except header
    while (table.rows.length > 1) {
        table.deleteRow(1);
    }

    let entries = loadStoredEntries();

    // Filter out individual voice events except if type is "RF"
    entries = entries.filter(e => !(e.subtype === "Voice Event" && e.type !== "RF"));

    // Sort descending by time (newest first)
    entries.sort((a, b) => new Date(a.time) - new Date(b.time));

    for (let i = 0; i < entries.length; i++) {
        let entry = entries[i];

        // Insert each new row at index 1 (directly below header) to keep newest on top
        let row = table.insertRow(1);
        let cell1 = row.insertCell(0);
        let cell2 = row.insertCell(1);
        let cell3 = row.insertCell(2);
        let cell4 = row.insertCell(3);
        let cell5 = row.insertCell(4);
        let cell6 = row.insertCell(5);
        let cell7 = row.insertCell(6);

        cell1.innerHTML = escapeHtml(formatTime(entry.time)) || "";
        cell2.innerHTML = escapeHtml(entry.src) || "";
        cell3.innerHTML = escapeHtml(entry.dst) || "";
        cell4.innerHTML = escapeHtml(entry.type || entry.subtype) || "";
        cell5.innerHTML = (entry.can !== undefined) ? escapeHtml(entry.can) : "";
        cell6.innerHTML = (entry.mer !== undefined && typeof entry.mer === "number") ? escapeHtml(entry.mer.toFixed(2)) : "";
        cell7.innerHTML = escapeHtml(entry.duration || "");
    }
}


let prevLinesCount = 0;

// Main polling loop
function monitorLogFile() {
    const contents = load_file(gateway_log_file);
    if (!contents) {
        setTimeout(monitorLogFile, 1000);
        return;
    }

    let lines = contents.trim().split(/\r\n|\r|\n/);
    if (lines.length > prevLinesCount) {
        // Get new lines only
        let newLines = lines.slice(prevLinesCount);

        // Add new lines to localStorage (with pairing)
        addEntriesToStorage(newLines);

        // Update the table from storage
        populateTableFromStorage();

        prevLinesCount = lines.length;
    }

    setTimeout(monitorLogFile, 1000);
}

document.addEventListener("DOMContentLoaded", () => {

    // Load any existing stored entries into the table on startup
    populateTableFromStorage();

    // Start monitoring
    setTimeout(monitorLogFile, 1000);
});

