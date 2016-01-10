import signal, sys, ssl
import socket, struct, hashlib, threading, cgi
from optparse import OptionParser

parser = OptionParser(usage="usage: %prog [options]", version="%prog 1.0")
parser.add_option("--host", default='172.31.5.89', type='string', action="store", dest="host", help="hostname (localhost)")
parser.add_option("--port", default=9000, type='int', action="store", dest="port", help="port (8000)")
parser.add_option("--ssl",  action="store", default=False, help="--ssl (set argument to use ssl)" )
parser.add_option("--cert", default='./cert.pem', type='string', action="store", dest="cert", help="cert (./cert.pem)")
parser.add_option("--ver", default=ssl.PROTOCOL_TLSv1_2, type='int', action="store", dest="ver", help="ssl version")
(options, args) = parser.parse_args()
#!/usr/bin/env python

def decode_key (key):
	num = ""
	spaces = 0
	for c in key:
		if c.isdigit():
			num += c
	 	if c.isspace():
			spaces += 1
	return int(num) / spaces

def create_hash (key1, key2, code):
	a = struct.pack(">L", decode_key(key1))
	b = struct.pack(">L", decode_key(key2))
	md5 = hashlib.md5(a + b + code)
	return md5.digest()

def recv_data (client, length):
	data = client.recv(length)
	if not data: return data
	return data.decode('utf-8', 'ignore')

def send_data (client, data):
	message = "\x00%s\xFF" % data.encode('utf-8')
	return client.send(message)

def parse_headers (data):
	headers = {}
	lines = data.splitlines()
	for l in lines:
		parts = l.split(": ", 1)
		if len(parts) == 2:
			headers[parts[0]] = parts[1]
	headers['code'] = lines[len(lines) - 1]
	return headers

def handshake (client):
    print 'Handshaking...'
    data = client.recv(1024)
    headers = parse_headers(data)

    print 'Got headers:'
    for k, v in headers.iteritems():
        print k, ':', v
    digest = create_hash(
		'Sec-WebSocket-Key1',
		'Sec-WebSocket-Key2',
		headers['code']
	)
    shake = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n"
    shake += "Upgrade: WebSocket\r\n"
    shake += "Connection: Upgrade\r\n"
    shake += "Sec-WebSocket-Origin: %s\r\n" % (headers['Origin'])
    shake += "Sec-WebSocket-Location: ws://%s/stuff\r\n" % (headers['Host'])
    shake += "Sec-WebSocket-Protocol: sample\r\n\r\n"
    shake += digest
    return client.send(shake)

def handle (client, addr):
	handshake(client)
	lock = threading.Lock()
	while 1:
		data = recv_data(client, 1024)
		if not data: break
		data = cgi.escape(data)
		lock.acquire()
		[send_data(c, data) for c in clients]
		lock.release()
	print 'Client closed:', addr
	lock.acquire()
	clients.remove(client)
	lock.release()
	client.close()

def start_server ():

    if options.ssl == 1:
        context = ssl.Context(ssl.PROTOCOL_TLSv1_2)
        context.use_privatekey_file('./certs/cert.key')
        context.use_certificate_file('./certs/cert.cert')

        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s = ssl.Connection(context, s)
        s.bind(('ip-172-31-5-89', 9000))
        s.listen(5)

        (connection, address) = s.accept()
        while True:
            print repr(connection.recv(65535))
    else:
        s = socket.socket()
        s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        s.bind(('ip-172-31-5-89', 9000))
        s.listen(5)
        while 1:
            conn, addr = s.accept()
            print 'Connection from:', addr
            clients.append(conn)
            threading.Thread(target = handle, args = (conn, addr)).start()

clients = []
start_server()
