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

This is a simple way to catch the POST request made by the "Execute in browser" button 
by using apache (or any other WSGI client).

The purpose is to use a sandbox other of Google APP Engine. The idea of having a standalone
simple python web server running in a different port was abandoned because of this issue:
http://stackoverflow.com/questions/2099728/how-do-i-send-an-ajax-request-on-a-different-port-with-jquery

The structure of this files is based to the example shown here:
http://webpython.codepoint.net/wsgi_request_parsing_post

Here are some brief direction of how to install mod_wsgi in apache:
(Taken from: http://serverfault.com/questions/91468/how-to-set-up-mod-wsgi-for-python-on-ubuntu)
1. sudo apt-get install libapache2-mod-wsgi
2. sudo /etc/init.d/apache2 restart
3. sudo vim /etc/apache2/sites-available/default

Edit such as:

<Directory /var/www/>
  Options Indexes FollowSymLinks MultiViews ExecCGI

  AddHandler cgi-script .cgi
  AddHandler wsgi-script .wsgi

  AllowOverride None
  Order allow,deny
  allow from all
</Directory>

4. /etc/init.d/apache2 restart

5. Then we can place this file anywhere under the /var/www dir
6. Change the pypedia.js file so that the POST ajax command will 
be directed here and not to pypediacode.appspot.com

"""

from cgi import parse_qs, escape

import json
import urllib2

def application(environ, start_response):


   # the environment variable CONTENT_LENGTH may be empty or missing
   try:
      request_body_size = int(environ.get('CONTENT_LENGTH', 0))
   except (ValueError):
      request_body_size = 0

   # When the method is POST the query string will be sent
   # in the HTTP request body which is passed by the WSGI server
   # in the file like wsgi.input environment variable.
   request_body = environ['wsgi.input'].read(request_body_size)

   #No need to parse the request body. Data are sent as such
#   d = parse_qs(request_body)

   text = str(request_body)
   text = urllib2.unquote(text)

   #Do the magic here and execute text in a magic sandbox
   #sandbox(text)

   #Otherwise to get the data from a specifiv POST field we would have to do:
#   text = d.get('text', [''])[0] # Get the age value
#   text = escape(text) # Avoid script injection

   response_body = json.dumps({"text" : text})
   
   status = '200 OK' 

   response_headers = [('Content-type', 'text/plain'),
                        ('Content-Length', str(len(response_body)))]
   start_response(status, response_headers)

   return [response_body]

