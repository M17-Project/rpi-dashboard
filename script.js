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

function data_append()
{
    var contents=load_file("files/log.txt");
    var linescount = contents.split(/\r\n|\r|\n/).length-1;
    
    if(linescount==0)
        prev_linescount=0;

    if(linescount>prev_linescount)
    {
        for(i=prev_linescount; i<linescount; i++)
        {
            var line = contents.split("\n")[i];

            var table = document.getElementById("lastheard");
            var row = table.insertRow(1);
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);
            var cell4 = row.insertCell(3);
            var cell5 = row.insertCell(4);
            var cell6 = row.insertCell(5);

            cell1.innerHTML = line.split("\"")[1];
            cell2.innerHTML = line.split("\"")[3];
            cell3.innerHTML = line.split("\"")[5];
            cell4.innerHTML = line.split("\"")[7];
            cell5.innerHTML = line.split("\"")[9];
            cell6.innerHTML = line.split("\"")[11];
        }

        prev_linescount=linescount;
    }

    setTimeout(data_append, 1000);
}

var prev_linescount=0;
setTimeout(data_append, 10);
