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

function validCredentials($auth, $rcvUser, $rcvPsw)
{
	return (strcasecmp($auth['username'], $rcvUser) == 0 && strcmp($auth['password'], $rcvPsw) == 0);
}

function authenticationFail($isBasic = true)
{
	if ($isBasic) header('WWW-Authenticate: Basic realm="XML Phonebook ' . date('W-H') . '"'); //make sessions last 1 hour maximum
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

	//standardize get input
	$_GET = array_change_key_case($_GET, CASE_LOWER);

	//basic authentication
	$auth = Core::get_xml_auth();

	//credentials are required
	if (isset($auth['username'])) {
		$rcvUser;
		$rcvPsw;

		if (Core::get_xml_auth_weak()) {
			$rcvUser = (isset($_GET['user']) ? $_GET['user'] : null);
			$rcvPsw = (isset($_GET['psw']) ? $_GET['psw'] : '');
		}

		//accept GET authentication if enabled...
		if ($rcvUser != null && !validCredentials($auth, $rcvUser, $rcvPsw))
			authenticationFail(false);
		//...but still continue to basic authentication if no $_GET['user'] was given (= if user is trying to use basic directly)
		else if ($rcvUser == null && !validCredentials($auth, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
			authenticationFail();
	}

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
