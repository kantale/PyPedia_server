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

//Load extensions
require_once( "{$IP}/extensions/PyPedia_server/ASHighlight/ashighlight.php" );
require_once( "{$IP}/extensions/PyPedia_server/MyVariables/MyVariables.php" );
include("{$IP}/extensions/PyPedia_server/SimpleForms/SimpleForms.php");

//Change this to the path of your local installation of python 2.6 or perl
$pypediaPythonPath = "/usr/bin/python";
$pypediaPerlPath = "/usr/bin/perl";

//The language that this PYPEDIA manages
$pypediaLanguage = "PYTHON";
#$pypediaLanguage = "PERL"; //Very experimental

switch ($pypediaLanguage) {
	case "PYTHON" :
		$pypediaLanguageExtension = "py";
		break;
	case "PERL" :
		$pypediaLanguageExtension = "pl";
		break;
}


//Default Permissions
$pypediaDefaultPermissions = array();
$pypediaDefaultPermissions["Development Code Permissions"] = array();
$pypediaDefaultPermissions["Development Code Permissions"][0] = "ALL";
$pypediaDefaultPermissions["Documentation Permissions"] = array();
$pypediaDefaultPermissions["Documentation Permissions"][0] = "_WPL_ARTICLE_CREATOR_";
$pypediaDefaultPermissions["Code Permissions"] = array();
$pypediaDefaultPermissions["Code Permissions"][0] = "_WPL_ARTICLE_CREATOR_";
$pypediaDefaultPermissions["Unit Tests Permissions"] = array();
$pypediaDefaultPermissions["Unit Tests Permissions"][0] = "_WPL_ARTICLE_CREATOR_";
$pypediaDefaultPermissions["Permissions Permissions"] = array();
$pypediaDefaultPermissions["Permissions Permissions"][0] = "_WPL_ARTICLE_CREATOR_";

#$pypediaXMLRPCServerIP = "192.168.0.102";
#$pypediaXMLRPCServerIP = "192.168.2.104";
#$pypediaXMLRPCServerIP = "127.0.0.1";
$pypediaXMLRPCServerIP = "95.142.166.55";

//Default article's Structure
$pypediaDefaultStructure = array(	0 => "_WPL_ARTICLE_",
							1 => array(
								0 => "Documentation",
								1 => array(
									0 => "Parameters"
								),
								2 => array(
									0 => "Return"
								),
								3 => array(
									0 => "See also"
								)
							),
							2 => array(
								0 => "Code"
							),
							3 => array(
								0 => "Unit Tests"
							),
							4 => array(
								0 => "Development Code"
							),
							5 => array(
								0 => "Permissions",
								1 => array(
									0 => "Documentation Permissions"
								),
								2 => array(
									0 => "Code Permissions"
								),
								3 => array(
									0 => "Unit Tests Permissions"
								),
								4 => array(
									0 => "Permissions Permissions"
								)
							)
						);

//Remove the edit link in a section.
//DOESN'T WORK. USER SHOULD EDIT A PAGE FIRST
function pypediaDoEditSectionLink($skin, $title, $section, $tooltip, $result, $lang = false) {
//	global $wgUser;
//
//	$sectionsNotAllowedForEditing = array("Parameters", "Code", "Unit Tests", "Permissions", "Documentation Permissions", "Code Permissions", "Unit Tests Permissions", "Permissions Permissions");
//
//	if ( ! in_array("codeeditor", $wgUser->getGroups())) {
//		foreach ($sectionsNotAllowedForEditing as $sectionNotAllowedForEditing) {
//			if (!strpos($result,  'title="Edit section: '.$sectionNotAllowedForEditing.'"') === false) {
//				$result = "";
//				break;
//			}
//		}
//	}

	return true;
}

//http://www.mediawiki.org/wiki/Manual:Hooks/EditFilter
//How to show a message in the top of the edit page if something wrong happens.
function pypediaEditFilter($editor, $text, $section, &$error, $summary) {
	//if ($section=="") {
	//	$error = "Save the article without making any change to the prefilled text";
	//}
	//$error="-->$section<--";	//"" for article edit 1,2,3... for section..
	return true;
}

//This hook adds an html injection to every edit page
//TODO: Add the code local
function pypediaEditForm($editPage) {
	global $wgServer;
	global $wgScriptPath;

	$msg = "";
	if ($editPage->mTitle->getNamespace() == ""  &&  $editPage->section == "") {
		$msg = 'Warning: press "Save page" without altering the prefilled text. Editing is allowed per section, not in complete article.';
		$editPage->editFormPageTop .= "<h3><span style='color:#0B0B0B'>$msg</span></h3>";
	}

	$message = pypediaCheckIfEditIsAllowed($editPage);
	if ($message != "ok" && $message != "proceed") {
		//Should be big and ugly !
		$editPage->editFormPageTop .= "<h3><span style='color:#FF0000'>This edit will <b>not</b> be saved<p>Reason: $message</p>Click <a href='$wgServer$wgScriptPath/index.php/PyPedia:Documentation#Who_can_contribute.3F'>here</a> for more info about editing</span></h3>";
		//$editPage->editFormPageTop .= "<script>document.getElementById('wpTextbox1').disabled = true;</script>";
	}
	$editPage->editFormPageTop .= '<b>Click three times in the text area for code friendly editing</b><script>(function inject() { var baseUrl="' . $wgServer . $wgScriptPath  . '/extensions/PyPedia_server/ace/build/textarea/src/"; function load(path, module, callback) { path = baseUrl + path; if (!load.scripts[path]) { load.scripts[path] = { loaded: false, callbacks: [ callback ] }; var head = document.getElementsByTagName("head")[0]; var s = document.createElement("script"); function c() { if (window.__ace_shadowed__ && window.__ace_shadowed__.define.modules[module]) { load.scripts[path].loaded = true; load.scripts[path].callbacks.forEach(function(callback) { callback(); }); } else { setTimeout(c, 50); } }; s.src = path; head.appendChild(s); c(); } else if (load.scripts[path].loaded) { callback(); } else { load.scripts[path].callbacks.push(callback); } }; load.scripts = {}; window.__ace_shadowed_load__ = load; load("ace.js", "text!ace/css/editor.css", function() { var ace = window.__ace_shadowed__; var Event = ace.require("pilot/event"); var areas = document.getElementsByTagName("textarea"); for (var i = 0; i < areas.length; i++) { Event.addListener(areas[i], "click", function(e) { if (e.detail == 3) { ace.options = { mode:"python",theme:"twilight",gutter:"true",fontSize:"12px",softWrap:"off",showPrintMargin:"false",useSoftTabs:"false" }; ace.transformTextarea(e.target); } }); } });})()</script>';
	return true;
}



//Checks if this is a user title
function pypediaIsUser($pypediaTitle) {
//	Old user naming schema
//	return (substr($pypediaTitle, 0, 4) == "User");

	$pypediaTitle_r = str_replace(" ", "_", $pypediaTitle);
	$splitted = explode("_", $pypediaTitle_r);
	$c = count($splitted);
	if ($c >= 3) {
		if ($splitted[$c-2] == "user") {
			return true;
		}
	}

	return false;
}

//Return the name of the user that created a USER article
function pypediaGetUserFromArticleName($pypediaTitle) {
        $pypediaTitle_r = str_replace(" ", "_", $pypediaTitle);
        $splitted = explode("_", $pypediaTitle_r);
        $c = count($splitted);
        if ($c >= 3) {
                if ($splitted[$c-2] == "user") {
                        return $splitted[$c-1];
                }
        }

        return false;

}

function pypediaCheckUserTitle($pypediaTitle, $pypediaUser) {
	$pypediaTitle_s = explode(' ', $pypediaTitle);

	//Invalid user name format
	if (!pypediaIsUser($pypediaTitle)) {
		return "The name of this User specific article is invalid . The name of this article should be: %lt;MethodName&gt;_user_&lt;Username&gt;";
	}

	$pypediaTitle_s_length = count($pypediaTitle_s);

	// The user declared is not the real one
	if ($pypediaTitle_s[$pypediaTitle_s_length - 1] != $pypediaUser) {
		return "To create a user specific article, the article's name should be &lt;MethodName&gt;_user_" . $pypediaUser;
	}

	return "ok";
}


//Perform checks to confirm whether this edit is legitimate or not
function pypediaCheckIfEditIsAllowed($editpage) {

	global $wgUser;
	global $wgServer;

	//Duplicate code.. FIXME

	//Who tries to edit this article?
	//If the editor is unregistered then $pypediaUser is the editor's ip address
	$pypediaUser = $wgUser->mName;

	//The title of the edited article
	$pypediaTitle = $editpage->mTitle->getText();

	//The namespace of the edited article (if 0 then normal namespace, if odd then talk page)
	$pypediaNamespace = $editpage->mTitle->getNamespace();

	//What Section is it? 1,2,3,... or "" if editing the complete article
	$pypediaSection = $editpage->section;

	//Get the groups of the user
	$pypediaGroups = $wgUser->getGroups();
	$pypediaGroupString = "";
	$pypediaIsCodeeditor = 0;
	$pypediaIsPypediaadmin = 0;
	$pypediaIsAnonymous = $wgUser->isAnon() ? 1 : 0;

	//Does she belong to "codeeditor", "pypediaadmin"?
	foreach($pypediaGroups as $pypediaGroup) {
		if ($pypediaGroup == "codeeditor") {
			$pypediaIsCodeeditor = 1;
		}
		else if ($pypediaGroup == "pypediaadmin") {
			$pypediaIsPypediaadmin = 1;
		}
	}

	//Admins are allowed to do anything except what PyPedia forbids
	if ($pypediaIsPypediaadmin) {
		return "proceed";
	}

	//What was the previous content of the page?
	$oldtext = $editpage->mArticle->getContent();

	//The new edited text
	$newtext = $editpage->textbox1;

	//We shouldn't do this here but any effort to edit LocalSettings.php and restrict user pypediauser from editing just fails..
	//Can anyone help??
	if ($pypediaUser == "Pypediauser") {
		return "User Pypediauser isn't allowed to edit articles";
	}

	//If title is the Main_page then we don't have to do anything..
	if ($pypediaTitle == "Main Page") {
		if ($pypediaIsPypediaadmin == 1) {
			return "ok";
		}
		else {
			return "You are not allowed to edit the Main_Page";
		}
	}

	//Allow the editing of some algorithms in the project namespace
	if ($pypediaTitle == "Wanted algorithms" && $pypediaNamespace == 4) {
		return "ok";
	}

	//Editing a Namespace other than ""
	if ($pypediaNamespace != "") {
		if ($pypediaNamespace  % 2 == 0) {//This is not a talk page
			if ($pypediaNamespace == 2) { //This is a User page
				if ($pypediaUser != $pypediaTitle) {
					return "You are not allowed to edit someone else's User page";
				}
				return "proceed";
			}
			if (!$pypediaIsPypediaadmin) {
				return "You are not allowed to edit this namespace";
			}
		}
		return "ok";
	}

	//Check if the user edits the complete article and not just a section
	if ($pypediaSection == "") {
		//She edits the complete article
		//Check if she added any text.
		$pypediaPrefilledText = "";
		pypediaPrefill($pypediaPrefilledText, $editpage->mTitle);

		//We compare the prefilled text of each article with the text added.
		//We do not allow any modification when we edit the complete article..
		if (trim($newtext) != trim($pypediaPrefilledText)) {
			//This is like editing the Permissions section
			$currentReturn = pypediaCheckSectionPermissions($pypediaSection, $pypediaUser, $oldtext);
			if ($currentReturn != "proceed") {
				return "Editing of the complete article is allowed only for creating redirects for users that have 'Permission' permissions. Save the article without making any change to the prefilled text. Then you can edit a section of the article";
			}
			return "proceed";
		}

		//Anonymous users are not allowed to create articles starting with "User"
		if (pypediaIsUser($pypediaTitle) && $pypediaIsAnonymous) {
			return "Anonymous users are not allowed to create user articles. These are specific articles for signed users";
		}

		//Ony admins can create non-User articles
		if ( (!pypediaIsUser($pypediaTitle)) && (!$pypediaIsPypediaadmin)) {
			return "Only admins are allowed to create articles that belong to the normal namespace. You are allowed to create articles with title like: Foo_user_{$pypediaUser}. For example: <a href='{$wgServer}/index.php?title={$pypediaTitle}_user_{$pypediaUser}&action=edit'>{$pypediaTitle}_user_{$pypediaUser}</a>";
		}

		//Check if the structure of the title of a User article is correct
		if (pypediaIsUser($pypediaTitle)) {
			return pypediaCheckUserTitle($pypediaTitle, $pypediaUser);
		}

		//In this case: $newtext == $pypediaPrefillText
		//As soon as the prefilled text is valid input we do not need to do any more checks.
		return "ok";
	}

	//Otherwise do a general check in the permissions defined in the article.
	return pypediaCheckSectionPermissions($pypediaSection, $pypediaUser, $oldtext);
}

//Check is a user is alowed to edit a section according to the permissions of this section
function pypediaCheckSectionPermissions($pypediaSection, $pypediaUser, $oldtext) {

	global $pypediaDefaultStructure;

	$tmp_pypediaSection = $pypediaSection;
	while ($tmp_pypediaSection){
		$tmp = 0;
		$currentTitle = pypediaGetTitleOfSectionFromStructure($pypediaDefaultStructure, $tmp_pypediaSection, $tmp, 1);
		$currentTitle = trim(str_replace('=', '', $currentTitle));
		if ($currentTitle == "Development Code") {
			return "proceed";
		}
		$searchFor = "===" . $currentTitle . " Permissions===";
		$matches = array();
		$pattern = '/' . $searchFor . '\n*(.*)/';
		if (preg_match($pattern, $oldtext, $matches)) {
			$matches_s = explode(',', $matches[1]);
			for ($i=0; $i<count($matches_s); $i++) {
				$currentMatch = trim($matches_s[$i]);
				if ($currentMatch == $pypediaUser) {
					return "proceed";
				}
				elseif ($currentMatch == "ALL") {
					return "proceed";
				}
				elseif ($currentMatch == "SIGNED" and !pypediaCheckIfUserIsIP($pypediaUser)) {
					return "proceed";
				}
			}
			return "You, $pypediaUser, don't have permissions to edit the section $currentTitle";
		}
		$tmp_pypediaSection--;
	}
	return "Pypedia Error: Could not find section permissions";
}

// == Rules ==
// * Only users belonging to "codeeditors" can edit complete articles
// * Anonymous and simple users can only edit the "Development Code" section and the talk pages
// * Only users belonging to "pypediaadmin" can edit the WPL namespace
// * Only users belonging to "pypediaadmin" can edit the "Main Page"
function pypediaEditPageAttemptSave($editpage) {

	global $wgUser;
	global $pypediaDefaultStructure;
	global $pypediaLanguageExtension;

	//Who tries to edit this article?
	//If the editor is unregistered then $pypediaUser is the editor's ip address
	$pypediaUser = $wgUser->mName;

	//God mode. TODO: Make a special user group.
	if ($pypediaUser == "WikiSysop") {
		return true;
	}

	//The title of the edited article
	$pypediaTitle = $editpage->mTitle->getText();

	//The namespace of the edited article (if 0 then normal namespace, if odd then talk page)
	$pypediaNamespace = $editpage->mTitle->getNamespace();

	//What Section is it? 1,2,3,... or "" of editing the complete article
	$pypediaSection = $editpage->section;

	//Get the groups of the user
	$pypediaGroups = $wgUser->getGroups();
	$pypediaGroupString = "";
	$pypediaIsCodeeditor = 0;
	$pypediaIsPypediaadmin = 0;
	$pypediaIsAnonymous = $wgUser->isAnon() ? 1 : 0;

	//Does she belong to "codeeditor", "pypediaadmin"?
	foreach($pypediaGroups as $pypediaGroup) {
		if ($pypediaGroup == "codeeditor") {
			$pypediaIsCodeeditor = 1;
		}
		else if ($pypediaGroup == "pypediaadmin") {
			$pypediaIsPypediaadmin = 1;
		}
	}

	//What was the previous content of the page?
	$oldtext = $editpage->mArticle->getContent();

	//The new edited text
	$newtext = $editpage->textbox1;

	//Is this a redirect page ?
	if (preg_match('/^\s*#REDIRECT\s*\[\[[\w ]*\]\]\s*$/', $newtext)) {
		//Check if she is a codeeditor
		if ($pypediaIsCodeeditor) {	//Codeeditors are allowed to do redirects
			return true;
		}
		//Check if this is a User
		//You can redirect to anything
		if (pypediaIsUser($pypediaTitle)) {
			$currentMessage = pypediaCheckSectionPermissions(8, $pypediaUser, $oldtext);
			if ($currentMessage != "proceed") {
				pypediaError($currentMessage, $pypediaTitle, $pypediaSection);
				return false;
			}
			return true;
		}
	}

	//Perform Checks to confirm that (although the user was warned), she submited the edits...
	$message = pypediaCheckIfEditIsAllowed($editpage);
	if ($message == "ok") {
		//ok, means that no subseqent check shoule be performed.
		return true;
	}
	else if ($message != "proceed") {
		//Something terrible happened..
		pypediaError($message, $pypediaTitle, $pypediaSection);
		return false;
	}



	//If the user is pypediaadmin (God mode). Is allowed to do anything. (We assume he knows what he does)
//	if ($pypediaIsPypediaadmin) {
//		return true;
//	}


	//Is this an article namespace?
	if ($pypediaNamespace != 0) { //This isn't an article..
		if ($pypediaNamespace == 2) {
			//This is a user page.
			//Which user is this page?
			$usersPage = $pypediaTitle;
			if ($usersPage == $pypediaUser) {
				//Yes it is the same.
				//Get the ssh declaration
				$sshSection = pypediaGetSection($newtext, "==ssh==");
				if (trim($sshSection) == "") {
					//No ssh declaration
				}
				else {
					$sshErrorMessage = "Misformatted line in ssh section. Format should be:
host=<hostValue>
username=<usernameValue>
port=<portValue> (optional default value: 22)
path=<pathValue> (optional default value: ./)

\"=\" is not allowed in any field
";
					//ssh declaration
					$sshSectionSplitted = split("\n", $sshSection);
					$sshHost = false;
					$sshUsername = false;
					$sshPort = 22;
					$sshPath = "./";
					foreach ($sshSectionSplitted as $sshSectionLine) {
						//For each declaration line in ssh section
						if (trim($sshSectionLine == "")) continue;
						$sshSectionLineSplitted = split("=", trim($sshSectionLine));
						if (count($sshSectionLineSplitted) != 2) {
							pypediaError($sshErrorMessage, "User:".$pypediaTitle, $pypediaSection);
							return false;
						}
						$sshLineParameter = trim($sshSectionLineSplitted[0]);
						$sshLineValue = trim($sshSectionLineSplitted[1]);
						if ($sshLineParameter == "host") {
							$sshHost = $sshLineValue;
						}
						else if ($sshLineParameter == "username") {
							$sshUsername = $sshLineValue;
						}
						else if ($sshLineParameter == "port") {
							$sshPort = $sshLineValue;
						}
						else if ($sshLineParameter == "path") {
							$sshPath = $sshLineValue;
						}
						else {
							pypediaError($sshErrorMessage, "User:".$pypediaTitle, $pypediaSection);
							return false;
						}
					}
					//All have to be declared
					if ($sshHost === false || $sshUsername === false) {
						pypediaError($sshErrorMessage, "User:".$pypediaTitle, $pypediaSection);
						return false;
					}

					//Store the credentials in the database
					$dbr = &wfGetDB(DB_SLAVE);
					$res = $dbr->update(
							'user',
							array("user_ssh_host" => $sshHost,
							"user_ssh_username" => $sshUsername,
							"user_ssh_port" => $sshPort,
							"user_ssh_path" => $sshPath),
							array("user_name" => "$pypediaUser")
							);

					//Remove the ssh section
					$editpage->textbox1 = pypediaRemoveSection($newtext, "==ssh==");

					//Success.. Inform the user that everything went nice with ..an error
					//We aren't going to save anything in the wiki anyway
					//pypediaError("New credentials were stored", "Main_Page");
				}
			}
			else {
				pypediaError("You are not allowed to change someone else's page", $pypediaTitle, $pypediaSection);
				return false;
			}

			return true;
		} //$pypediaNamespace == 2

		else {	//Talk page..
			return true;
		}
	}
	//The rest checks are for main namespaces only.


	//Get the substructure of the section that was edited
	$count = 0;
	$level = 1;
	$substructure = pypediaGetSubStructureFromSection($pypediaDefaultStructure, $pypediaSection, $count, $level);

	//Then check if the article is structured correctly according to that structure
	$pypediaRet = pypediaCheckStructure($newtext, $substructure, $level);
	if ($pypediaRet != "ok") {
		pypediaError($pypediaRet, $pypediaTitle, $pypediaSection);
		$editpage->textbox1 = $oldtext;
		return false;
	}

	//Does this user have permissions in this section?
	$ret = pypediaCheckPermissions($oldtext, $newtext, $pypediaUser, $pypediaIsPypediaadmin);
	if ($ret[0] != "ok") {
		pypediaError($ret[0], $pypediaTitle, $pypediaSection);
		$editpage->textbox1 = $oldtext;
		return false;
	}

	//Getting the code and the unit tests
	$theCode = null;
	$theUnitTests = null;
	$theParameters = null;
	if ($ret["new Code"] != null) {
		//There was code edit.
		$theCode = $ret["new Code"];

		//We need unit tests to test it.
		if ($ret["new Unit Tests"] != null) {
			$theUnitTests = $ret["new Unit Tests"];
		}
		else {
			$theUnitTests = $ret["old Unit Tests"];
		}
	}
	else {
		//There wasn't any code edit
		if ($ret["new Unit Tests"] != null) {
			//But there was Unit Tests edit
			$theUnitTests = $ret["new Unit Tests"];
			$theCode = $ret["old Code"];
		}
	}

	//Check the parameters
	if ($ret["new Parameters"] != null) {
		//There was change in the parameters section
		$galaxyXML = pypediaGetTextinTag($ret["new Parameters"], '<source lang="xml">', '</source>');
		if ($galaxyXML[1] != "ok") {
			pypediaError($galaxyXML[1], $pypediaTitle, $pypediaSection);
			return false;
		}
		$response =  pypediaGalaxyXML2HTML($galaxyXML[0], $pypediaTitle, $pypediaUser, $pypediaSection);
		if (substr($response, 0, 5) == "Error") {
			pypediaError("Could not parse Galaxy XML in Parameters section: " . $response, $pypediaTitle, $pypediaSection);
			return false;
		}
		else {
			$newParameters = "<!-- DO NOT EDIT HERE! AUTOMATICALLY GENERATED -->\n" . $response . "\n<!-- EDIT HERE! -->\n" . '<source lang="xml">' . $galaxyXML[0] . "\n" . "</source>\n";
			$editpage->textbox1 = pypediaSetTextToASection($editpage->textbox1, "Parameters", $newParameters);
			//pypediaError("-->" . $oldtext, $pypediaTitle);
			//pypediaError("-->" . $editpage->textbox1, $pypediaTitle);
			//pypediaError("-->" . $response, $pypediaTitle);
			//pypediaError("-->" . $galaxyXML, $pypediaTitle);
			//return false;

		}
	}

	//If there wasn't any change in Code or in Unit Tests. No need to run any other test.
	if ($theCode == null && $theUnitTests == null) {
		return true;
	}

	//Strip the <source lang="py"> from the code
	$ret = pypediaGetTextinTag($theCode, '<source lang="' . $pypediaLanguageExtension . '">', '</source>');
	//$ret = pypediaGetTextinTag($theCode, '<source lang="py">', '</source>');

	if ($ret[1] != "ok") {
		pypediaError($ret[1], $pypediaTitle, $pypediaSection);
		$editpage->textbox1 = $oldtext;
		return false;
	}
	$theCode = $ret[0];

	//Strip the <source lang="py"></source> from the unit tests
	$ret = pypediaGetTextinTag($theUnitTests, '<source lang="' . $pypediaLanguageExtension . '">', '</source>');
	//$ret = pypediaGetTextinTag($theUnitTests, '<source lang="py">', '</source>');
	if ($ret[1] != "ok") {
		pypediaError($ret[1], $pypediaTitle, $pypediaSection);
		$editpage->textbox1 = $oldtext;
		return false;
	}
	$theUnitTests = $ret[0];

	//Check if there is a function or class declaration in the article.
	$ret = pypediaCheckFunction($theCode, $pypediaTitle);
	if ($ret[1] !== "ok") {
		pypediaError($ret[1], $pypediaTitle, $pypediaSection);
		$editpage->textbox1 = $oldtext;
		return false;
	}

	//Get the code from calling functions called in $theCode
	$theCode = pypediaGetCodeFromArticle($pypediaTitle, $theCode, $theUnitTests);

	if ($theCode[0] == "@") {
		pypediaError($theCode[1], $pypediaTitle, $pypediaSection);
		return false;
	}

	//Execute the code
//	$results = pypediaexec2($theCode, $theUnitTests, $pypediaTitle, $pypediaSection);
	$results = pypediaexec3($theCode, $theUnitTests, $pypediaTitle, $pypediaSection);

	//Check the result of the execution
	if ($results == 'ok') {
		//Everything went fine..
		return true;
	}
	else {
		//Since the code is wrong we revert the changes..
		$editpage->textbox1 = $oldtext;

		//Show what went wrong..
		pypediaError($results, $pypediaTitle, $pypediaSection);

		//We should return false. Otherwise the session is lost.
		return false;
	}
}


//Sets the text in a section of an article
function pypediaSetTextToASection($article, $section, $theText) {
	$articleSplited = split("\n", $article);

	$ret = "";
	$inside = 0;
	foreach ($articleSplited as $line) {
		if (ereg("^ *=+$section=+ *$", $line)) {
			$inside = 1;
			$ret = $ret . "\n" . $line . "\n" . $theText;
		}
		else if ($inside == 0) {
			$ret = $ret . "\n" . $line;
		}
		else if (ereg("^[ ]*[\=]+[a-zA-Z0-9]+[\=]+[ ]*$", $line)) {
			$ret = $ret . "\n" . $line;
			$inside = 0;
		}
	}

	return $ret;
}

//Gets the current article's permissions
function pypediaGetPermissions($oldtext) {
	global $pypediaDefaultPermissions;
	global $pypediaDefaultStructure;

	if ($oldtext == "") return $pypediaDefaultPermissions;
	if (strpos($oldtext, "There is currently no text in this page.") === 0) {
		return $pypediaDefaultPermissions;
	}

	$ret = array();

	//Take permissions from the text
	foreach($pypediaDefaultPermissions as $key => $value) {
		$depth = pypediaGetDepthOfSectionInAStructure($pypediaDefaultStructure, $key, 1); //TODO: why this function fails sometimes?
		if ($depth >= 2) {
			$section = str_repeat("=", $depth) . $key . str_repeat("=", $depth);
			$permissionString = pypediaGetSection($oldtext, $section);
		}
		else {
			$permissionString = "";
		}

		//If there isn't any section with permissions, return the default permissions
		if ($permissionString == "") {
			$ret[$key] = $pypediaDefaultPermissions[$key];
		}
		else {
			//If there is then parse this section
			$permissionString = str_replace("\n", ",", $permissionString);
			$permissionStringSplitted = split(",", $permissionString);
			$ret[$key] = array();
			$count = 0;
			foreach ($permissionStringSplitted as $user) {
				if (strlen(trim($user)) < 2) continue;
				$ret[$key][$count] = trim($user);
				$count++;
			}
		}
	}

	return $ret;
}

//Gets the path to root for a section.
// ==Documentation==
// ===Parameters===
//pypediaGetPathToSection("Parameters") -> (Parameters, Documentation)
function pypediaGetPathToSection($structure, $section, &$ret) {

	foreach ($structure as $defaultSection) {
		if (is_string($defaultSection)) {
			array_push($ret, $defaultSection);
			if ($defaultSection == $section) {
				return true;
			}
		}
		if (is_array($defaultSection)) {
			if (pypediaGetPathToSection($defaultSection, $section, $ret)) {
				return true;
			}
			else {
				array_pop($ret);
			}
		}
	}

	return false;
}

//Gets the new and old versions. Checks if the changes were valid according to permissions.
function pypediaCheckPermissions($oldtext, $newtext, $pypediaUser, $pypediaIsPypediaadmin) {
	global $pypediaDefaultStructure;

	$ret = array();

	$ret["new Code"] = null;
	$ret["old Code"] = null;
	$ret["new Unit Tests"] = null;
	$ret["old Unit Tests"] = null;
	$ret["new Parameters"] = null;
	$ret["old Parameters"] = null;

	$permissions = pypediaGetPermissions($oldtext);
	$keysOfPermissions = array_keys($permissions);

	$oldStructuredText = pypediaGetStructuredText($oldtext, $newtext);
	$newStructuredText = pypediaGetStructuredText($newtext, null);

	//Get the old Code and old Unit Tests
	foreach($oldStructuredText as $oldSection) {
			//Get old code and unit tests
			if ($oldSection["name"] == "Code") {
				$ret["old Code"] = $oldSection["text"];
			}
			else if ($oldSection["name"] == "Unit Tests") {
				$ret["old Unit Tests"] = $oldSection["text"];
			}
			else if ($oldSection["name"] == "Parameters") {
				$ret["old Parameters"] = $oldSection["text"];
			}
	}

	//If this is a new article. Copy the code and the unit tests
	if (strpos($oldtext, "There is currently no text in this page.") === 0) {
		$ret["new Code"] = $ret["old Code"];
		$ret["new Unit Tests"] = $ret["old Unit Tests"];
		$ret["new Parameters"] = $ret["old Parameters"];
	}

	//Get all changed sections.
	foreach($newStructuredText as $changedSection) {

		//Find the changed section in the old. ($oldCurrentSection)
		$oldCurrentSection = null;
		foreach ($oldStructuredText as $oldSection) {

			if ($changedSection["name"] == $oldSection["name"]) {
				$oldCurrentSection = $oldSection;
				break;
			}
		}

		//Was there any changes?
		if ($changedSection["text"] != $oldCurrentSection["text"]) {

			//There were changes.

			//If the changes were in Code or in Unit Tests we need to re-run the code.
			if ($changedSection["name"] == "Code") {
				$ret["new Code"] = $changedSection["text"];
			}
			else if ($changedSection["name"] == "Unit Tests") {
				$ret["new Unit Tests"] = $changedSection["text"];
			}
			else if ($changedSection["name"] == "Parameters") {
				$ret["new Parameters"] = $changedSection["text"];
			}

			//Were there valid?

			//Get the hierarchy of the changed section
			$hierarchy = array();
			pypediaGetPathToSection($pypediaDefaultStructure, $changedSection["name"], $hierarchy);
			$hierarchy = array_reverse($hierarchy);


			//Get the permissions of the changed section
			$currentPermissions = null;
			foreach ($hierarchy as $climbUpHierarchy) {	//We take all sub..sub section to the top
				//Does this sub section has permissions?
				if (in_array($climbUpHierarchy . " Permissions", $keysOfPermissions)) {
					$currentPermissions = $permissions[$climbUpHierarchy . " Permissions"];
					break;
				}
			}

			//Check if the permissions allow the change.
			$changeWasOK = false;
			foreach($currentPermissions as $permitedUser) {
				if ($permitedUser == "ALL" or $permitedUser == $pypediaUser) {
					$changeWasOK = true;
					break;
				}
			}

			//Return message if the change was not ok
			if (!$changeWasOK && !$pypediaIsPypediaadmin) {
				$ret[0] = "You ($pypediaUser) don't have permissions to make changes in the {$changedSection["name"]} section";
				return $ret;
			}
		}
	}

	$ret[0] = "ok";
	return $ret;
}

//Return the depth of a function and the name.
//i.e: ===Parameters===. Depth = 3, Name = "Parameters"
function pypediaGetSectionDepthAndName($section) {
	$ret = array();

	$length = strlen($section);
	$equals = true;
	$depth = 0;
	$nameStart = 0;
	$nameEnd = 0;
	for ($i=0; $i<=$length; $i++) {
		if ($section[$i] == "=" and $equals) {
			$depth++;
		}
		if ($section[$i] != "=" and $equals) {
			$equals = false;
			$nameStart = $i;
		}
		if ($section[$i] == "=" and $equals == false) {
			$nameEnd = $i-$nameStart;
			break;
		}
	}

	$ret["depth"] = $depth;
	$ret["name"] = substr($section, $nameStart, $nameEnd);

	return $ret;
}

//Returns the text in $text structured according to sections
function pypediaGetStructuredText($text, $alternativeText) {
	$ret = array();

	if (strpos($text, "There is currently no text in this page.") === 0) {
		$currentText = $alternativeText;
	}
	else {
		$currentText = $text;
	}

	$sections = pypediaFindAllSectionDeclarations($currentText);

	$count = 0;

	foreach($sections as $section) {
		$aret = pypediaGetSectionDepthAndName($section);
		$aret["text"] = pypediaGetSection($currentText, $section);

		$ret[$count] = $aret;
		$count++;
	}

	return $ret;
}

//Gets the depth of a declaration of a section in a structure
function pypediaGetDepthOfSectionInAStructure($structure, $section, $depth) {

	foreach($structure as $sectionInStructure) {
		if (is_string($sectionInStructure)) {
			if ($sectionInStructure == $section) return $depth;
		}
		if (is_array($sectionInStructure)) {
			$aret = pypediaGetDepthOfSectionInAStructure($sectionInStructure, $section, $depth+1);
			if ($aret>0) {
				return $aret;
			}
		}
	}

	return -1;
}

//Get the array that contains the substructure of the $structure. $section is an int
function pypediaGetSubStructureFromSection($structure, $section, &$count, &$level) {
	if ($section == "") return $structure;

	foreach ($structure as $substructure) {
		if (is_string($substructure)) {
			if ($substructure == "_WPL_ARTICLE_") continue;
			$count++;
			if ($count == $section) {
				return $structure;
			}
		}
		else if (is_array($substructure)) {
			$level++;
			$aret = pypediaGetSubStructureFromSection($substructure, $section, $count, $level);
			if ($aret != null) return $aret;
			$level--;
		}
	}

	return null;
}

//Gets the title of a section from a counter. 1 = Documentation, 2 = Parameters, ...
function pypediaGetTitleOfSectionFromStructure($structure, $i, &$count, $level) {

	foreach ($structure as $section) {
		if (is_string($section)) {
			if ($section == "_WPL_ARTICLE_") continue;
			$count++;
			if ($count == $i) {
				return str_repeat("=", $level) . $section . str_repeat("=", $level);
			}
		}
		else if (is_array($section)) {
			$aret = pypediaGetTitleOfSectionFromStructure($section, $i, $count, $level+1);
			if ($aret != "null") return $aret;
		}
	}

	return "null";
}

//Find all section declarations. TODO: Do it with regular expressions
function pypediaFindAllSectionDeclarations($article) {
	$ret = array();

	$lines = split("\n", $article);

	$count = 0;

	for ($i=0; $i<count($lines); $i++) {
		preg_match_all("|^[ \t]*=[=]*[a-zA-Z0-9_ ]+=[=]*[ \t]*$|U", $lines[$i], $out, PREG_PATTERN_ORDER);
		if (count($out[0]) > 0) {$ret[$count] = $out[0][0]; $count++;}

	}

	return $ret;
}

//Get size of structure
function pypediaGetSizeOfStructure($structure, &$numberOfSections) {

	foreach ($structure as $section) {
		if (is_string($section)) {
			if ($section == "_WPL_ARTICLE_") {
				continue;
			}
			$numberOfSections++;
		}
		if (is_array($section)) {
			pypediaGetSizeOfStructure($section, $numberOfSections);
		}
	}
}

//Checks if the structure of the article is legitimate according to pypedia.
function pypediaCheckStructure($article, $structure, $level) {
	$sections = pypediaFindAllSectionDeclarations($article);

	$sectionsCount = 0;

	$sizeOfStructure = 0;
	pypediaGetSizeOfStructure($structure, $sizeOfStructure);

	$sizeOfSections = count($sections);

	if ($sizeOfStructure != $sizeOfSections) {
		return "Section addition or deletion is not allowed";
	}

	foreach ($sections as $section) {
		$section = trim($section);

		$sectionsCount++;
		$count = 0;
		$defaultStructureSection = pypediaGetTitleOfSectionFromStructure($structure, $sectionsCount, $count, $level) ;
		if ($defaultStructureSection != $section) {
			return "Section: ->$section<- is misalligned with default section: ->$defaultStructureSection<-";
		}
	}

	return "ok";
}

//Checks if a user is an ip address
function pypediaCheckIfUserIsIP($pypediaUser) {
	return ereg("[0-9]+\:[0-9]+\:[0-9]+\:[0-9]+\:[0-9]+\:[0-9]+\:[0-9]+\:[0-9]+", $pypediaUser); 
}

//Decodes an array that contains permissions into a string shown in the prefill text.
function pypediaFromPermissionsArrayToString($permissionsArray, $pypediaUser) {
	$ret = "";

	foreach($permissionsArray as $user) {
		if ($user == "_WPL_ARTICLE_CREATOR_") {
			if (pypediaCheckIfUserIsIP($pypediaUser)) {
				$toString = "ALL";
			}
			else {
				$toString = $pypediaUser;
			}
		}
		else {
			$toString = $user;
		}

		$ret .= $toString . ", ";
	}

	//Remove last ", "
	if (strlen($ret) > 2) {
		return substr($ret, 0, -2);
	}

	return $ret;
}

//Makes the prefil text according to the structure
function pypediaMakePrefilTextFromStructure($structure, $title, $permissions, $level) {

	global $pypediaLanguageExtension;
	global $pypediaLanguage;
	global $wgUser;
	global $wgServer;
	global $wgScriptPath;

	$title_real = str_replace(' ', '_', $title);
	$ret = "";

	foreach ($structure as $section) {
		if (is_string($section)) {
			if ($section == "_WPL_ARTICLE_") { 
				$ret = "{{#form:action=<nowiki>$wgServer$wgScriptPath/extensions/PyPedia_server/pypdownload.php</nowiki>|method=post|target=_blank|id=header_form|enctype=multipart/form-data}}{{#input:type=hidden|name=article_title|value=$title_real}}{{#input:type=hidden|name=pyp_username|value={{CURRENTUSER}}}}{{#input:type=ajax|value=Fork this article|id=fa}}{{#formend:}}
";
				continue; 
			} //Do Nothing

			//Print the section
			$ret .= str_repeat("=", $level) . $section . str_repeat("=", $level) . "\n\n";

			if ($section === "Documentation") {
				$ret .= "Documentation for '''$title'''
[[Category:User]]
[[Category:Algorithms]]
";
			}
			else if (($section === "Code") || $section === "Development Code") {
				//$ret .= "<source lang=\"py\">
				$ret .= "<source lang=\"" . $pypediaLanguageExtension . "\">";
				switch ($pypediaLanguage) {
					case "PYTHON" :
						//Replace spaces with "_"
						$currentTitle = str_replace(" ", "_", $title);
						$ret .= "
def $currentTitle():
	pass

</source>

";
						break;
					case "PERL" :
						$ret .= "
sub $title {
}

</source>

";
						break;
				}

			}
			else if ($section == "Unit Tests") {
				if ($pypediaLanguage == "PYTHON") $ret .= "<source lang=\"py\">

def uni1():
	return True

</source>

";
				else if ($pypediaLanguage == "PERL") $ret .= "<source lang=\"py\">
sub uni1 {
}

</source>

";
			}
			else if ($section == "Documentation Permissions") {
				$ret .= "{$permissions[0]}

";
			}
			else if ($section == "Code Permissions") {
				$ret .= "{$permissions[1]}

";
			}
			else if ($section == "Unit Tests Permissions") {
				$ret .= "{$permissions[2]}

";
			}
			else if ($section == "Permissions Permissions") {
				if ($wgUser->isAnon()) {
					$ret .= "ALL
";
				}
				else {
					$ret .= "{$permissions[3]}
";
				}

			}
			else if ($section == "Parameters") {
				$ret .= "<!-- DO NOT EDIT HERE! AUTOMATICALLY GENERATED -->
{{#form:action=<nowiki>$wgServer$wgScriptPath/extensions/PyPedia_server/pypdownload.php</nowiki>|method=post|target=_blank|id=parameters_form|enctype=multipart/form-data}}
<p>
{{#input:type=hidden|name=article_title|value=$title_real}}
{{#input:type=hidden|name=pyp_username|value={{CURRENTUSER}}}}
{{#input:type=ajax|value=Download code|id=dc}}
{{#input:type=ajax|value=Execute on remote computer|id=eorc}}
{{#input:type=ajax|value=Execute on browser|id=eob}}
{{#formend:}}

<!-- EDIT HERE! -->
<source lang=\"xml\">

<inputs>
</inputs>

</source>
";
			}
		}
		else if (is_array($section)) {
			$ret .= pypediaMakePrefilTextFromStructure($section, $title, $permissions, $level+1);
		}
	}

	return $ret;
}

//Prefill function. It is declared as a hook in LocalSettings.php
function pypediaPrefill(&$textbox, &$title) {
	global $wgUser;
	global $pypediaDefaultPermissions;
	global $pypediaDefaultStructure;

	//Get the title of the article
	$title_str = $title->getText();

	//Get the username of the editor
	$pypediaUser = $wgUser->mName;

	//If the page is not an normal article then don't prefill
	if ($title->getNamespace() != 0) {
		$textbox = "";
	}
	else {

		$permissions = array();
		$permissions[0] = pypediaFromPermissionsArrayToString($pypediaDefaultPermissions["Documentation Permissions"], $pypediaUser);
		$permissions[1] = pypediaFromPermissionsArrayToString($pypediaDefaultPermissions["Code Permissions"], $pypediaUser);
		$permissions[2] = pypediaFromPermissionsArrayToString($pypediaDefaultPermissions["Unit Tests Permissions"], $pypediaUser);
		$permissions[3] = pypediaFromPermissionsArrayToString($pypediaDefaultPermissions["Permissions Permissions"], $pypediaUser);

		$textbox = pypediaMakePrefilTextFromStructure($pypediaDefaultStructure, $title_str, $permissions, 1);
	}
	return true;
}

function pypediaError($pypediaText, $pypediaTitle, $pypediaSection) {

	global $wgUser;

	$tmp1 = str_replace("\r", "", $pypediaText);
	$repl1 = array("\n", "\"");
	$repl2 = array("\\n", "\\\"");
	$tmp2 = str_replace($repl1, $repl2, $tmp1);

	$redirect = "index.php?title=" . $pypediaTitle . "&action=edit";
	if ($pypediaSection) {
		$redirect = $redirect . "&section=$pypediaSection";
	}
	$mesg1 =  '<html><body><script type="text/javascript">window.alert("' . $tmp2 . '"); window.location = "' . $redirect . '"</script></body></html>';

	print $mesg1;

	$username = $wgUser->mName;
	$time_now = gmdate("Y-m-d H:i:s", time());
	pypedialog("$username|$time_now|$mesg1");
}

function pypediaAlert($pypediaText) {
	global $pypediaURL, $wgScriptPath;

	$tmp1 = str_replace("\r", "", $pypediaText);
	$repl1 = array("\n", "\"");
	$repl2 = array("\\n", "\\\"");
	$tmp2 = str_replace($repl1, $repl2, $tmp1);

	$mesg1 =  '<script type="text/javascript">window.alert("' . $tmp2 . '");';

	print $mesg1;

	pypedialog("mesg1:$mesg1<--");
}

//Get the text of a section. It doesn't gets subsections. $section should be like ==XXXX==
function pypediaGetSection($pypediaText, $section) {

	$length = strlen($pypediaText);

	$start = strpos($pypediaText, $section);

	if ($start === false) {
		//Not found
		return "";
	}

	$end = -1;

	$startEnter = strpos($pypediaText, "\n", $start);
	if ($startEnter === false) {
		$startEnter = $length;
	}

	$end = strpos($pypediaText, "\n==", $startEnter);
	if ($end === false) {
		$end = $length;
	}
	else {
		$end++;
	}

	$ret = substr($pypediaText, $startEnter, $end-$startEnter);
	if (strlen($ret) == 0) $ret = "\n";

	return $ret;
}

//Remove a section from the text. section should be like: ==XXXX==
function pypediaRemoveSection($pypediaText, $pypediaSection) {
	$pypediaTextSplitted = split("\n", $pypediaText);

	$toReturn = "";

	$inSection = false;
	foreach($pypediaTextSplitted as $pypediaTextLine) {
		if (trim($pypediaTextLine) == $pypediaSection) {
			$inSection = true;
			continue;
		}
		if ($inSection) {
			if (strlen(trim($pypediaTextLine)) > 2) {
				if (substr(trim($pypediaTextLine), 0, 2) == "==") {
					$inSection = false;
				}
			}
		}

		if (! $inSection) {
			$toReturn .= $pypediaTextLine . "\n";
		}
	}

	return $toReturn;
}

function pypediaCheckFunction($pypediaText, $pypediaTitle) {

	$ret = array();

	$currentPypediaTitle = str_replace(" ", "_", $pypediaTitle);
	$rexpression = "\ndef[ \t]+".$currentPypediaTitle."[ \t]*\(";
	$classRExpr  = "\nclass[ \t]+".$currentPypediaTitle."[ \t]*[\:\(]";

	$ret[1] = "ok";

	if (!ereg($rexpression, $pypediaText) && (!ereg($classRExpr, $pypediaText))) {
		$ret[1] = "Could not find declaration of function or class same as the page title";
	}

	return $ret;
}

function pypediaGetTextinTag($pypediaText, $pypediaTag1, $pypediaTag2) {
	$ret = array();

	$tmp1 = split($pypediaTag1, $pypediaText);

	$tmp2 = count($tmp1);
	if ($tmp2 == 1) {
		$ret[1] = "No $pypediaTag1 tag found";
	}
	elseif ($tmp2 > 2) {
		$ret[1] = "More than one $pypediaTag1 tags founs";
	}
	else {
		$tmp3 = split($pypediaTag2, $tmp1[1]);
		$tmp4 = count($tmp3);
		if ($tmp4 == 1) {
			$ret[1] = "No $pypediaTag2 tag found";
		}
		elseif ($tmp4 > 2) {
			$ret[1] = "More than one $pypediaTag2 tags found";
		}
		else {
			$ret[0] = $tmp3[0];
			$ret[1] = "ok";
		}
	}

	return $ret;
}

/*

$request = xmlrpc_encode_request('add', array(3, 4));
$response = do_call($host, $port, $request);


http://www.php.net/manual/en/ref.xmlrpc.php
To connect to a python xmlrpc server I use:

This needs to be replaced ! Adds significant delay. do everything in php
*/
function pypedia_do_call($host, $port, $request) {

    $url = "http://$host:$port/";
    $header[] = "Content-type: text/xml";
    $header[] = "Content-length: ".strlen($request);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

    $data = curl_exec($ch);

    if (curl_errno($ch)) {
        #print curl_error($ch);
        return curl_error($ch);
    } else {
        curl_close($ch);
        return $data;
    }
}

//Converts the user Galaxy-like XML input of parameters to the SimpleForms format
//It calls a python function via XMLRPC
//Deprecated. Use pypediaGalaxyXML2HTML instead
function pypediaGalaxyXML2HTML_XMLRPC($galaxyXML, $pypediaTitle, $pypediaUser, $pypediaSection) {
	global $pypediaXMLRPCServerIP;

	$XMLRPC_request = xmlrpc_encode_request('galaxyXML2HTML', array($galaxyXML, $pypediaTitle, $pypediaUser));
	$XMLRPC_host = $pypediaXMLRPCServerIP;
	$XMLRPC_port = "45000";

	$response = pypedia_do_call($XMLRPC_host, $XMLRPC_port, $XMLRPC_request);

	if (strpos($response, "<methodResponse>") == 0) {
		pypediaError($response, $pypediaTitle, $pypediaSection);
		return false;
	}

	return xmlrpc_decode($response);
}

//Converts the user Galaxy-like XML input of parameters to the SimpleForms format
function pypediaGalaxyXML2HTML($galaxyXML, $pypediaTitle, $pypediaUser, $pypediaSection) {
	global $wgServer, $wgScriptPath;

	libxml_use_internal_errors(true);

	$parsed = 0;

	try {
		//Parse the XML
		$parsed = new SimpleXMLElement($galaxyXML);
	} catch(Exception $e) {
		$ret = 'Error: ' . $e->getMessage() . '\n';
		foreach(libxml_get_errors() as $error) {
			$ret .= "\t" . $error->message;
		}
		return $ret;
	}

	//Iterate through the generated structure
	$ret = "{{#form:action=<nowiki>$wgServer$wgScriptPath/extensions/PyPedia_server/pypdownload.php</nowiki>|method=post|target=_blank|id=parameters_form|enctype=multipart/form-data}} 
<p>";
	foreach ($parsed->param as $param) {
		$ret .= $param["label"];

		$selections = "";
		switch ($param["type"]) {
			case "select" : {
				$ret .= "\n{{#input:type=select";
				$prefix = "selc__";
				foreach($param->option as $option) {
					$selections .= "*" . $option["value"] . "\n";
				}
				break;
			}
			case "data" : {
				$ret .= "{{#input:type=text";
				$prefix = "data__";
				break;
			}
			case "eval" : {
				$ret .= "{{#input:type=text";
				$prefix = "eval__";
				break;
			}
			case "file" : {
				$ret .= "{{#input:type=file";
				$prefix = "file__";
				break;
			}
			default : {
				return "Error: Unknown type: {$param["type"]}. Accepted values are 'select', 'data', 'eval' and 'file'";
			}
		}

		$ret .= "|name=$prefix{$param['name']}|";
		if ($selections) {
			$ret .= "\n$selections}}<br>\n";
		}
		else {
			$ret .= "value={$param['value']}}} <br>\n";
		}

	}

	$pypediaTitle_nospace = str_replace(" ", "_", $pypediaTitle);

	$ret .= "
{{#input:type=hidden|name=article_title|value=$pypediaTitle_nospace}}
{{#input:type=hidden|name=pyp_username|value={{CURRENTUSER}}}}
{{#input:type=ajax|value=Download code|id=dc}}
{{#input:type=ajax|value=Execute on remote computer|id=eorc}}
{{#input:type=ajax|value=Execute on browser|id=eob}}
{{#formend:}}
";

	return $ret;
}

//Execute the unitests
function pypediaexec2($theCode, $unitests, $pypediaTitle, $pypediaSection) {
	global $pypediaPythonPath;
	global $pypediaXMLRPCServerIP;

	$pypSession = session_id();

	//XML RPC method call
	$XMLRPC_request = xmlrpc_encode_request('pypedia_exec', array($theCode, $unitests, $pypediaTitle, $pypSession));
	$XMLRPC_host = $pypediaXMLRPCServerIP;
	$XMLRPC_port = "45000";

	$response = pypedia_do_call($XMLRPC_host, $XMLRPC_port, $XMLRPC_request);

	if (strpos($response, "<methodResponse>") == 0) {
		pypediaError($response, $pypediaTitle, $pypediaSection);
		return false;
	}

	return xmlrpc_decode($response);
}

//Build the code that will run the submitted code and it will return the output
function pypedia_build_python_run_code($the_code) {

$code = "
import sys
import StringIO
import traceback

temp_stdout = sys.stdout
sys.stdout = StringIO.StringIO()

theGlobals = globals()

try:
	exec(\"\"\"" . addslashes($the_code) . "\"\"\") in theGlobals
except Exception as details:
	print traceback.format_exc()

to_return = sys.stdout.getvalue()
sys.stdout = temp_stdout
print to_return
" ;

	return $code;
}

//Build the python code that will test the unitests
function pypedia_build_python_code($the_code, $the_unitests) {

$code = '

import sys
import time
import inspect
import StringIO
import traceback

def exec_code(theCode, theUnitests):

	theGlobals = globals()

	try:
		exec(theCode) in theGlobals
	except ImportError:
		#ImportErrors in the code are allowed.
		return "ok"
	except Exception as details:
		return traceback.format_exc()

	#Which are the functions in current scope?
	#We make a COPY of the f_locals dictionary because it changes during iteration
	scopeStart = dict(inspect.currentframe(0).f_locals)

	try:
		exec(theUnitests)
	except Exception as details:
		return "Unitest Error: " + str(details)

	scopeEnd = dict(inspect.currentframe(0).f_locals)

	for k,v in scopeEnd.iteritems():
		#scopeStart is in scopeEnd but not in scopeStart...
		if k == "scopeStart": continue

		if k not in scopeStart:
			try:
				returned = v()
			except Exception as inst:
				return str(inst)

			#Did we returned boolean?
			if type(returned).__name__ == "bool":
				#If it is false then raise exception
				if not returned:
					return "Unitest: %s Failed" % (k)
			elif type(returned).__name__ == "str":
				return "Unitest: %s Failed\nReason Given:\n%s" % (k, returned)
			else:
				return "Unitest error. Unitest %s returned type %s. Don\'t know how to handle this." % (k, type(returned).__name__)

	return "ok"

';

$code .= "
temp_stdout = sys.stdout
sys.stdout = StringIO.StringIO()
ret = exec_code(\"\"\"" . addslashes($the_code) . "\"\"\", \"\"\"" . addslashes($the_unitests) . "\"\"\")
sys.stdout = temp_stdout
print ret
" ;

	return $code;

}

//Execute the unitests in google appspots directly from here
function pypediaexec3($theCode, $unitests, $pypediaTitle, $pypediaSection) {
	if (!$unitests) {
		$code = pypedia_build_python_run_code($theCode);
		$line_limit = 500;
	}
	else {
		$line_limit = 10;
		$code = pypedia_build_python_code($theCode, $unitests);
	}

	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,'http://pypediacode.appspot.com');
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch,CURLOPT_POST, 1);
	curl_setopt($ch,CURLOPT_POSTFIELDS,urlencode($code));

	$result = curl_exec($ch);
	$str_respond = json_decode($result)->{'text'};
	curl_close($ch);

	if ($str_respond != "ok\n") {
		$fixed_reply = '';
		$str_respond_d = explode('\n', $str_respond);
		end($str_respond_d);
		$i = 0;
		//Keep the last $line_limit lines of the result to show to the user.
		//If we are running unitests then either "ok" is expected or the error message
		//Otherwise we allow only 500 limit of output of the submitted code
		while( ($val = current($str_respond_d)) and ($i<$line_limit)) {
			if (trim($val) == "") continue;

			$i++;
			$fixed_reply .= "\n" .  $val;
  			prev($str_respond_d);
		}
		return $fixed_reply;
	}
	
	return "ok";
}

//Get the content of an article right BEFORE a timestamp
//TODO: Can we do it with simpler database api?
//TODO: Remove the 50 throttle
function pypediaGetArticleBeforeTimestamp($pypediaTitle, $pypediaTimestamp) {

	//Request database
	$dbw = wfGetDB( DB_SLAVE );

	//Get the Page_id of the article
	$res = $dbw->select( 'page',
		'page_id',
		array(
			'page_title' => $pypediaTitle,
			'page_namespace' => 0,
		),
		__METHOD__);

	$article_id = 0;
	foreach ( $res as $row ) {
		$article_id = $row->page_id;
	}

	//Get the last 50 revisions of that article
	$res = $dbw->select( 'revision',
		'rev_timestamp',
		array(
			'rev_page' => $article_id,
		),
		__METHOD__,
		array( 'ORDER BY' => 'rev_timestamp ASC', 'LIMIT' => 50 ) );

	//Get the most recent timestamp of the article that is older than the $pypediaTimestamp
	$revision_timestamp = null;
	$last_timestamp = null;
	foreach ( $res as $row ) {
		$current_timestamp = strval($row->rev_timestamp);
		$last_timestamp = $current_timestamp;
		if ($current_timestamp <= $pypediaTimestamp) {
			$revision_timestamp = $current_timestamp;
		}
		else {
			break;
		}
	}

	if (!$revision_timestamp) {
		$revision_timestamp = $last_timestamp;
	}

	//Get the estimated revision
	$aTitle = Title::newFromText($pypediaTitle);
	$r = Revision::loadFromTimestamp( $dbw, $aTitle, $revision_timestamp );
	
	if (!$r) {
		return null;
	}
	else {
		return $r->getText();
	}

}

//Gets the content from an article. Gets called forth
function pypediaGetArticle($pypediaTitle, &$newTitle, $pypediaTimestamp = null) {

	//Get content from article
	if ($pypediaTimestamp) {
		$ret = pypediaGetArticleBeforeTimestamp($pypediaTitle, $pypediaTimestamp);
		if (!$ret) {
			return null;
		}
	}
	else {
		//Much faster
		$aTitle = Title::newFromText($pypediaTitle);
		$anArticle = new Article($aTitle);
		if ($anArticle != null) {
			$ret = $anArticle->getContent();
		}
		else {
			return null;
		}
	}

	#Is it redirect?
	preg_match_all("|\#REDIRECT[ \t]+\[\[[a-zA-Z0-9_]+\]\]|U", $ret, $out, PREG_PATTERN_ORDER);
	if (count($out[0]) == 0) {
		//It is not redirect
		$newTitle = $pypediaTitle;
		return $ret;
	}
	else {
		//It is redirect
		//Get the redirect article
		$start = strpos($out[0][0], "[[");
		$end = strpos($out[0][0], "]]");
		$newTitle = substr($out[0][0], $start+2, $end-$start-2);
		$ret2 = pypediaGetArticle($newTitle, $unused, $pypediaTimestamp);
		return $ret2;
	}

}

//Non Recursive! Gets the code from an Article. Gets called third
function pypediaGetCodeFromArticleNR($pypediaTitle, &$newTitle, $pypediaTimestamp = null) {
	$anArticle = pypediaGetArticle($pypediaTitle, $newTitle, $pypediaTimestamp);

	if ($anArticle != null) {
		$anArticle = pypediaGetSection($anArticle, "==Code==");
		$aCode = pypediaGetTextinTag($anArticle, '<source lang="py">', '</source>');
		//pypediaError($aCode[1] . "<-->" . $pypediaTitle, $pypediaTitle);
		if ($aCode[1] != "ok") {
			return NULL;
		}
		else {
			return $aCode[0];
		}
	}

	return null;
}

//Recursive. Called from "main"
function pypediaGetCodeFromArticle($pypediaTitle, $pypediaUntestedCode, $pypediaUntestedUnittests, $pypediaTimestamp = null) {
	$functionsMet = array();
	$functionsMet[$pypediaTitle] = True;
	$ret = "";
	$RetCode = pypediaGetCodeFromArticle2($pypediaTitle, $functionsMet, $ret, $pypediaUntestedCode, $pypediaUntestedUnittests, $pypediaTimestamp);

	if ($RetCode === true) {
	}
	else {
		return $RetCode;
	}


	return $ret;
}

#Remove all instances of a regular expression from a string
function pypediaRemoveRegExpr($aRegExpr, $aString) {

	preg_match_all($aRegExpr, $aString, $out, PREG_PATTERN_ORDER);

	$ret = $aString;

	$c = count($out[0]);
	for ($i=0; $i<$c; $i++) {
		$ret = str_replace($out[0][$i], "", $ret);
	}

	return $ret;
}

//Returns a list with function calls listed in theCode
function pypediaGetFunctionCallsFromCode($theCode) {

	//Remove """   """"
	$toTest = pypediaRemoveRegExpr("|\"\"\".*\"\"\"|U", $theCode);

	//Remove " "
	$toTest = pypediaRemoveRegExpr("|\".*\"|U", $toTest);

	//Remove ' '
	$toTest = pypediaRemoveRegExpr("|\'.*\'|U", $toTest);

	//Remove comments
	$toTest = pypediaRemoveRegExpr("|\#.*\n|U", $toTest);

	//Get all function calls of the code
	preg_match_all("|[a-zA-Z][a-zA-Z0-9_]*[ \t]*\(|U", $toTest, $out, PREG_PATTERN_ORDER);

	//$funCount = count($out[0]);
	return $out[0];
}

//Recursive search for all function calls of an article. Gets called second
function pypediaGetCodeFromArticle2($pypediaTitle, &$functionsMet, &$ret, $pypediaUntestedCode, $pypediaUntestedUnittests, $pypediaTimestamp = null) {
	//Get Current Article
	if ($pypediaUntestedCode != null) {
		$anArticle = $pypediaUntestedCode;
		$newTitle = NULL;
	}
	else {
		$anArticle = pypediaGetCodeFromArticleNR($pypediaTitle, $newTitle, $pypediaTimestamp);
		//pypediaError($anArticle . "<-what?->" . $pypediaTitle, "  ");
	}
	if ($anArticle == null) {
		return null;
	}

	//Is it a redirect article
	if ($newTitle != NULL) {
		$functionsMet[$newTitle] = True;
		//If it is redirect define a new function (the redirected)
		if ($newTitle != $pypediaTitle) {
			$ret = $pypediaTitle . ' = ' . $newTitle . "\n" . $ret;
		}
	}

	//Get code on top of the current
	$ret = $anArticle . $ret;

	//Take all function calls of the article
	if ($pypediaUntestedUnittests) {	//If we have unit tests add the functions that are called from the unit tests
		$anArticle .= "\n" . $pypediaUntestedUnittests;
	}
	$funList = pypediaGetFunctionCallsFromCode($anArticle); //Get all the calling functions from this snippet of code
	$funCount = count($funList);
	if ($funCount == 2) {
		if ($funList[0] == "@") {
			return $funList;
		}
	}

	for ($tmp1=0; $tmp1<$funCount; $tmp1++) {
		#We remove the "(" from the "foo(" that matches the regular expression
		$funName = substr($funList[$tmp1], 0, -1);

		//Have we met that before?
		$thisExists = False;
		if (array_key_exists($funName, $functionsMet)) {
			if ($functionsMet[$funName] == True) { //Do nothing
				$thisExists = True;
			}
		}
		else if (False) { //TODO: Check if this is common function (efficiency)
		}
		else if (!$thisExists){
			$functionsMet[$funName] = True;
			//Add the code recursively
			pypediaGetCodeFromArticle2($funName, $functionsMet, $ret, null, null, $pypediaTimestamp);
			//pypediaError($funName . "<-->" . $ret, "nnn");
		}
	}

	return true;
}

//For debugging..
function pypedialog($text) {
	$fh = fopen("log.txt", "a");
	fwrite($fh, $text);
	fwrite($fh, "\n");
	fclose($fh);
}

function pypediaArticleAfterFetchContent(&$article, &$content) {
	$content = $content . "TEST";
	return true;
}
