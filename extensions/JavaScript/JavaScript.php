<?php
# Extension:Javascript
# - Licenced under LGPL (http://www.gnu.org/copyleft/lesser.html)
# - Author: http://www.organicdesign.co.nz/nad
# - Started: 2007-06-25, see article history
 
if (!defined('MEDIAWIKI')) die('Not an entry point.');
 
define('JAVASCRIPT_VERSION','1.0.2, 2007-06-27');
 
if (!isset($wgJavascriptPaths)) $wgJavascriptPaths = array();
$wgJavascriptFiles = array();
 
$wgExtensionFunctions[] = 'wfSetupJavascript';
 
# Build list of files from list, no duplicate names
foreach ($wgJavascriptPaths as $path) {
	$ipath = $_SERVER['DOCUMENT_ROOT']."/$path";
	if (is_file("$ipath")) $wgJavascriptFiles[$path] = true;
	elseif (is_dir($ipath)) {
		if ($dir = opendir($ipath)) {
			while (false !== ($file = readdir($dir))) $wgJavascriptFiles["$path/$file"] = true;
			closedir($dir);
			}
		}
	}
$list = '';
foreach (array_keys($wgJavascriptFiles) as $file)
	$list .= "<li>[$wgServer$file ".basename($file)."]</li>\n";
 
$wgExtensionCredits['other'][] = array(
	'name'        => 'Javascript',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => "Loaded Javascript files:<ul>$list</ul>",
	'url'         => 'http://www.mediawiki.org/wiki/Extension:Javascript',
	'version'     => JAVASCRIPT_VERSION
	);
 
# Notes:
# Load all dirs/files in $wgJavascriptPaths into $wgJavascriptScripts using filename as key
# Other extensions can add to the list if they need to
# After all extenions are loaded and $wgOut is established, add all the js scripts
# defined from the LocalSettings.php global variable $wgJavascriptPaths
 
function wfSetupJavascript() {
	global $wgOut,$wgServer,$wgJavascriptFiles;
	foreach (array_keys($wgJavascriptFiles) as $file)
		$wgOut->addScript("<script type=\"text/javascript\" src=\"$wgServer$file\"></script>\n");
	}
 
?>
