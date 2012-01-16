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
import pypedia

from cStringIO import StringIO

def exec_wiki_code():
	#Import function

	article = sys.argv[1]
	exec("from pypedia import %s" % (article))

	parameters = {}
	for argument in sys.argv[2:]:
		argument_split = argument.split("=")
		parameter = argument_split[0]
		value = str.join("", argument_split[1:])

		prefix = parameter[0:4]
		suffix = parameter[6:]
		if prefix == "eval":
			value = eval(value)

		parameters[suffix] = value

	ret = eval("%s(**%s)" % (article, str(parameters)))


	return ret


if __name__ == "__main__":

	if len(sys.argv) < 2:
		print "Invalid number of arguments"
		sys.exit(-1)

	sys.stdout = mystdout = StringIO()
	except_catch = None
	ret = None

	try:
		ret = exec_wiki_code()
	except Exception as inst:
		except_catch = str(inst)

	sys.stdout = sys.__stdout__

	print "-------Error-------"
	print except_catch
	print "-------Printed-----"
	print mystdout.getvalue()
	print "-------Returned----"
	print ret
