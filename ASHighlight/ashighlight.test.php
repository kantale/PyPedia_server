<?php

require_once("ashighlight.class.php");

$text = "test()";
$lang = "py";

$p = new ASHighlight();

$p->parse_code($text,$lang);

if($p->error){
	print "parse_code returns error:";
	die($p->errmsg);
}

print "output...\n";
print $p->stylesheet;
print $p->out;
print "...output\n";

print ".........................\n";

$a = 'Before <span class="hl kwd">Test</span> After';

$pattern = '/<span class="hl kwd">(\w+)<\/span>/i';

$replacement = '-->${1}<--';

$replacement = '<span class="hl kwd"><a href="http://www.wikipl.com/index.php/${1}">${1}</a></span>';

print  preg_replace('/<span class="hl kwd">(\w+)<\/span>/i', '<span class="hl kwd"><a href="http://www.wikipl.com/index.php/${1}">${1}</a></span>', $a);
print "\n";

?>
