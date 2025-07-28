var prev_linescount = 0;
var bufferedEntry = null;

function load_file(file_path)
{
    var result = null;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", file_path, false);
    xmlhttp.send();
    if(xmlhttp.status == 200)
    {
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
        return isoString; // Fallback
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

function data_append() {
    var contents = load_file(gateway_log_file);
    if (!contents) return;

    var lines = contents.trim().split(/\r\n|\r|\n/);
    var linescount = lines.length;

    if (linescount === 0) {
        prev_linescount = 0;
    }

    if (linescount > prev_linescount) {
        var table = document.getElementById("lastheard");
        var newLines = lines.slice(prev_linescount);

        var i = 0;

        // If there's a buffered entry, try to pair it with the first new line
        if (bufferedEntry && newLines.length > 0) {
            try {
                var nextEntry = JSON.parse(newLines[0]);
                var isVoicePair = bufferedEntry.src === nextEntry.src &&
                                  bufferedEntry.subtype === "Voice Start" &&
                                  nextEntry.subtype === "Voice End";

                if (isVoicePair) {
                    var row = table.insertRow(1);
                    row.insertCell(0).innerHTML = escapeHtml(formatTime(bufferedEntry.time)) || "";
                    row.insertCell(1).innerHTML = escapeHtml(bufferedEntry.src) || "";
                    row.insertCell(2).innerHTML = escapeHtml(bufferedEntry.dst) || "";
                    row.insertCell(3).innerHTML = escapeHtml(bufferedEntry.type) || "";
                    row.insertCell(4).innerHTML = escapeHtml(bufferedEntry.can) !== undefined ? bufferedEntry.can : "";
                    row.insertCell(5).innerHTML = escapeHtml(bufferedEntry.mer !== undefined ? bufferedEntry.mer.toFixed(2) : "");
                    row.insertCell(6).innerHTML = calculateDuration(bufferedEntry.time, nextEntry.time);

                    i = 1; // skip the next line (already used)
                    bufferedEntry = null;
                } else {
                    // Couldn't pair, just render it and continue
                    var row = table.insertRow(1);
                    row.insertCell(0).innerHTML = escapeHtml(formatTime(bufferedEntry.time)) || "";
                    row.insertCell(1).innerHTML = escapeHtml(bufferedEntry.src) || "";
                    row.insertCell(2).innerHTML = escapeHtml(bufferedEntry.dst) || "";
                    row.insertCell(3).innerHTML = escapeHtml(bufferedEntry.type) || "";
                    row.insertCell(4).innerHTML = escapeHtml(bufferedEntry.can) !== undefined ? bufferedEntry.can : "";
                    row.insertCell(5).innerHTML = escapeHtml(bufferedEntry.mer !== undefined ? bufferedEntry.mer.toFixed(2) : "");
                    row.insertCell(6).innerHTML = "";
                    bufferedEntry = null;
                }
            } catch (e) {
                console.error("Error parsing buffered pair: ", newLines[0], e);
                bufferedEntry = null;
            }
        }

        // Process the rest of the new lines
        for (; i < newLines.length; i++) {
            try {
                var entry = JSON.parse(newLines[i]);
                var nextEntry = (i + 1 < newLines.length) ? JSON.parse(newLines[i + 1]) : null;

                var isVoicePair = nextEntry &&
                                  entry.src === nextEntry.src &&
                                  entry.subtype === "Voice Start" &&
                                  nextEntry.subtype === "Voice End";

                if (isVoicePair) {
                    var row = table.insertRow(1);
                    row.insertCell(0).innerHTML = escapeHtml(formatTime(entry.time)) || "";
                    row.insertCell(1).innerHTML = escapeHtml(entry.src) || "";
                    row.insertCell(2).innerHTML = escapeHtml(entry.dst) || "";
                    row.insertCell(3).innerHTML = escapeHtml(entry.type) || "";
                    row.insertCell(4).innerHTML = escapeHtml(entry.can) !== undefined ? entry.can : "";
                    row.insertCell(5).innerHTML = escapeHtml(entry.mer !== undefined ? entry.mer.toFixed(2) : "");
                    row.insertCell(6).innerHTML = calculateDuration(entry.time, nextEntry.time);

                    i++; // skip the next line
                } else if (entry.subtype === "Voice Start") {
                    // Buffer for next round
                    bufferedEntry = entry;
                } else {
                    var row = table.insertRow(1);
                    row.insertCell(0).innerHTML = escapeHtml(formatTime(entry.time)) || "";
                    row.insertCell(1).innerHTML = escapeHtml(entry.src) || "";
                    row.insertCell(2).innerHTML = escapeHtml(entry.dst) || "";
                    row.insertCell(3).innerHTML = escapeHtml(entry.type) || "";
                    row.insertCell(4).innerHTML = escapeHtml(entry.can) !== undefined ? entry.can : "";
                    row.insertCell(5).innerHTML = escapeHtml(entry.mer !== undefined ? entry.mer.toFixed(2) : "");
                    row.insertCell(6).innerHTML = "";
                }

                while (table.rows.length > 16) {
                    table.deleteRow(table.rows.length - 1);
                }

            } catch (e) {
                console.error("Parse error in line: ", newLines[i], e);
            }
        }

        prev_linescount = linescount;
    }

    setTimeout(data_append, 1000);
}


// Add "Duration" column header 
document.addEventListener("DOMContentLoaded", function() {
    var table = document.getElementById("lastheard");
    if (table && table.rows.length > 0 && table.rows[0].cells.length === 6) {
        table.rows[0].insertCell(6).innerHTML = "Duration";
    }
});

setTimeout(data_append, 10);

