from autobahn.asyncio.websocket import WebSocketServerProtocol

class MyServerProtocol(WebSocketServerProtocol):

   def onMessage(self, payload, isBinary):
      ## echo back message verbatim
      s = payload.decode('utf8')
      print(s)
      s.encode('utf8')
      #self.sendMessage(payload, isBinary)


if __name__ == '__main__':

   import sys

   from twisted.python import log
   from twisted.internet import reactor
   log.startLogging(sys.stdout)

   from autobahn.twisted.websocket import WebSocketServerFactory
   factory = WebSocketServerFactory()
   factory.protocol = MyServerProtocol

   reactor.listenTCP(9000, factory)
   reactor.run()



   from xml.etree.ElementTree import Element, SubElement, Comment, tostring

   top = Element('top')

   child = SubElement(top, 'child')
   child.text = 'This child contains text.'

   print(tostring(top))