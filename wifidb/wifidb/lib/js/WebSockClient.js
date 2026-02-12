var socket, currentRequest;

function init() {
    console.log(host);
    var last = host.substring(host.lastIndexOf("/") + 1, host.length);
    connect(last);
}

function connect(last) {
    var tries = 0;
    try {

        socket = new WebSocket(host);

        socket.onopen    = function() {
            console.log(last);
            if(last == 'LiveAPs')
            {
                LiveLoop();
            }else if(last == 'Scheduling')
            {
                ScheduleLoop();
            }
        };

        socket.onmessage = function(msg) {
            xmlDoc = $.parseXML( msg.data );
            $xml = $( xmlDoc );

            $search1 = $xml.find( "notice");
            if($search1.length > 0)
            {
                // notice
            }
            $search2 = $xml.find( "error");
            if($search2.length > 0)
            {
                // error
            }

            var CurrentTable = createTable(currentRequest, "10");
            switch(currentRequest)
            {
                case "import_waiting":
                    clearTable(currentRequest, 4);
                    parseImportWaiting(msg.data, CurrentTable);
                    currentRequest = "import_active";
                    send('import_active');
                    break;
                case "import_active":
                    clearTable(currentRequest, 4);
                    parseImportActive(msg.data, CurrentTable);
                    currentRequest = "daemon_stats";
                    send('daemon_stats');
                    break;
                case "daemon_stats":
                    clearTable(currentRequest, 4);
                    parseDaemonStats(msg.data, CurrentTable);
                    currentRequest = "daemon_schedule";
                    send('daemon_schedule');
                    break;
                case "daemon_schedule":
                    clearTable(currentRequest, 4);
                    parseDaemonSchedule(msg.data, CurrentTable);
                    break;
                case "LiveList":
                    clearTable(currentRequest, 2);
                    parseLiveList(msg.data, CurrentTable);
                    break;
                default:
                    console.log(currentRequest + "< ---- >"+msg.data);
                    break;
            }

        };
        socket.onclose   = function() {
            if(tries>=30)
            {
                return;
            }
            reconnect();
            tries++;
        };
    }
    catch(ex){
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
    } catch(ex) {
    }
}

function quit(){
    if (socket != null) {
        socket.close();
        socket=null;
    }
}

function reconnect() {
    quit();
    connect();
}

/* Remaining helper parsing functions are expected to exist in the page scope */
