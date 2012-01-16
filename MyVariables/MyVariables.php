<?php
$wgCustomVariables = array('CURRENTUSER','CURRENTUSERREALNAME','LOGO');
 
$wgHooks['MagicWordMagicWords'][]          = 'wfAddCustomVariable';
$wgHooks['MagicWordwgVariableIDs'][]       = 'wfAddCustomVariableID';
$wgHooks['LanguageGetMagic'][]             = 'wfAddCustomVariableLang';
$wgHooks['ParserGetVariableValueSwitch'][] = 'wfGetCustomVariable';
 
function wfAddCustomVariable(&$magicWords) {
	foreach($GLOBALS['wgCustomVariables'] as $var) $magicWords[] = "MAG_$var";
	return true;
	}
 
function wfAddCustomVariableID(&$variables) {
	foreach($GLOBALS['wgCustomVariables'] as $var) $variables[] = constant("MAG_$var");
	return true;
	}
 
function wfAddCustomVariableLang(&$langMagic, $langCode = 0) {
	foreach($GLOBALS['wgCustomVariables'] as $var) {
		$magic = "MAG_$var";
		$langMagic[defined($magic) ? constant($magic) : $magic] = array(0,$var);
		}
	return true;
	}
 
function wfGetCustomVariable(&$parser,&$cache,&$index,&$ret) {
	switch ($index) {
 
		case MAG_CURRENTUSER:
			$parser->disableCache(); # Mark this content as uncacheable
			$ret = $GLOBALS['wgUser']->mName;
			break;
 
		case MAG_LOGO:
			$ret = $GLOBALS['wgLogo'];
			break;
 
		case MAG_CURRENTUSERREALNAME:
			$parser->disableCache(); # Mark this content as uncacheable
			$ret = $GLOBALS['wgUser']->mRealName;
			break;
		}
	return true;
	}
