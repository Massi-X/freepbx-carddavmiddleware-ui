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

try {
	$instance = Core::getInstance();

	//calculate phone type to provide to getXMLforPhones()
	$typeName = $_GET['type'];
	if (isset($typeName) && !$type = array_search(strtoupper($typeName), $instance::PHONE_TYPES)) //strtoupper because in CoreInterface are defined like that
		Core::sendUINotification(Core::NOTIFICATION_TYPE_VERBOSE, str_replace('%type', $typeName, _('The given type "%type" does not correspond to a valid entry for phonebook selection. Please check your phone configuration.'))); //verbose notification are never sent by email

	//default type will be used if not found (the one set in UI)
	if (!isset($type))
		$type = -1;

	echo $instance->getXMLforPhones(false, $type);
} catch (Exception $e) {
	//send real message to the UI
	Core::sendUINotification(Core::NOTIFICATION_TYPE_ERROR, $e->getMessage());
	//and print a generic one here
	$xml = new SimpleXMLElement('<xml/>');
	$xml->addChild('error', _('Something went wrong while retrieving the addressbook(s). Please log into the UI to see a more detailed error.'));
	echo $xml->asXML();
}
