<?php
/*
 * CardDAV Middleware UI
 * Written by Massi-X <firemetris@gmail.com> © 2023
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

header('Content-type: text/xml');
require __DIR__ . '/core/core.php';

use Core;

try {
	$instance = Core::getInstance();
	echo $instance->getXMLforPhones($instance::get_cache_expire() == 0);
} catch (Exception $e) {
	//send real message to the UI
	Core::sendUINotification(Core::NOTIFICATION_TYPE_ERROR, $e->getMessage());
	//and print a generic one here
	$xml = new SimpleXMLElement('<xml/>');
	$xml->addChild('error', _('Something went wrong while retrieving the addressbook(s). Please log into the UI to see a more detailed error.'));
	echo $xml->asXML();
}
