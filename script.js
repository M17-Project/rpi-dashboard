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

        for (var i = prev_linescount; i < linescount; i++) {
            try {
                var entry = JSON.parse(lines[i]);
                var nextEntry = (i + 1 < linescount) ? JSON.parse(lines[i + 1]) : null;
                var isVoicePair = nextEntry &&
                                  entry.src === nextEntry.src &&
                                  entry.subtype === "Voice Start" &&
                                  nextEntry.subtype === "Voice End";

                var row = table.insertRow(1);
                var cell1 = row.insertCell(0); // time
                var cell2 = row.insertCell(1); // src
                var cell3 = row.insertCell(2); // dst
                var cell4 = row.insertCell(3); // type
                var cell5 = row.insertCell(4); // can
                var cell6 = row.insertCell(5); // mer
                var cell7 = row.insertCell(6); // duration

                cell1.innerHTML = escapeHtml(formatTime(entry.time)) || "";
                cell2.innerHTML = escapeHtml(entry.src) || "";
                cell3.innerHTML = escapeHtml(entry.dst) || "";
                cell4.innerHTML = escapeHtml(entry.type) || "";
                cell5.innerHTML = escapeHtml(entry.can) !== undefined ? entry.can : "";
                cell6.innerHTML = escapeHtml(entry.mer !== undefined ? entry.mer.toFixed(2) : "");
                cell7.innerHTML = isVoicePair ? calculateDuration(entry.time, nextEntry.time) : "";

                if (isVoicePair) {
                    i++; // skip the next line since we used it
                }

                while (table.rows.length > 16 ) {
                    table.deleteRow(table.rows.length - 1);
                }
            } catch (e) {
                console.error("Parse error in line: ", lines[i], e);
            }
        }

        prev_linescount = linescount;
    }

    setTimeout(data_append, 1000);
}

// Add "Duration" column header (you need this if your table header is static HTML)
document.addEventListener("DOMContentLoaded", function() {
    var table = document.getElementById("lastheard");
    if (table && table.rows.length > 0 && table.rows[0].cells.length === 6) {
        table.rows[0].insertCell(6).innerHTML = "Duration";
    }
});

var prev_linescount = 0;
setTimeout(data_append, 10);

