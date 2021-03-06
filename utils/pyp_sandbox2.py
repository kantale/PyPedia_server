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

import SimpleHTTPServer
import SocketServer
import traceback
import StringIO
import urlparse
import urllib
import json
import sys

from time import gmtime, strftime
from multiprocessing import Process, Queue

from Queue import Empty

def run_unsafe_code(the_code, output_queue):
     	temp_stdout = sys.stdout
    	sys.stdout = StringIO.StringIO()

        data = {}
        data["output"] = "text"

        try:
        	exec(the_code, {})
        	data["text"] = sys.stdout.getvalue()
        except Exception, e:
            exception_data = StringIO.StringIO()
            traceback.print_exc(file=exception_data)
            exception_data.seek(0)
            data["output"] = "exception"
            data["text"] = "%s\r\n" % exception_data.read() 

        sys.stdout = temp_stdout
        output_queue.put(data)

def exec_timed_process(the_code, time_limit):
    output_queue = Queue()
    p = Process(target=run_unsafe_code, args=(the_code, output_queue,))

    p.start()
#    p.join(time_limit)
    
    try:	
        data = output_queue.get(timeout = time_limit)
    except Empty:
    	p.terminate()
    	data = {'output' : 'exception', 'text' : 'Time out limit reached'}

    return data

class P_handler(SimpleHTTPServer.SimpleHTTPRequestHandler):

    time_limit = 10

    def do_POST(self):
        #Same code as in appspot
    	temp_stdout = sys.stdout
    	sys.stdout = StringIO.StringIO()

    	# get the POST data, unquote and strip
        content_len = int(self.headers.getheader('content-length'))
        cmd = self.rfile.read(content_len)
        cmd = urllib.unquote_plus(cmd)        
 
        data = exec_timed_process(cmd, self.time_limit)

        sys.stdout = temp_stdout
        
        #Check if log_fd exists
        try:
        	self.log_fd
        except AttributeError:
        	self.log_fd = open('log.txt', 'a')

        #Print log info.
        self.log_fd.write('_________________\n')
        con_ip, con_port = self.client_address
        self.log_fd.write('%s Client: %s %s\n' % (strftime("%Y-%m-%d %H:%M:%S", gmtime()) , str(con_ip), str(con_port)))
        self.log_fd.write('Requested code:\n')
        self.log_fd.write(cmd)
        self.log_fd.write("Response: %s\n" % json.dumps(repr(data)))
        self.log_fd.flush()

        self.send_response(200) 

        self.send_header("Access-Control-Allow-Origin", "http://www.pypedia.com")
        self.send_header("Access-Control-Request-Method", "POST, GET")
        self.send_header("Access-Control-Max-Age", "1728000")

        self.end_headers()

        self.wfile.write(json.dumps(data))
#        self.wfile.write(urllib.quote(json.dumps(data)))



def main():

	PORT = 8080

	#Following directions from: http://stackoverflow.com/questions/2274320/socketserver-threadingtcpserver-cannot-bind-to-address-after-program-restart
	httpd = SocketServer.TCPServer(("", PORT), P_handler, False) # Do not automatically bind
	httpd.allow_reuse_address = True # Prevent 'cannot bind to address' errors on restart
	httpd.server_bind()     # Manually bind, to support allow_reuse_address
	httpd.server_activate() # (see above comment)

	print "serving at port", PORT
	httpd.serve_forever()


if __name__ == '__main__':
	main()

