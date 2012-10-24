"""

  Copyright (C) Alexandros Kanterakis
 
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.
 
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.
 
  You should have received a copy of the GNU General Public License along
  with this program; if not, write to the Free Software Foundation, Inc.,
  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
  http://www.gnu.org/copyleft/gpl.html
 
"""

"""
This is a standalone web server that takes the POST request from
pypedia.js

because of this issue:
http://stackoverflow.com/questions/2099728/how-do-i-send-an-ajax-request-on-a-different-port-with-jquery

It requires to run in port 80 except if you do a clever apache installation..

We recommend to use apache server and sandbox.wsgi script for this reason

About how to catch POST request with python:
http://stackoverflow.com/questions/4233218/python-basehttprequesthandler-post-variables

In order to test you can create a simple POST http request:
http://docs.python.org/library/httplib.html

----------------------------------
import httplib, urllib2

python_code_to_send = 'print "Hello world!"'
params = urllib2.quote(python_code_to_send)

headers = {"Content-type": "application/x-www-form-urlencoded",
	 "Accept": "text/plain"}

conn = httplib.HTTPConnection("127.0.0.1") # Or the location where this file is
conn.request("POST", "", params, headers)

response = conn.getresponse()
print response.status, response.reason

data = response.read()
print data

conn.close()
---------------------------------
"""

import cgi

from BaseHTTPServer import BaseHTTPRequestHandler,HTTPServer
from sandbox import Sandbox

sandbox = Sandbox()

PORT = 8000
allowed_ip = '127.0.0.1'

class pyp_sandbox_handler(BaseHTTPRequestHandler):

	def do_POST(self):
		#Check client's ip
		client_ip, client_port = self.client_address
		if client_ip != allowed_ip:
			self.send_response(403)
			return

		#Check headers
		ctype, pdict = cgi.parse_header(self.headers.getheader('content-type'))

		if ctype == 'application/x-www-form-urlencoded':
			length = int(self.headers.getheader('content-length'))
			postvars = cgi.parse_qs(self.rfile.read(length), keep_blank_values=1)
		else:
			print "Unknown header"
			postvars = {}

		print postvars

		self.send_response(200)
		self.send_header('Content-type','text/html')
		self.end_headers()
		# Send the html message
		self.wfile.write("<html><body><strong>Hello World !</strong></body></html>")
		return


if __name__ == "__main__":
	try:
		#Create a web server and define the handler to manage the
		#incoming request
		server = HTTPServer(('', PORT), pyp_sandbox_handler)
		print 'Started httpserver on port ' , PORT
	
		#Wait forever for incoming htto requests
		server.serve_forever()

	except KeyboardInterrupt:
		print '^C received, shutting down the web server'
		server.socket.close()

#print sandbox.call(func1, 1, 2)
#print sandbox.call(func2)
#print sandbox.call(func3)

