import webapp2

#import cgi
import sys

import traceback
import json
import StringIO
import logging
import os


class MainHandler(webapp2.RequestHandler):

	def post(self):
		temp_stdout = sys.stdout
		sys.stdout = StringIO.StringIO()
		
		# get the POST data, unquote and strip
		cmd = list(self.request.POST)[0]
		cmd = cmd.replace(u"\xa0", "")
		
		logging.info("command: %s" % repr(cmd))
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
	
		logging.info("data: %s" % json.dumps(repr(data)))
		self.response.headers.add_header("Access-Control-Allow-Origin", "http://www.pypedia.com")
		self.response.headers.add_header("Access-Control-Request-Method", "POST, GET")
		self.response.headers.add_header("Access-Control-Max-Age", "1728000")
		self.response.out.write(json.dumps(data))


app = webapp2.WSGIApplication([('/', MainHandler),], debug=True)

