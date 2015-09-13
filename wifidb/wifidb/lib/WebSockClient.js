var socket, currentRequest;

function init() {
    connect();
    myLoop();
}

function connect() {
    var tries = 0;
    try {
        socket = new WebSocket(host);
        //console.log('WebSocket - status '+socket.readyState);

        socket.onopen    = function() {
            //console.log("Connected - status "+this.readyState);
            currentRequest = "import_waiting";
            send('import_waiting');
        };

        socket.onmessage = function(msg) {
            //console.log("Returned Message: "+msg.data);
            //clearTable();
            xmlDoc = $.parseXML( msg.data );
            $xml = $( xmlDoc );

            $search1 = $xml.find( "notice");
            //console.log($search1.length);
            if($search1.length > 0)
            {
                //return;
                //alert("Notice in "+currentRequest);
            }
            $search2 = $xml.find( "error");
            //console.log($search2.length);
            if($search2.length > 0)
            {
                //return;
                //alert("Error in "+currentRequest);
            }

            //$search2 = $xml.find("import_active");
            //console.log("Current Request: " + currentRequest);
            var CurrentTable = createTable(currentRequest, "10");
            clearTable(currentRequest);
            switch(currentRequest)
            {
                case "import_waiting":
                    parseImportWaiting(msg.data, CurrentTable);
                    currentRequest = "import_active";
                    send('import_active');
                    break;
                case "import_active":
                    parseImportActive(msg.data, CurrentTable);
                    currentRequest = "daemon_stats";
                    send('daemon_stats');
                    break;
                case "daemon_stats":
                    parseDaemonStats(msg.data, CurrentTable);
                    currentRequest = "daemon_schedule";
                    send('daemon_schedule');
                    break;
                case "daemon_schedule":
                    parseDaemonSchedule(msg.data, CurrentTable);
                    break;
                default:
                   // console.log(currentRequest + "< ---- >"+msg.data);
                    break;
            }
        };
        socket.onclose   = function() {
           // console.log("Disconnected - status "+this.readyState);
            if(tries>=30)
            {
                return;
            }
           // console.log("Re-Connecting (try "+tries+" of 30)...."+this.readyState);
            reconnect();
            tries++;
        };
    }
    catch(ex){
       // console.log("WebSockets Not Supported.");
        window.location.replace("/wifidb/opt/scheduling.php?func=old_schedule");
    }
}

function send(msg){
    if(!msg) {
        alert("Message can not be empty");
        return;
    }
    try {
        socket.send(msg);
       // console.log('Sent: '+msg);
    } catch(ex) {
        //console.log(ex);
    }
}

function quit(){
    if (socket != null) {
        //log("Goodbye!");
        socket.close();
        socket=null;
    }
}

function reconnect() {
    quit();
    connect();
}

function parseImportWaiting(response, WaitingTable) {

    var $waiting, $search1, xmlDoc, $xml, loop;
    // no matches returned
    if (response == null) {
        return false;
    } else {
        xmlDoc = $.parseXML( response ),
        $xml = $( xmlDoc ),
        $search1 = $xml.find(currentRequest);
        $waiting = $search1[0];
        //console.log($waiting);
        if ($waiting.childNodes.length > 0) {
            WaitingTable.setAttribute("bordercolor", "black");
            WaitingTable.setAttribute("border", "1");
            for (loop = 0; loop < $waiting.childNodes.length; loop++) {
                var file = $waiting.childNodes[loop];
               // console.log(file.childNodes.length);
                if(file.childNodes.length == 1)
                {
                    //create row
                    //WaitingTable.style.display = 'table';
                    var row = document.createElement("tr");
                    row.setAttribute("style", "background-color: yellow");
                    CreateCell(WaitingTable, row, "No Imports Waiting. Go import some...", 7) // File ID

                }else
                {
                    //create row
                    //WaitingTable.style.display = 'table';
                    var row = document.createElement("tr");
                    row.setAttribute("style", "background-color: yellow");
                    CreateCell(WaitingTable, row, file.childNodes[0].innerHTML) // File ID
                    CreateCell(WaitingTable, row, file.childNodes[1].innerHTML) // FileName
                    CreateCell(WaitingTable, row, file.childNodes[2].innerHTML) // Username
                    CreateCell(WaitingTable, row, file.childNodes[3].innerHTML) // Title
                    CreateCell(WaitingTable, row, file.childNodes[4].innerHTML) // Size
                    CreateCell(WaitingTable, row, file.childNodes[5].innerHTML) // Date/Time
                    CreateCell(WaitingTable, row, file.childNodes[6].innerHTML) // Hash
                }
            }
        }
        return true;
    }
}

function parseImportActive(response, ActiveTable) {

    var $active, $active, $search1, xmlDoc, $xml, loop, row, cell;
    // no matches returned
    if (response == null) {
        return false;
    } else {
        xmlDoc = $.parseXML( response ),
            $xml = $( xmlDoc ),
            $search1 = $xml.find(currentRequest);
        $active = $search1[0];
        if ($active.childNodes.length > 0) {
            //console.log("Active Response Length: "+$active.childNodes.length);
            ActiveTable.setAttribute("bordercolor", "black");
            ActiveTable.setAttribute("border", "1");
            for (loop = 0; loop < $active.childNodes.length; loop++) {
                //console.log(loop);
                var file = $active.childNodes[loop];
                if(file.childNodes.length == 1)
                {
                    row = document.createElement("tr");
                    row.setAttribute("style", "background-color: yellow");
                    CreateCell(ActiveTable, row, "No Active Imports yet, Daemon needs to rest a little bit. Poor thing works too hard.", 9) // File ID

                }else
                {
                    row = document.createElement("tr");
                    row.setAttribute("style", "background-color: lime");
                    CreateCell(ActiveTable, row, file.childNodes[0].innerHTML) // File ID
                    CreateCell(ActiveTable, row, file.childNodes[1].innerHTML) // FileName
                    CreateCell(ActiveTable, row, file.childNodes[2].innerHTML) // Username
                    CreateCell(ActiveTable, row, file.childNodes[3].innerHTML) // Title
                    CreateCell(ActiveTable, row, file.childNodes[4].innerHTML) // Size
                    CreateCell(ActiveTable, row, file.childNodes[5].innerHTML) // Date/Time
                    CreateCell(ActiveTable, row, file.childNodes[6].innerHTML) // Hash
                    CreateCell(ActiveTable, row, file.childNodes[7].innerHTML) // Current AP
                    CreateCell(ActiveTable, row, file.childNodes[8].innerHTML) // AP of Total APs

                }
            }
        }
        return true;
    }
}

function parseDaemonStats(response, DaemonStatsTable) {
    if (response == null) {
        return false;
    } else {
        //console.log(response);
        xmlDoc = $.parseXML(response),
            $xml = $(xmlDoc),
            $search1 = $xml.find(currentRequest);
        var $Stats = $search1[0];
       // console.log($Stats.childNodes);
        if ($Stats.childNodes.length > 1) {

//            for (loop = 0; loop < $Stats.childNodes.length; loop++) {
//              //  console.log(loop);
                var file = $Stats.childNodes;
//                DaemonStatsTable.style.display = 'table';

                var row = document.createElement("tr");
                row.setAttribute("colspan", "7");
                row.setAttribute("style", "background-color: " + file[7].innerHTML);

                CreateCell(DaemonStatsTable, row, file[0].innerHTML) // Node
                CreateCell(DaemonStatsTable, row, file[1].innerHTML) // PID File
                CreateCell(DaemonStatsTable, row, file[2].innerHTML) // PID
                CreateCell(DaemonStatsTable, row, file[3].innerHTML) // Time
                CreateCell(DaemonStatsTable, row, file[4].innerHTML) // Memory
                CreateCell(DaemonStatsTable, row, file[5].innerHTML) // Command
                CreateCell(DaemonStatsTable, row, file[6].innerHTML) // Updated
//            }
        }else
        {
            var row = document.createElement("tr");
            row.setAttribute("style", "background-color: yellow");
            CreateCell(DaemonStatsTable, row, "Daemons are not running, how odd...", 7) // Node
        }
    }
}

function parseDaemonSchedule(response, DaemonScheduleTable) {
    if (response == null) {
        return false;
    } else {
        var xmlDoc = $.parseXML(response),
            $xml = $(xmlDoc),
            $search1 = $xml.find(currentRequest);
        var $Stats = $search1[0];
        if ($Stats.childNodes.length > 1) {

            for (var loop = 0; loop < $Stats.childNodes.length; loop++) {
                //console.log(loop);
                var file = $Stats.childNodes[loop];
                //console.log(file.childNodes[0]);
                //create row
                var row = document.createElement("tr");
                if(file.childNodes[3].innerHTML === "Running")
                {
                    row.setAttribute("bgcolor", "lime");
                }else
                {
                    row.setAttribute("style", "background-color: yellow");
                }
                row.setAttribute("colspan", "7");

                CreateCell(DaemonScheduleTable, row, file.childNodes[0].innerHTML) // Node
                CreateCell(DaemonScheduleTable, row, file.childNodes[1].innerHTML) // Daemon
                CreateCell(DaemonScheduleTable, row, file.childNodes[2].innerHTML) // Interval
                CreateCell(DaemonScheduleTable, row, file.childNodes[3].innerHTML) // Status

                var pad = "00";
                var UTCDate = new Date(file.childNodes[4].innerHTML);
                //console.log(UTCDate);
                //console.log(UTCDate.getMonth()+1);
                var month_pre = "" + (UTCDate.getMonth()+1); //WTF JavaScript, why is the Month off by one? January is 0 WTF... Seriously...
                var month = pad.substring(0, pad.length - month_pre.length) + month_pre;

                var day_pre = "" + UTCDate.getDate();
                var day = pad.substring(0, pad.length - day_pre.length) + day_pre;

                var hours_pre = "" + UTCDate.getHours();
                var hours = pad.substring(0, pad.length - hours_pre.length) + hours_pre;

                var min_pre = "" + UTCDate.getMinutes();
                var min = pad.substring(0, pad.length - min_pre.length) + min_pre;

                var sec_pre = "" + UTCDate.getSeconds();
                var sec = pad.substring(0, pad.length - sec_pre.length) + sec_pre;

                var UTCDateString = UTCDate.getFullYear()+"-"+month+"-"+day+" "+hours+":"+min+":"+sec;
                var tz_cookie = readCookie('wifidb_client_timezone');
                if(tz_cookie === "zero")
                {
                    tz_cookie = 0;
                }
                var offset_cal = (3600000 * (tz_cookie));
                var timestamp = UTCDate.getTime();

                //console.log("Offset_Calc: "+ offset_cal);
                //console.log("TimeStamp SQL: "+ timestamp );

                var LocalDate = new Date((timestamp) + offset_cal);

                var month_pre = "" + (LocalDate.getMonth()+1); // Again WTF Javascript...
                var month = pad.substring(0, pad.length - month_pre.length) + month_pre;

                var day_pre = "" + LocalDate.getDate();
                var day = pad.substring(0, pad.length - day_pre.length) + day_pre;

                var hours_pre = "" + LocalDate.getHours();
                var hours = pad.substring(0, pad.length - hours_pre.length) + hours_pre;

                var min_pre = "" + LocalDate.getMinutes();
                var min = pad.substring(0, pad.length - min_pre.length) + min_pre;

                var sec_pre = "" + LocalDate.getSeconds();
                var sec = pad.substring(0, pad.length - sec_pre.length) + sec_pre;
                var LocalDateString = LocalDate.getFullYear()+"-"+month+"-"+day+" "+hours+":"+min+":"+sec;
                CreateCell(DaemonScheduleTable, row, UTCDateString) // NextUTC
                CreateCell(DaemonScheduleTable, row, LocalDateString) // NextLocal
            }
        }else
        {
            var row = document.createElement("tr");
            row.setAttribute("style", "background-color: yellow");
            CreateCell(DaemonScheduleTable, row, "Well thats not good, there are not Daemons scheduled to run...", 7) // Node

        }
    }
}

function readCookie(name) {
    return (name = new RegExp('(?:^|;\\s*)' + ('' + name).replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&') + '=([^;]*)').exec(document.cookie)) && name[1];
}

function CreateCell(table, row, data, colspan) {
    if(!data)
    {
        return;
    }
    if(!colspan)
    {
        colspan = 1;
    }
    var cell;
    //console.log("Data: "+ data);
    cell = document.createElement("td");
    cell.setAttribute("align", "center");
    cell.setAttribute("colspan", colspan);
    cell.appendChild(document.createTextNode(data));

    row.appendChild(cell);
    table.appendChild(row);
}

function createTable(tableName, span)
{
    var td, tr, autoRow;
    //console.log("tableName: "+tableName);
    tr = document.createElement("tr");
    //tr.setAttribute("width", "100%");
    autoRow = document.getElementById(tableName);
    //console.log(autoRow.childNodes);
    td = document.createElement("td");
    td.setAttribute("colspan", span);
    tr.appendChild(td);
    autoRow.appendChild(tr);

    return autoRow;
}

function clearTable(tableName) {
    var table_to_clean;

    table_to_clean = document.getElementById(tableName);
    //console.log(tableName+" Length: "+table_to_clean.childNodes.length);
    if (table_to_clean.childNodes.length > 0) {
        for (loop = table_to_clean.childNodes.length -1; loop >= 0 ; loop--) {
            if(loop < 4)
            {
                continue;
            }
            table_to_clean.removeChild(table_to_clean.childNodes[loop]);
        }
    }
}

function myLoop () {           //  create a loop function
    setInterval(function () {    //  call a 3s setTimeout when the loop is called
        send('import_waiting');          //  your code here
        currentRequest = "import_waiting";
    }, 2000);
}