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
	header('WWW-Authenticate: Basic realm="XML Phonebook '.date('W-H').'"'); //make sessions last 1 hour maximum
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

	//calculate phone type to provide to getXMLforPhones()
	$typeName = isset($_GET['type']) ? $_GET['type'] : null;
	if ($typeName != null && !$type = array_search(strtoupper($typeName), $instance::PHONE_TYPES)) //strtoupper because in CoreInterface are defined like that
		Core::sendUINotification(Core::NOTIFICATION_TYPE_VERBOSE, str_replace('%type', $typeName, _('The given type "%type" does not correspond to a valid entry for phonebook selection. Please check your phone configuration.'))); //verbose notification are never sent by email

	//default type will be used if not found (the one set in UI)
	if (!isset($type)) $type = -1;

	echo $instance->getXMLforPhones(false, $type);
} catch (Throwable $t) {
	//send real message to the UI
	Core::sendUINotification(Core::NOTIFICATION_TYPE_ERROR, $t->getMessage());
	//and print a generic one here
	printError(_('Something went wrong while retrieving the addressbook(s). Please log into the UI to see a more detailed error.'));
}
