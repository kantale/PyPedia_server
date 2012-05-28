<?php
/**
 * This is the main web entry point for MediaWiki.
 *
 * If you are reading this in your web browser, your server is probably
 * not configured correctly to run PHP applications!
 *
 * See the README, INSTALL, and UPGRADE files for basic setup instructions
 * and pointers to the online documentation.
 *
 * http://www.mediawiki.org/
 *
 * ----------
 *
 * Copyright (C) 2001-2011 Magnus Manske, Brion Vibber, Lee Daniel Crocker,
 * Tim Starling, Erik Möller, Gabriel Wicke, Ævar Arnfjörð Bjarmason,
 * Niklas Laxström, Domas Mituzas, Rob Church, Yuri Astrakhan, Aryeh Gregor,
 * Aaron Schulz, Andrew Garrett, Raimond Spekking, Alexandre Emsenhuber
 * Siebrand Mazeland, Chad Horohoe, Roan Kattouw and others.
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
 *
 * @file
 */

// Bail on old versions of PHP.  Pretty much every other file in the codebase
// has structures (try/catch, foo()->bar(), etc etc) which throw parse errors in PHP 4.
// Setup.php and ObjectCache.php have structures invalid in PHP 5.0 and 5.1, respectively.
if ( !function_exists( 'version_compare' ) || version_compare( phpversion(), '5.2.3' ) < 0 ) {
	require( dirname( __FILE__ ) . '/includes/PHPVersionError.php' );
	wfPHPVersionError( 'index.php' );
}

# Initialise common code.  This gives us access to GlobalFunctions, the AutoLoader, and
# the globals $wgRequest, $wgOut, $wgUser, $wgLang and $wgContLang, amongst others; it
# does *not* load $wgTitle
if ( isset( $_SERVER['MW_COMPILED'] ) ) {
	require ( 'phase3/includes/WebStart.php' );
} else {
	require ( dirname( __FILE__ ) . '/includes/WebStart.php' );
}

$mediaWiki = new MediaWiki();
global $wgUser;

//PYPEDIA Add rest interface
//Has before timestamp been declared?
$before_timestamp = $wgRequest->getVal( 'b_timestamp' );
if (!$before_timestamp) {
	$before_timestamp = null;
}

$raw_code = $wgRequest->getVal( 'get_code' );
if ($raw_code) {
	$theCode = pypediaGetCodeFromArticle("", $raw_code, "", $before_timestamp);
	print $theCode;
	exit;
}
$raw_code = $wgRequest->getVal( 'dl_code' );
if ($raw_code) {
	$pos1 = strpos($raw_code, "=") + 1;
	$pos2 = strpos($raw_code, "(");
	if ($pos1 === false || $pos2 === false) {
	}
	else {
		$fun_name = substr($raw_code, $pos1, $pos2-$pos1);
		$theCode = pypediaGetCodeFromArticle("", $raw_code, "", $before_timestamp);
		header('Content-disposition: attachment; filename=' . $fun_name . '.py');
		header('Content-type: text/plain');
		echo $theCode;
		exit;
	}
}

$raw_code = $wgRequest->getVal( 'run_code' );
if ($raw_code) {
	$theCode = pypediaGetCodeFromArticle("", $raw_code, "", $before_timestamp);
	$results = pypediaexec3($theCode, null, null, null);
	print $results;
	exit;
}

$raw_code = $wgRequest->getVal( 'fork' );
if ($raw_code) {
	$currentUser = $wgUser->getName();

	//Check if the user has logged in
	if (ip2long($currentUser) !== false) {
		print '<html><body><script type="text/javascript">window.alert("Error: You need to be signed in to fork an article"); window.location = "'. $raw_code .'"</script></body></html>';
	}
	else {
		$forked_user_name = pypediaGetUserFromArticleName($raw_code);
		//Get the user where the original article belongs
		if ($forked_user_name === false) {
			$new_article_name = $raw_code . "_user_" . $currentUser;
		}
		else if ($forked_user_name == $currentUser) {
			print '<html><body><script type="text/javascript">window.alert("Error: You cannot fork an article that belongs to you"); window.location = "'. $raw_code .'"</script></body></html>';
		}
		else {
			$new_article_name = str_replace("_user_" . $forked_user_name, "_user_" . $currentUser, $raw_code);
		}
		$old_contents = pypediaGetArticle($raw_code);
		$new_contents = str_replace($raw_code, $new_article_name, $old_contents);

		//Changing permissions
		$new_contents = preg_replace('/===Documentation Permissions===\n*(.*)/i', "===Documentation Permissions===\n\n" . $currentUser, $new_contents);
		$new_contents = preg_replace('/===Code Permissions===\n*(.*)/i', "===Code Permissions===\n\n" . $currentUser, $new_contents);
		$new_contents = preg_replace('/===Unit Tests Permissions===\n*(.*)/i', "===Unit Tests Permissions===\n\n" . $currentUser, $new_contents);
		$new_contents = preg_replace('/===Permissions Permissions===\n*(.*)/i', "===Permissions Permissions===\n\n" . $currentUser, $new_contents);

		//Moving from the Validated to the user category
		$new_contents = str_replace('[[Category:Validated]]', '[[Category:User]]', $new_contents);

		$aTitle = Title::newFromText($new_article_name);
		$anArticle = new Article($aTitle);
		if ($anArticle != null) {
			$initial_content = $anArticle->getContent();
			//Check if the article is empty
			if (substr($initial_content, 0, 40) !== 'There is currently no text in this page.') {
				print '<html><body><script type="text/javascript">window.alert("Error: There is already an article with title: '. $new_article_name  .'"); window.location = "'. $raw_code .'"</script></body></html>';
			}
			else {
				$articleCreated = $anArticle->doEdit($new_contents, 'Created from forking article: ' . $raw_code, EDIT_NEW);
				if ($articleCreated) {
					print '<html><body><script type="text/javascript">window.location = "'. $new_article_name .'"</script></body></html>';
				}
				else {
					print '<html><body><script type="text/javascript">window.alert("Error: Internal error. Could not create article"); window.location = "'. $raw_code .'"</script></body></html>';
				}
			}
		}
		else {
			print '<html><body><script type="text/javascript">window.alert("Error: Internal error. Cannot initiate Article class"); window.location = "'. $raw_code .'"</script></body></html>';
		}

	}
	exit;
}

$raw_code = $wgRequest->getVal( 'ssh_code' );
if ($raw_code) {
	$article_name = urldecode($raw_code);
	$username = urldecode($wgRequest->getVal( 'username' ));
	$password = urldecode($wgRequest->getVal( 'password' ));
	$params = $wgRequest->getVal( 'params' );

	$ret = pypedia_SSH_Execute($article_name, $username, $password, $params);
	print $ret;

	exit;
}

$raw_code = $wgRequest->getVal( 'ul_file' );
if ($raw_code) {
	$filename = urldecode($raw_code);
	$data = urldecode($wgRequest->getVal( 'data' ));
	$username = urldecode($wgRequest->getVal( 'username' ));
	print pypedia_SSH_upload_file_local($filename, $data, $username);
	exit;
}

$raw_code = $wgRequest->getVal( 'ul_remote' );
if ($raw_code) {
	$filename = urldecode($raw_code);
	$username = urldecode($wgRequest->getVal( 'username' ));
	$password = urldecode($wgRequest->getVal( 'password' ));
	print pypedia_SSH_upload_file_remote($filename, $username, $password);
	exit;
}

// \PYPEDIA

$mediaWiki->run();
