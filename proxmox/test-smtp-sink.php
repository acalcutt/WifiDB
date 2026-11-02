<?php
// A stand-in mail server for the tests (the boot tests' test-mail.inc.sh, tests/Unit/WdbmailDkimTest.php): it takes
// every message, with any login or none, and writes each to a file in the folder it's given, as it was received.
//   php test-smtp-sink.php <port> <folder>     port 0: any free one, printed on the first line
// One connection at a time, which is all the tests need. No STARTTLS.
$port = isset($argv[1]) ? (int)$argv[1] : 2525;
$dir = isset($argv[2]) ? $argv[2] : '/tmp/smtp-sink';
if (!is_dir($dir)) { mkdir($dir, 0777, true); }
$server = stream_socket_server("tcp://127.0.0.1:$port", $errno, $errstr);
if (!$server) { fwrite(STDERR, "$errstr\n"); exit(1); }
$name = stream_socket_get_name($server, false);
echo substr($name, strrpos($name, ':') + 1), "\n";
$n = 0;
while ($c = @stream_socket_accept($server, -1)) {
    stream_set_timeout($c, 30);
    $say = function ($line) use ($c) { fwrite($c, $line . "\r\n"); };
    $say('220 sink.test ESMTP');
    $data = null;
    $auth = null;
    while (($line = fgets($c)) !== false) {
        $line = rtrim($line, "\r\n");
        if ($data !== null) {
            if ($line === '.') {
                file_put_contents(sprintf('%s/%03d.eml', $dir, ++$n), $data);
                $data = null;
                $say('250 OK queued');
            } else {
                $data .= (strncmp($line, '..', 2) === 0 ? substr($line, 1) : $line) . "\r\n";
            }
            continue;
        }
        if ($auth !== null) {   // the login, taken whatever it is
            if ($auth === 'user') { $auth = 'pass'; $say('334 UGFzc3dvcmQ6'); }
            else { $auth = null; $say('235 2.7.0 Accepted'); }
            continue;
        }
        $parts = explode(' ', $line);
        switch (strtoupper($parts[0])) {
            case 'EHLO': fwrite($c, "250-sink.test\r\n250-AUTH PLAIN LOGIN\r\n250 8BITMIME\r\n"); break;
            case 'HELO': $say('250 sink.test'); break;
            case 'AUTH':
                $mech = isset($parts[1]) ? strtoupper($parts[1]) : '';
                if ($mech === 'PLAIN' && isset($parts[2])) { $say('235 2.7.0 Accepted'); }
                elseif ($mech === 'PLAIN') { $auth = 'pass'; $say('334 '); }
                elseif ($mech === 'LOGIN') { $auth = 'user'; $say('334 VXNlcm5hbWU6'); }
                else { $say('504 Unrecognized authentication type'); }
                break;
            case 'MAIL': case 'RCPT': case 'RSET': case 'NOOP': $say('250 OK'); break;
            case 'DATA': $data = ''; $say('354 End data with <CR><LF>.<CR><LF>'); break;
            case 'QUIT': $say('221 Bye'); break 2;
            default: $say('502 Command not implemented');
        }
    }
    fclose($c);
}
