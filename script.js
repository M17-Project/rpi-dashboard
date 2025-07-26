function load_file(file_path)
{
    var result = null;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", file_path, false);
    xmlhttp.send();
    if(xmlhttp.status==200)
    {
        result = xmlhttp.responseText;
    }
    return result;
}

function data_append() {
    var contents = load_file("files/dashboard.log");
    var lines = contents.trim().split(/\r\n|\r|\n/);
    var linescount = lines.length;

    if (linescount === 0) {
        prev_linescount = 0;
    }

    if (linescount > prev_linescount) {
        for (var i = prev_linescount; i < linescount; i++) {
            try {
                var entry = JSON.parse(lines[i]);

                var table = document.getElementById("lastheard");
                var row = table.insertRow(1);
                var cell1 = row.insertCell(0);
                var cell2 = row.insertCell(1);
                var cell3 = row.insertCell(2);
                var cell4 = row.insertCell(3);
                var cell5 = row.insertCell(4);
                var cell6 = row.insertCell(5);

                cell1.innerHTML = formatTime(entry.time) || "";
                cell2.innerHTML = entry.src || "";
                cell3.innerHTML = entry.dst || "";
                cell4.innerHTML = entry.type || "";
                cell5.innerHTML = entry.can !== undefined ? entry.can : "";
                cell6.innerHTML = ""; // MER not there yet

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

var prev_linescount=0;
setTimeout(data_append, 10);
