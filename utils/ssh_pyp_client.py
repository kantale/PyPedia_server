"""

  Copyright (C) 2009-2012 Alexandros Kanterakis
 
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

import sys
import urllib2

import pypedia

from cStringIO import StringIO

def exec_wiki_code():
	#Import function

#	article = sys.argv[1]
#	exec("from pypedia import %s" % (article))

	params = urllib2.unquote(sys.argv[2])

	command = "%s(**{%s})" % (sys.argv[1], params)

	return eval(command)

if __name__ == "__main__":

	if len(sys.argv) < 2:
		print "Invalid number of arguments"
		sys.exit(-1)

	import_except_catch = None
	command = "try:\n\timport numpy\nexcept ImportError:\n\tpass\n" #Try to import numpy
	command += "from pypedia import %s" % (sys.argv[1])
	try:
		exec(command)
	except Exception as inst:
		import_except_catch = str(inst)

	sys.stdout = mystdout = StringIO()
	except_catch = None
	ret = None

	if not import_except_catch:
		try:
			ret = exec_wiki_code()
		except Exception as inst:
			except_catch = str(inst)
	else:
		except_catch = import_except_catch

	sys.stdout = sys.__stdout__

#	print "<html>"
#	print "<body>"
	print "<b>Error:</b><pre>"
	print except_catch
	print "</pre><b>Printed:</b><pre>"
	print mystdout.getvalue()
	print "</pre><b>Returned:</b><pre>"
	print ret
	print "</pre>"
#	print "</body>"
#	print "</html>"
