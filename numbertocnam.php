<?php
/*
 * CardDAV Middleware UI
 * Written by Massi-X <firemetris@gmail.com> © 2023
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

header('Content-type: text/xml');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require __DIR__ . '/core/core.php';

use Core;

//handy shortcut to print a basic xml message
function printBasicXML($key, $text)
{
	$xml = new SimpleXMLElement('<xml/>');
	$xml->addChild($key, $text);
	echo $xml->asXML();
}

//legacy warning so users will switch to POST
if (isset($_GET['number'])) {
	//send real message to the UI
	Core::sendUINotification(Core::NOTIFICATION_TYPE_ERROR, _('Passing parameters to numbertocnam with GET is deprecated. Please use "Auto Configure" again to fix this for you.'));
	//and print a generic one here
	printBasicXML('name', '[W!] ' . _('GET is deprecated, please switch to POST.'));
	die();
}

//actual code
try {
	if (isset($_POST['number']) && !empty($_POST['number'])) {
		$instance = Core::getInstance();
		echo $instance->getCNFromPhone($_POST['number'], $instance::get_cache_expire() == 0);
	} else if (!Core::get_superfecta_compat())
		printBasicXML('name', _('Unknown'));
} catch (Exception $e) {
	//send real message to the UI
	Core::sendUINotification(Core::NOTIFICATION_TYPE_ERROR, $e->getMessage());
	//and print a generic [W!] with number
	if (!Core::get_superfecta_compat())
		printBasicXML('name', '[W!] ' . $_POST['number']);
}
