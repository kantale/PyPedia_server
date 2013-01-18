

import SimpleHTTPServer
import SocketServer
import traceback
import StringIO
import urlparse
import urllib
import json
import sys


import threading
import Queue


from multiprocessing import Process

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
    temp_stdout = sys.stdout
    output_queue = Queue.Queue()
    p = Process(target=run_unsafe_code, args=(the_code, output_queue,))

    p.start()
    p.join(time_limit)
    if p.is_alive():
    	print 'Still alive to mpourdelo'
    	sys.stdout = temp_stdout
    	p.terminate()
    	return {'output' : 'exception', 'text' : 'Time out limit reached'}
    else:
    	print 'stamatise to mpourdelo'
    	data = output_queue.get()
    	print data
    	return data



class ThreadClass(threading.Thread):

    def __init__(self, the_code, output_queue):
        threading.Thread.__init__(self)
        self.the_code = the_code
        self.output_queue = output_queue

    def run(self):
     	temp_stdout = sys.stdout
    	sys.stdout = StringIO.StringIO()

        data = {}
        data["output"] = "text"

        try:
        	exec(self.the_code, {})
        	data["text"] = sys.stdout.getvalue()
        except Exception, e:
            exception_data = StringIO.StringIO()
            traceback.print_exc(file=exception_data)
            exception_data.seek(0)
            data["output"] = "exception"
            data["text"] = "%s\r\n" % exception_data.read()     

        sys.stdout = temp_stdout
        self.output_queue.put(data)

def exec_timed_thread(the_code, time_limit):

    output_queue = Queue.Queue()
    temp_stdout = sys.stdout

    t = ThreadClass(the_code, output_queue)

    t.start()
#    t.join(float(time_limit))
    if t.is_alive():
        sys.stdout = temp_stdout

        print 'Still alive to mpourdelo'

    else:
        print 'Epestrepse:'
        print output_queue.get()

    return


class P_handler(SimpleHTTPServer.SimpleHTTPRequestHandler):

    def do_POST(self):
        #Same code as in appspot
    	temp_stdout = sys.stdout
    	sys.stdout = StringIO.StringIO()

    	# get the POST data, unquote and strip
    	# print self.client_address # ex. ('127.0.0.1', 61469)
        content_len = int(self.headers.getheader('content-length'))
        cmd = self.rfile.read(content_len)
        cmd = urllib.unquote(cmd)
#        cmd = cmd.replace(u"\xa0", "")

#        print '-' * 20
#        print cmd
#        print '-' * 20

        #parsed = urlparse.parse_qs(post_body)
        #print parsed

        data = {}
        data["output"] = "text"

        try:
            exec(cmd, {})
            data["text"] = sys.stdout.getvalue()

        except Exception, e:
            exception_data = StringIO.StringIO()
            traceback.print_exc(file=exception_data)
            exception_data.seek(0)
            data["output"] = "exception"
            data["text"] = "%s\r\n" % exception_data.read()

        sys.stdout = temp_stdout
        print "data: %s" % json.dumps(repr(data))

        self.send_response(200) 

        self.send_header("Access-Control-Allow-Origin", "http://83.212.107.55")
        self.send_header("Access-Control-Request-Method", "POST, GET")
        self.send_header("Access-Control-Max-Age", "1728000")

        self.end_headers()

        self.wfile.write(json.dumps(data))
#        self.wfile.write(urllib.quote(json.dumps(data)))



def main():

	PORT = 8080

	httpd = SocketServer.TCPServer(("", PORT), P_handler)

#	print "serving at port", PORT
#	httpd.serve_forever()

#	exec_timed_thread('while True: a = 1+2\n', 2)

	exec_timed_process('print 3', 2)


if __name__ == '__main__':
	main()