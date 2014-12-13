<?php

$REX['ADDON']['page']['themes'] = 'Themes';
$REX['ADDON']['version']['themes'] = '1.0.0';
$REX['ADDON']['author']['themes'] = 'RexDude';
$REX['ADDON']['supportpage']['themes'] = 'forum.redaxo.de';

// includes
require($REX['INCLUDE_PATH'] . '/addons//website_manager/plugins/themes/settings.inc.php');

if ($REX['REDAXO']) {
	// add lang file
	$I18N->appendFile($REX['INCLUDE_PATH'] . '/addons/website_manager/plugins/themes/lang/');
}


