<?php

/*
 * Copyright (C) 2009-2012 Alexandros Kanterakis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

$parameterString = "";
$pypediaTitle = "";
$pypediaDownload = true;
$pypediaExecute = true;

//TODO: Refactor. Too much duplicate code

// Dispatch the "Execute on remote computer" button
if (isset($_REQUEST['pyp_execute'])) {

	$username = $_REQUEST['pyp_username'];

	include 'pw.php';

	$link = mysql_connect('localhost', $wgDBuser, $wgDBpassword);
	if (!$link) {
		pypd_pypediaError("Could not connect: " . mysql_error(), "Main_Page");
		return;
	}

	//Connected successfully

	mysql_select_db($wgDBname) or pypd_pypediaError( "Unable to select database", "Main_Page");

	$query = "SELECT user_ssh_host , user_ssh_username , user_ssh_port , user_ssh_path FROM {$wgDBpreffix}user WHERE user_name LIKE '$username'";

	$result=mysql_query($query);

	$num=mysql_numrows($result);

	if ($num == 0) {
		pypd_pypediaError("You have to be logged in in order to be able to execute methods", "Main_Page");
		return;
	}

	$user_ssh_host     = mysql_result($result, 0, "user_ssh_host");
	$user_ssh_username = mysql_result($result, 0, "user_ssh_username");
	$user_ssh_port     = mysql_result($result, 0, "user_ssh_port");
	$user_ssh_path     = mysql_result($result, 0, "user_ssh_path");

	if (trim($user_ssh_host) == "") {
		pypd_pypediaError("You haven't filled in ssh hostname, username and password. Read the documentation about how you can do that", "Main_Page");
		return;
	}

	$temp1 = $user_ssh_host . ' ' . $user_ssh_username . ' ' . $user_ssh_password . ' ' . $user_ssh_port . ' ' . $user_ssh_path;

	mysql_close($link);

//Execute via Restful service

	//Through ssh Connection
	if (1) {

		$remote_command = "";
		$pypediaPassword = "";
		//Parse the options to make the remotely executed command
		foreach($_REQUEST as $key => $value) {
			if ($key == "article_title") $pypediaTitle = $value;
			else if ($key == "pyp_download") $pypediaDownload = true;
			else if ($key == "pyp_execute") $pypediaExecute=true;
			else if ($key == "pyp_client") $pypediaClient = true;
			else if ($key == "pyp_password") $pypediaPassword = $value;
			else if ($key == "pyp_cloud") {/* Ignore it */}
			else if ($key == "pyp_username") $pypediaUsername = $value;
			else {
				$remote_command .= " $key='$value' ";
			}
		}

		if(!($con = ssh2_connect($user_ssh_host, $user_ssh_port))){
			echo "fail: unable to establish connection\n";
		}
		else {
			// try to authenticate with username root, password secretpassword
			if(!ssh2_auth_password($con, $user_ssh_username, $pypediaPassword)) {
				echo "fail: unable to authenticate\n";
			}
			else {
				//We are connected. Copy any files
				foreach ($_FILES as $key => $value) {
					if ($_FILES[$key]["size"] < 1500000) {

						ssh2_scp_send($con, $_FILES[$key]["tmp_name"], $user_ssh_path . "/" . $_FILES[$key]['name'], 0644);
						$remote_command .= " $key='" . $_FILES[$key]['name'] . "' ";
					}
					else {
						print "size not ok";
					}
				}

				$remote_command = "cd $user_ssh_path ; python ssh_pyp_client.py  $pypediaTitle $remote_command";

				// execute command
				//echo $remote_command;
				if (!($stream = ssh2_exec($con, $remote_command ))) {
					echo "fail: unable to execute command\n";
				}
				else {
					// collect returning data from command
					stream_set_blocking($stream, true);
					$data = "";
					while ($buf = fread($stream,4096)) {
						$data .= $buf;
					}
					print "<pre>$data</pre>";
					fclose($stream);
				}
			}
		}
	}

	//Through REST service
	if (0) {
		//Take the files
		foreach ($_FILES as $key => $value) {
			print "Got file:" . $key;
			if ($_FILES[$key]["size"] < 1500000) {
				print "size ok ";

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_VERBOSE, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, "www.pypedia.com:8080");
				curl_setopt($ch, CURLOPT_POST, true);

				$post = array($key=>"@" . $_FILES[$key]["tmp_name"],);

				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				$response = curl_exec($ch);
				print $response;
				curl_close($ch);
			}
			else {
				print "size not ok ";
			}
		}
		//Make a json string with all arguments
		$json_array = array();
		foreach($_REQUEST as $key => $value) {
			if ($key == "article_title") $pypediaTitle = $value;
			else if ($key == "pyp_download") $pypediaDownload = true;
			else if ($key == "pyp_execute") $pypediaExecute=true;
			else if ($key == "pyp_client") $pypediaClient = true;
			else if ($key == "pyp_password") $pypediaPassword = $value;
			else if ($key == "pyp_cloud") {/* Ignore it */}
			else if ($key == "pyp_username") $pypediaUsername = $value;
			else {
				$json_array[$key] = $value;
			}
		}
		$json_str = json_encode($json_array);
		$json_hex = bin2hex($json_str);

		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://www.pypedia.com:8080/$pypediaTitle/$json_hex");
		$response = curl_exec($ch);
		curl_close($ch);

		print $response;

	}

//End of execute via Restful service

//Execute via XMLRPC
	if (0) {
		$responseDownload = pypd_doDownload();
		$filename = substr($responseDownload,36);

		$responseExecute = pypd_callViaXMLRPCExecuteCode($responseDownload, $user_ssh_host, $user_ssh_username, $user_ssh_password, $user_ssh_port, $user_ssh_path);

		if (substr($responseExecute, 0, 4) != "http") {
			pypd_pypediaError($responseExecute, "Main_Page");
			return;
		}

		$message =  '<html><body><script type="text/javascript">window.location = "' . $responseExecute . '"</script></body></html>';
//	$message =  '<html><body><script type="text/javascript">window.open("' . $responseExecute . '", "_blank");window.location= "' . "http://www.google.com" . '";</script></body></html>';

		print $message;
	}
}

else {
	//pypd_pypediaError("This is not an entry point..", "Main_Page");
}

function pypd_pypediaError($pypediaText, $pypediaTitle) {
	$pypediaURL = "www.pypedia.com";
	$wgScriptPath = "";

	$tmp1 = str_replace("\r", "", $pypediaText);
	$repl1 = array("\n", "\"");
	$repl2 = array("\\n", "\\\"");
	$tmp2 = str_replace($repl1, $repl2, $tmp1);

	$redirect = "http://" . $pypediaURL . $wgScriptPath . "/index.php?title=" . $pypediaTitle;
	$mesg1 =  '<html><body><script type="text/javascript">window.alert("' . $tmp2 . '"); window.location = "' . $redirect . '"</script></body></html>';

	print $mesg1;
}

//For debugging..
function pypd_pypedialog($text) {
        $fh = fopen("log.txt", "a");
        fwrite($fh, $text);
        fwrite($fh, "\n");
        fclose($fh);
}


?>
