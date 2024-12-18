<?php
/*
 * CardDAV Middleware UI
 * Written by Massi-X <support@massi-x.dev> © 2024
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

header('Content-type: text/xml');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require __DIR__ . '/core/core.php';

use Core;

function authenticationFail()
{
	header('WWW-Authenticate: Basic realm="XML Phonebook ' . date('W-H') . '"'); //make sessions last 1 hour maximum
	header('HTTP/1.0 401 Unauthorized');
	printError(_('Invalid credentials.'));
	die();
}

function printError($error)
{
	$xml = new SimpleXMLElement('<xml/>');
	$xml->addChild('error', $error);
	echo $xml->asXML();
}

//get xml
try {
	$instance = Core::getInstance();

	//basic authentication
	$auth = Core::get_xml_auth();

	if (isset($auth['username']) && (!isset($_SERVER['PHP_AUTH_USER']) || strcasecmp($auth['username'], $_SERVER['PHP_AUTH_USER']) != 0 || strcmp($auth['password'], $_SERVER['PHP_AUTH_PW']) != 0))
		authenticationFail();

	//standardize get input
	$_GET = array_change_key_case($_GET, CASE_LOWER);

	//retrieve phone type to provide to getXMLforPhones()
	$typeName = isset($_GET['type']) ? strtoupper($_GET['type']) : null;
	$typeID = -1; //-1 will fallback to default

	foreach (Core::PHONE_TYPES as $id => $type) {
		if ($type['name'] == $typeName) {
			$typeID = $id;
			break;
		}
	}

	//the given type didn't match anything
	if ($typeName != null && $typeID == -1) {
		$text = str_replace('%type', $typeName, _('The given type "%type" does not correspond to a valid entry for phonebook selection. Please check your phone configuration.'));
		Core::sendUINotification(Core::NOTIFICATION_TYPE_VERBOSE, $text); //verbose notification are never sent by email
		printError(_('Something went wrong while retrieving the addressbook(s). Please log into the UI to see a more detailed error.'));
	} else //print the output
		echo $instance->getXMLforPhones(false, $typeID);
} catch (Throwable $t) {
	//send real message to the UI
	Core::sendUINotification(Core::NOTIFICATION_TYPE_ERROR, $t->getMessage());
	//and print a generic one here
	printError(_('Something went wrong while retrieving the addressbook(s). Please log into the UI to see a more detailed error.'));
}
