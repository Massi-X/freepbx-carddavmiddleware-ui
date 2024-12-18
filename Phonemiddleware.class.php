<?php
/*
 * CardDAV Middleware UI
 * Written by Massi-X <support@massi-x.dev> © 2024
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

namespace FreePBX\modules;

/**
 * BMO class for display and module calls
 **/

require_once __DIR__ . '/core/core.php'; //once! It could be required by others too

use Exception;
use FreePBX;
use Core;
use Throwable;
use Utilities;

const NOT_FIRST_RUN_KEY = 'NOT_FIRST_RUN_KEY';

class Phonemiddleware extends \DB_Helper implements \BMO
{
	private $FreePBX;
	private $Core = null;

	private $WWW_MODULE_DIR_OLD;
	private $WWW_MODULE_SYMLINK_OLD;
	private $WWW_MODULE_DIR_NEW;
	private $ASSETS_SYMLINK;

	private static $inPage = false;
	private static $xmlPhonebookURL = null;
	private static $numberToCnamURL = null;
	private static $emailTo = null;

	/**
	 * BMO constructor
	 */
	public function __construct($freepbx = null)
	{
		if ($freepbx == null)
			throw new Exception("Not given a FreePBX Object");

		global $amp_conf;
		$this->FreePBX = $freepbx;

		//create Core instance for BMO calls
		if ($this->Core == null)
			$this->Core = Core::getInstance();

		//for each one of these we provide both lowercase support (new) and "styled" mixed case support (that will eventually go away)
		$this->WWW_MODULE_DIR_OLD = $amp_conf['AMPWEBROOT'] . '/phonemiddleware';
		$this->WWW_MODULE_SYMLINK_OLD = $amp_conf['AMPWEBROOT'] . '/phoneMiddleware';
		$this->WWW_MODULE_DIR_NEW = $amp_conf['AMPWEBROOT'] . '/carddavmiddleware';
		$this->ASSETS_SYMLINK = $amp_conf['AMPWEBROOT'] . '/admin/assets/phonemiddleware';
	}

	/**
	 * Return an Core instance so that it can be used where it cannot be accessed directly (Mainly Backup/Restore functions)
	 * 
	 * @return	Core
	 */
	public function getCore()
	{
		return $this->Core;
	}

	/**
	 * Global search provider for the top omnibox.
	 * 
	 * @return 	void
	 */
	public function search($query, &$results)
	{
		$results[] = array('text' => _('CardDAV Setup'), 'type' => 'get', 'dest' => '?display=phonemiddleware');
		$results[] = array('text' => _('CardDAV Superfecta Configuration'), 'type' => 'get', 'dest' => '?display=phonemiddleware');
		$results[] = array('text' => _('CardDAV CallerID Lookup Configuration'), 'type' => 'get', 'dest' => '?display=phonemiddleware');
		$results[] = array('text' => _('CardDAV Inbound CNAM Configuration'), 'type' => 'get', 'dest' => '?display=phonemiddleware');
		$results[] = array('text' => _('CardDAV Outbound CNAM Configuration'), 'type' => 'get', 'dest' => '?display=phonemiddleware');
		$results[] = array('text' => _('CardDAV XML Phonebook'), 'type' => 'get', 'dest' => '?display=phonemiddleware');
		$results[] = array('text' => _('PhoneMiddleware'), 'type' => 'get', 'dest' => '?display=phonemiddleware'); //legacy support
		$results[] = array('text' => _('Phone Middleware'), 'type' => 'get', 'dest' => '?display=phonemiddleware'); //legacy support
	}

	/**
	 * Things to do before showing the page
	 * 
	 * @return 	void
	 */
	public function doConfigPageInit($page)
	{
		//signal to other functions that we are inside a page
		self::$inPage = true;

		$rootPath = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];

		//xml phonebook URL
		self::$xmlPhonebookURL = $rootPath . '/carddavmiddleware/carddavtoxml.php';

		//cnam URL. By default it is the same URL and protocol as the browser.
		//Then I try to check if plain http is available by looking at the current page or by calling sysadmin to see if force is disabled.
		//if any of these matches I set the URL to localhost
		//as a side note: sysadmin calls only works on genuine Sangoma systems. For other systems with SSL enabled a warning is printed in magicconfig.js
		self::$numberToCnamURL = $rootPath  . '/carddavmiddleware/numbertocnam.php';
		try {
			if (substr($rootPath, 0, 5) !== 'https' || !$this->getSysAdminPorts()['forcessl'])
				self::$numberToCnamURL = 'http://localhost/carddavmiddleware/numbertocnam.php';
		} catch (Throwable $t) {
			//an error occured. ignore it and fallback to default
		}

		//email "To" field
		try {
			self::$emailTo = Utilities::get_fpbx_to_email_config($this->FreePBX);
		} catch (Exception $e) {
			self::$emailTo = _('No address registered.'); //I only use "To" address here
		}

		// prepare here all the (php) variables needed by scripts.js
		echo '<script>';

		//language variable
		echo 'var pm_language = {' .
			'"Yes": "' . _('Yes') . '", ' .
			'"OK": "' . _('OK') . '", ' .
			'"Cancel": "' . _('Cancel') . '", ' .
			'"Close": "' . _('Close') . '", ' .
			'"Save": "' . _('Save') . '", ' .
			'"empty": "' . _('empty') . '", ' .
			'"undefined": "' . _('undefined') . '", ' .
			'"Donotshowagain": "' . _('Do not show again') . '", ' .
			'"Validate": "' . _('Validate') . '", ' .
			'"Must_validate": "' . _('You must revalidate your configuration.') . '", ' .
			'"Saving_dots": "' . _('Saving…') . '", ' .
			'"Validating_dots": "' . _('Validating…') . '", ' .
			'"Loading_dots": "' . _('Loading…') . '", ' .
			'"No_addresbook_found": "' . _('Connected successfully to the server, but no addressbooks found.') . '", ' .
			'"Move": "' . _('Move') . '", ' .
			'"Role": "' . _('Role') . '", ' .
			'"Title": "' . _('Title') . '", ' .
			'"Nickname": "' . _('Nickname') . '", ' .
			'"Birthday": "' . _('Birthday') . '", ' .
			'"Address": "' . _('Address') . '", ' .
			'"Name": "' . _('Name') . '", ' .
			'"SSL_Active": "' . _('SSL Active') . '", ' .
			'"SSL_Bypass": "' . _('Bypass SSL') . '", ' .
			'"JS_fn": "' . _('Full card name') . '", ' .
			'"JS_email_adr": "' . _('Email address') . '", ' .
			'"JS_org": "' . _('Organization name') . '", ' .
			'"JS_confirm_delete_notifications": "' . _('Are you sure to delete all the notifications?') . '", ' .
			'"JS_magic_step1": "' . _('** STEP 1: Superfecta Setup **') . '", ' .
			'"JS_magic_step1_1": "' . _('Creating or updating scheme...') . '", ' .
			'"JS_magic_step1_2": "' . _('Enabled regex.') . '", ' .
			'"JS_magic_step1_3": "' . _('Updated regex.') . '", ' .
			'"JS_magic_step1_4": "' . _('You can manually enable SPAM interception if needed by going into the \"') . Utilities::SUPERFECTA_SCHEME . _('\" scheme and setting \"Send SPAM Call To\" to what you like.') . '", ' .
			'"JS_magic_step2": "' . _('** STEP 2: OutCNAM Setup **') . '", ' .
			'"JS_magic_step3": "' . _('** STEP 3: Inbound Route(s) Setup **') . '", ' .
			'"JS_magic_step3_1": "' . _('Retrieving inbound route(s)...') . '", ' .
			'"JS_magic_step3_2": "' . _('Processing route with cid=%cid and did=%did...') . '", ' .
			'"JS_magic_step3_notfound": "' . str_replace('%autoconfig', _('Auto Configure'), _('No route found! Please run %autoconfig again when you have created one')) . '", ' .
			'"JS_magic_SSL_warning": "' . str_replace('%autoconfig', _('Auto Configure'), _('Your system is configured to redirect every request to https. Please make sure that you a valid FQDN and certificate or superfecta lookups will fail! You could also disable force redirect for the admin interface inside the \"System Admin\" module (section \"Port Management\") and then run %autoconfig again to fix this (Sytem Admin is only available on Sangoma systems).')) . '", ' .
			'"JS_magic_error": "' . _('Something went wrong during previous step. Please see the log for more information.') . '", ' .
			'"JS_magic_completed": "' . _('Process completed. You are ready to rock! Please wait for the changes to be applied... You may now close the window.') . '",';
		//these are "special" languages entries. The key is also used as value in javascript
		for ($i = 0; $i < count(Core::PHONE_TYPES); $i++) {
			$type = Core::PHONE_TYPES[$i];

			if ($type == null)
				continue;

			echo '"PHONE_TYPE_' . $i . '": "[' . $type . '] - ';

			switch ($i) {
				case Core::PHONE_TYPE_PASSTHROUGH:
					echo  _('Keep numbers and labels intact inside the XML (where possible) - Spaces converted to \'_\'');
					break;
				case Core::PHONE_TYPE_NO_LIMITS:
					echo  _('Guess the type (\'Telephone\', \'Mobile\', \'Other\') and generate an XML containing all the phone numbers without limits');
					break;
				case Core::PHONE_TYPE_FANVIL:
					echo _('Fanvil Phone (\'Telephone\', \'Mobile\', \'Other\' tags) - Maximum 3 phone numbers, types cannot repeat');
					break;
				case Core::PHONE_TYPE_SNOM:
					echo _('Snom Phone (\'Telephone\', \'Mobile\', \'Office\' tags) - Maximum 3 phone numbers, types cannot repeat');
					break;
				default:
					echo _('Unknown type');
					break;
			}
			echo '", ';
		}
		echo "};\n";

		//phonemiddleware values to js object (do not change these, can be used by core too)
		echo "var phonemiddleware = {" .
			'"isFirstRun": ' . ($this->getConfig(NOT_FIRST_RUN_KEY) ? 'false' : 'true') . ', ' .
			'"ajax_name": "phonemiddleware", ' .
			'"numberToCnamURL": "' . self::$numberToCnamURL . '", ' .
			'"SUPERFECTA_SCHEME": "' . Utilities::SUPERFECTA_SCHEME . '", ' .
			'"country_codes": [';

		foreach (self::get_country_codes() as $key => $value)
			echo '{"value": "' . $key . '", "name": "' . addslashes($value["name"]) . ' (+' . $value["code"] . ')"},';

		echo '], ' .
			'"SUPERFECTA_SCHEME_CONFIG": [';

		foreach (Utilities::SUPERFECTA_SCHEME_CONFIG as $key => $value)
			echo '{"key": "' . $key . '", "value": "' . addslashes($value) . '"},';

		echo ']' .
			"};\n" .
			'</script>';

		//css language support
		echo '<style>' .
			':root {' .
			'--language-available-items: "' . _('Available Items:') . '";' .
			'--language-available-separators-reusable: "' . _('Available Separators (Reusable):') . '";' .
			'}' .
			'</style>';

		//submit settings handling
		if (isset($_POST['submit'])) {
			$this->Core->set_cache_expire(isset($_POST['cache_expire']) ? (int) $_POST['cache_expire'] : 0);
			$this->Core->set_country_code(isset($_POST['country_code']) ? $_POST['country_code'] : '');
			$this->Core->set_output_construct(isset($_POST['output_construct']) ? $_POST['output_construct'] : '');
			if (isset($_POST['max_cnam_length_enable']) && $_POST['max_cnam_length_enable'] == 'on')
				$this->Core->set_max_cnam_length(isset($_POST['max_cnam_length']) ? (int) $_POST['max_cnam_length'] : 0);
			else
				$this->Core->set_max_cnam_length(0);
			$this->Core->set_xml_auth(isset($_POST['xml_auth_user']) ? $_POST['xml_auth_user'] : '', isset($_POST['xml_auth_psw']) ? $_POST['xml_auth_psw'] : '');
			$this->Core->set_phone_type(isset($_POST['phone_type']) ? (int) $_POST['phone_type'] : Core::PHONE_TYPE_NO_LIMITS);
			$this->Core->set_mail_level(isset($_POST['mail_level']) ? $_POST['mail_level'] : []);
			$this->Core->set_superfecta_compat(isset($_POST['superfecta_compat']) ? ($_POST['superfecta_compat'] == 'on' ? true : false) : false);
			$this->Core->set_spam_match(isset($_POST['spam_match']) ? ($_POST['spam_match'] == 'on' ? true : false) : false);

			if (!$this->Core->store_config()) {
				if (!is_array($_POST['errors']))
					$_POST['errors'] = [];

				array_push($_POST['errors'], _('Something went wrong! Failed to save settings.'));
			}
		}
	}

	/**
	 * Return the resolved XML Phonebook URL
	 * 
	 * @return	string					XML Phonebook URL
	 * @throws	Exception				If this doesn't run inside a page
	 */
	public static function getXmlPhonebookURL()
	{
		if (!self::$inPage) throw new Exception(_('This is only available inside a page'));

		return self::$xmlPhonebookURL;
	}

	/**
	 * Return the resolved NumberToCNAM URL
	 * 
	 * @return	string					NumberToCNAM URL
	 * @throws	Exception				If this doesn't run inside a page
	 */
	public static function getNumberToCnamURL()
	{
		if (!self::$inPage) throw new Exception(_('This is only available inside a page'));

		return self::$numberToCnamURL;
	}

	/**
	 * Return the resolved email address
	 * 
	 * @return	string					"To" email address (if available)
	 * @throws	Exception				If this doesn't run inside a page
	 */
	public static function getToAddress()
	{
		if (!self::$inPage) throw new Exception(_('This is only available inside a page'));

		return self::$emailTo;
	}


	/**
	 * Set which ajax requests should be allowed
	 * 
	 * @param	string		$req	The request string from JS
	 * @return 	void
	 */
	public function ajaxRequest($req, &$setting)
	{
		switch ($req) {
			case 'savecarddav':
			case 'validatecarddav':

			case 'deletenotification':
			case 'deleteallnotifications':

			case 'superfectainit':
			case 'superfectareorder':
			case 'outcnamsetup':
			case 'inboundroutesetup':

			case 'createorder':
			case 'validatepurchase':
			case 'restorepurchase':

			case 'setfirstrun':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Handle ajax request and return a result back in JSON
	 * 
	 * @return 	void
	 */
	public function ajaxHandler()
	{
		//set default timeout (10s) for those connections to stop the user wait too much in case of problems
		ini_set('default_socket_timeout', 10);

		switch ($_REQUEST['command']) {
			case 'savecarddav':
				$this->Core->set_url($_POST['carddav_url']);
				$this->Core->set_auth($_POST['carddav_user'], $_POST['carddav_psw']);
				$this->Core->set_carddav_addressbooks($_POST['carddav_addressbooks']);
				if (method_exists(Core::class, 'get_ssl_enabled')) $this->Core->set_ssl_enabled($_POST['carddav_ssl_enable'] == 'on');

				try {
					if (!$this->Core->store_config()) //store config with the updated values above
						throw new Exception();
				} catch (Exception $e) {
					throw new Exception(_('Failed to save the addressbook settings!'));
				}
				return true;
			case 'validatecarddav':
				if (empty($_POST['carddav_url']))
					throw new Exception(_('URL must be set.')); //url must be there

				//load all the values from the server
				$this->Core->set_url($_POST['carddav_url']);
				$this->Core->set_auth($_POST['carddav_user'], $_POST['carddav_psw']);
				if (method_exists(Core::class, 'get_ssl_enabled')) $this->Core->set_ssl_enabled($_POST['carddav_ssl_enable'] == 'on');
				$this->Core->init(); //force new init

				$result = $this->Core->discover_addressbooks_to_display();
				$uris = $this->Core->get_carddav_addressbooks();

				//then reorder the array as we saved it. Position of non checked values will obviously not be preserved as nobody cares.
				for ($i = count($uris) - 1; $i >= 0; --$i) {
					$uri = $uris[$i];
					if (array_key_exists($uri, $result)) { //if the book is still there (read: if not deleted from the server)
						//check the element
						$result[$uri]['checked'] = true;

						//move the element to the top. The array is iterated in reverse to correctly push the first value to the first place.
						$temp = $result[$uri];
						unset($result[$uri]);
						array_unshift($result, $temp);
					}
				}

				return array_values($result); //js doesn't care about array keys. It is simpler to give it a normal indexed array
			case 'deletenotification':
				try {
					$this->Core->deleteUINotification($_POST['id']);
				} catch (Exception $e) {
					throw new Exception(_('Failed to delete the notification(s)!'));
				}
				return true;
			case 'deleteallnotifications':
				try {
					$this->Core->deleteAllUINotifications();
				} catch (Exception $e) {
					throw new Exception(_('Failed to delete the notification(s)!'));
				}
				return true;
			case 'superfectainit':
				//create scheme
				$r = new \ReflectionObject($this->FreePBX->Superfecta);
				$p = $r->getProperty('schemeDefaults');
				$p->setAccessible(true);

				$message = _('Updated scheme.');

				$scheme = $p->getValue($this->FreePBX->Superfecta);
				$scheme['scheme_name'] = Utilities::SUPERFECTA_SCHEME;
				$scheme['destination'] = ''; //missing value in defaults. So add empty
				$scheme['Curl_Timeout'] = 5; //5 seconds is already too much
				$scheme['SPAM_Text'] = '[SPAM] '; //a default text. Could be changed by the user if needed
				$scheme['SPAM_threshold'] = 1; //1 = the only value supported by Core
				$scheme['Character_Encodings'] = 'UTF-8'; //added in 16.0.27
				$scheme['Strip_Accent_Characters'] = 'N'; //added in 16.0.27. Do not strip accent chars (hope devices in 2023 don't have problems with that!)
				$scheme['Caller_Id_Max_Length'] = -1; //added in 16.0.27. -1 = no limit (limit is already managed by Core)
				if (!$this->FreePBX->Superfecta->getScheme(Utilities::SUPERFECTA_SCHEME)) {
					if (!$this->FreePBX->Superfecta->addScheme(Utilities::SUPERFECTA_SCHEME, $scheme)['status'])
						return ['status' => false, 'message' => _('An unknown exception occured.')];
					else
						$message = _('Created scheme.');
				}

				if ($this->FreePBX->Superfecta->updateScheme(Utilities::SUPERFECTA_SCHEME, $scheme))
					return ['status' => true, 'message' => $message];
				else
					return ['status' => false, 'message' => _('An unknown exception occured.')];
			case 'superfectareorder':
				//hacky way to get to the result. Beacuse in the next steps I set my scheme as the only one, this isn't really needed but I leave it here for completeness.
				try {
					//first get global var and unset everything that is inside to prevent any problem
					global $_REQUEST;
					global $_POST;
					$_REQUEST = [];
					$_POST = [];

					//put it on top
					$_REQUEST['command'] = 'sort';
					$_REQUEST['scheme'] = Utilities::SUPERFECTA_SCHEME;
					$_POST['position'] = 'up';
					$executions = 0; //prevent infinite loops
					while ($this->FreePBX->Superfecta->getScheme(Utilities::SUPERFECTA_SCHEME)['order'] > 10) {
						$this->FreePBX->Superfecta->ajaxHandler();

						if ($executions >= 99) //I hope nobody has more than 100 schemes!
							break;
						++$executions;
					}

					return ['status' => true, 'message' => _('Moved scheme at the top.')];
				} catch (Throwable $t) {
					return ['status' => false, 'message' => _('Unable to move scheme at the top. Proceeding anyway...')];
				}
			case 'outcnamsetup':
				//hacky way to get to the result
				$data['enable_cdr'] = 'CHECKED';
				$data['enable_rpid'] = 'CHECKED';
				$data['scheme'] = 'base_' . Utilities::SUPERFECTA_SCHEME; //for strange reasons superfecta prepends "base_" to the scheme name. Simply hardcode it here
				$this->FreePBX->OutCNAM->editConfig(1, $data); //ID is always 1, see https://github.com/POSSA/freepbx-Outbound_CNAM/blob/master/Outcnam.class.php#L49
				return ['status' => true, 'message' => _('OutCNAM configured.')]; //outcnam does not return any useful information so as long as there is no errors this will be the return value
			case 'inboundroutesetup':
				//hacky way to get to the result.
				$extra = _('Deleted cidlookup configuration.') . "\n";
				$route = json_decode(file_get_contents("php://input"));
				if (!$route)
					throw new Exception(_('Data is invalid.')); //the data is not valid

				//reset cidlookup
				try {
					$this->FreePBX->Cidlookup->didDelete($route->extension, $route->cidnum); //viewing_itemid is unused here so doesn't matter (I don't know if this note is still valid, it was from the old method)
				} catch (Throwable $t) {
					$extra = _('Module cidlookup not installed/active. Skipping...') . "\n";
					//cidlookup is not installed. No worry, skip this step and only add an info message
				}

				//set superfecta
				$settings['sf_enable'] = 'true';
				$settings['sf_scheme'] = Utilities::SUPERFECTA_SCHEME;
				$settings['extension'] = $route->extension;
				$settings['cidnum'] = $route->cidnum;

				$this->FreePBX->Superfecta->bulkhandler_superfecta_cfg($settings); //viewing_itemid is unused here so doesn't matter

				return ['status' => true, 'message' => $extra . _('Updated superfecta configuration.')]; //sadly there is no way (simple enough) to know if this was successful or not
			case 'setfirstrun':
				//store first run, no input needed and return value is always true
				$this->FreePBX->PhoneMiddleware->setConfig(NOT_FIRST_RUN_KEY, true);
				return ['status' => true, 'message' => ''];
		}
	}

	/**
	 * Handle ajax request and return a result back in plain text
	 * 
	 * @return 	void
	 */
	public function ajaxCustomHandler()
	{
		switch ($_REQUEST['command']) {
			case 'createorder':
				if (method_exists(Core::class, 'create_order')) {
					$res = Core::create_order();
					echo $res['message'];
					return $res['result'];
				} else
					throw new Exception(_('Command is not implemented'));
			case 'validatepurchase':
				if (method_exists(Core::class, 'validate_purchase')) {
					$res = Core::validate_purchase($_POST, file_get_contents("php://input"));
					echo $res['message'];
					return $res['result'];
				} else
					throw new Exception(_('Command is not implemented'));
			case 'restorepurchase':
				if (method_exists(Core::class, 'restore_purchase')) {
					$res = Core::restore_purchase($_POST, file_get_contents("php://input"));
					echo $res['message'];
					return $res['result'];
				} else
					throw new Exception(_('Command is not implemented'));
		}
	}

	/**
	 * Method that is executed on install/upgrade. die() using the red span from page.modules.php instead of throwing exceptions here to better present errors to the user
	 * 
	 * @return 	void
	 */
	public function install()
	{
		//delete www folder
		try {
			//to support upgrade from previous versions i need to check both for is_link because I switched the two (is_link check always first)
			if ((is_link($this->WWW_MODULE_DIR_OLD) && !unlink($this->WWW_MODULE_DIR_OLD)) || (file_exists($this->WWW_MODULE_DIR_OLD) && !Utilities::delete_dir($this->WWW_MODULE_DIR_OLD)))
				throw new Exception();
			if ((is_link($this->WWW_MODULE_SYMLINK_OLD) && !unlink($this->WWW_MODULE_SYMLINK_OLD)) || (file_exists($this->WWW_MODULE_SYMLINK_OLD) && !Utilities::delete_dir($this->WWW_MODULE_SYMLINK_OLD)))
				throw new Exception();
			if (file_exists($this->WWW_MODULE_DIR_NEW) && !Utilities::delete_dir($this->WWW_MODULE_DIR_NEW))
				throw new Exception();
		} catch (Exception $e) {
			die('<span class="error">' . str_replace('%folder', 'root', _('Installation failed: Unable to delete %folder folder. Make sure the module has read/write permissions.')) . '</span>');
		}

		//recreate www folder and symlinks
		if (
			!mkdir($this->WWW_MODULE_DIR_NEW) ||
			!symlink($this->WWW_MODULE_DIR_NEW, $this->WWW_MODULE_DIR_OLD) || //symlink new folder to old ones
			!symlink($this->WWW_MODULE_DIR_NEW, $this->WWW_MODULE_SYMLINK_OLD) || //symlink new folder to old ones
			!symlink(__DIR__ . '/carddavtoxml.php', $this->WWW_MODULE_DIR_NEW . '/carddavtoxml.php') || //symlink carddavtoxml
			!symlink(__DIR__ . '/carddavtoxml.php', $this->WWW_MODULE_DIR_NEW . '/carddavtoXML.php') || //symlink carddavToXML (for backward compatibility)
			!symlink(__DIR__ . '/numbertocnam.php', $this->WWW_MODULE_DIR_NEW . '/numbertocnam.php') || //symlink numbertocnam
			!symlink(__DIR__ . '/numbertocnam.php', $this->WWW_MODULE_DIR_NEW . '/numberToCNAM.php') || //symlink numberToCNAM (for backward compatibility)
			!file_exists($this->ASSETS_SYMLINK) && !symlink(__DIR__ . '/assets/', $this->ASSETS_SYMLINK) //symlink assets folder if not already there (not done automatically by fpbx, and seems like it is right this way)
		)
			die('<span class="error">' . _('Failed to initialize working directory. The module won\'t work. Make sure the module has read/write permissions.') . '</span>');

		//add Job
		FreePBX::Job()->addClass('phonemiddleware', 'job', 'FreePBX\modules\PhoneMiddleware\Job', '* * * * *');

		//because the module is compatible with both 15 and 16 framework, I need to check the framework module itself for the required version (not possible inside module.xml)
		//see https://github.com/FreePBX/framework/commit/c05c245d691ecffaac52cc118adb4451e107300c and https://github.com/FreePBX/framework/commit/f4b390e451c4db4238cb1dfb20f195e04aba3907
		out(_('Checking dependencies...'));

		if (!function_exists('getversion'))
			die('<span class="error">' . _('Unable to get system version!') . '</span>');

		$version = getversion();

		//there is no compare for versions lower than 15 because it is prevented by module.xml
		$is15 = version_compare($version, 15) >= 0;
		$is16 = version_compare($version, 16) >= 0;
		$framework_version = 0;

		//I do not check 17 or higher simply because there is no need
		if ($is15 || $is16) {
			try {
				$framework_version = $this->FreePBX->Modules->getXML('framework')->version;
			} catch (Throwable $t) {
				die('<span class="error">' . _('Unable to get framework version!')) . '</span>';
			}

			//always check from highest to lowest
			if ($is16) {
				if (version_compare($framework_version, '16.0.40.4') == -1)
					die('<span class="error">' . str_replace('%version', '16.0.40.4', _('Minimum required version of framework is %version. Please update first to the required version before install/update.')) . '</span>');
			} else if ($is15) {
				if (version_compare($framework_version, '15.0.37.2') == -1)
					die('<span class="error">' . str_replace('%version', '15.0.37.2', _('Minimum required version of framework is %version. Please update first to the required version before install/update.')) . '</span>');
			}
		}

		//post install hooks from core, if providen. Excpetions are not catched here, if you care you must catch them yourself.
		if (method_exists(Core::class, 'post_install_hook'))
			Core::post_install_hook($this->FreePBX);
	}

	/**
	 * Method that is executed on uninstall
	 * 
	 * @return 	void
	 */
	public function uninstall()
	{
		$isException = false;

		//delete main root folder (because this function is 2.0.0+ only, the folder is already lowercase only).
		//also if this is executed in a non-consinsent state it doesn't matter because will be removed on next install.
		try {
			if (!Utilities::delete_dir($this->WWW_MODULE_DIR_NEW))
				throw new Exception();
		} catch (Exception $e) {
			$isException = true;
			out(str_replace('%folder', 'root', _('Unable to delete %folder folder.')) . ' ' . _('Try to delete it manually to completely remove the module.'));
		}

		//delete www (symlink) folder
		try {
			//to support upgrade from previous versions i need to check both for is_link because I switched the two (is_link check always first)
			if ((is_link($this->WWW_MODULE_DIR_OLD) && !unlink($this->WWW_MODULE_DIR_OLD)) || (file_exists($this->WWW_MODULE_DIR_OLD) && !Utilities::delete_dir($this->WWW_MODULE_DIR_OLD)))
				throw new Exception();
			if ((is_link($this->WWW_MODULE_SYMLINK_OLD) && !unlink($this->WWW_MODULE_SYMLINK_OLD)) || (file_exists($this->WWW_MODULE_SYMLINK_OLD) && !Utilities::delete_dir($this->WWW_MODULE_SYMLINK_OLD)))
				throw new Exception();
		} catch (Exception $e) {
			$isException = true;
			out(str_replace('%folder', 'symlink root', _('Unable to delete %folder folder.')) . ' ' . _('Try to delete it manually to completely remove the module.'));
		}

		//pre uninstall hooks from core, if providen. Excpetions are catched here and a generic warning will be printed at the end.
		try {
			if (method_exists(Core::class, 'post_uninstall_hook'))
				Core::post_uninstall_hook($this->FreePBX);
		} catch (Throwable $t) {
			$isException = true;
		}

		if ($isException)
			throw new Exception(_('Some error(s) occurred during the process. Please check above for error messages.')); //this will be printed in red automatically and is not an exception that causes the page to die

		//symlink folder as well as Jobs are automatically removed by freepbx
	}

	/**
	 * Return an associative array of all country codes. Thanks to https://gist.github.com/josephilipraja/8341837?permalink_comment_id=3883690#gistcomment-3883690
	 *
	 * @return	array				Country code list with phone number and friendly name
	 */
	public static function get_country_codes()
	{
		return [
			'AD' => ['name' => 'ANDORRA', 'code' => '376'],
			'AE' => ['name' => 'UNITED ARAB EMIRATES', 'code' => '971'],
			'AF' => ['name' => 'AFGHANISTAN', 'code' => '93'],
			'AG' => ['name' => 'ANTIGUA AND BARBUDA', 'code' => '1268'],
			'AI' => ['name' => 'ANGUILLA', 'code' => '1264'],
			'AL' => ['name' => 'ALBANIA', 'code' => '355'],
			'AM' => ['name' => 'ARMENIA', 'code' => '374'],
			'AN' => ['name' => 'NETHERLANDS ANTILLES', 'code' => '599'],
			'AO' => ['name' => 'ANGOLA', 'code' => '244'],
			'AQ' => ['name' => 'ANTARCTICA', 'code' => '672'],
			'AR' => ['name' => 'ARGENTINA', 'code' => '54'],
			'AS' => ['name' => 'AMERICAN SAMOA', 'code' => '1684'],
			'AT' => ['name' => 'AUSTRIA', 'code' => '43'],
			'AU' => ['name' => 'AUSTRALIA', 'code' => '61'],
			'AW' => ['name' => 'ARUBA', 'code' => '297'],
			'AX' => ['name' => 'ÅLAND ISLANDS', 'code' => '358'],
			'AZ' => ['name' => 'AZERBAIJAN', 'code' => '994'],
			'BA' => ['name' => 'BOSNIA AND HERZEGOVINA', 'code' => '387'],
			'BB' => ['name' => 'BARBADOS', 'code' => '1246'],
			'BD' => ['name' => 'BANGLADESH', 'code' => '880'],
			'BE' => ['name' => 'BELGIUM', 'code' => '32'],
			'BF' => ['name' => 'BURKINA FASO', 'code' => '226'],
			'BG' => ['name' => 'BULGARIA', 'code' => '359'],
			'BH' => ['name' => 'BAHRAIN', 'code' => '973'],
			'BI' => ['name' => 'BURUNDI', 'code' => '257'],
			'BJ' => ['name' => 'BENIN', 'code' => '229'],
			'BL' => ['name' => 'SAINT BARTHELEMY', 'code' => '590'],
			'BM' => ['name' => 'BERMUDA', 'code' => '1441'],
			'BN' => ['name' => 'BRUNEI DARUSSALAM', 'code' => '673'],
			'BO' => ['name' => 'BOLIVIA', 'code' => '591'],
			'BQ' => ['name' => 'CARIBEAN NETHERLANDS', 'code' => '599'],
			'BR' => ['name' => 'BRAZIL', 'code' => '55'],
			'BS' => ['name' => 'BAHAMAS', 'code' => '1242'],
			'BT' => ['name' => 'BHUTAN', 'code' => '975'],
			'BV' => ['name' => 'BOUVET ISLAND', 'code' => '55'],
			'BW' => ['name' => 'BOTSWANA', 'code' => '267'],
			'BY' => ['name' => 'BELARUS', 'code' => '375'],
			'BZ' => ['name' => 'BELIZE', 'code' => '501'],
			'CA' => ['name' => 'CANADA', 'code' => '1'],
			'CC' => ['name' => 'COCOS (KEELING) ISLANDS', 'code' => '61'],
			'CD' => ['name' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE', 'code' => '243'],
			'CF' => ['name' => 'CENTRAL AFRICAN REPUBLIC', 'code' => '236'],
			'CG' => ['name' => 'CONGO', 'code' => '242'],
			'CH' => ['name' => 'SWITZERLAND', 'code' => '41'],
			'CI' => ['name' => 'COTE D IVOIRE', 'code' => '225'],
			'CK' => ['name' => 'COOK ISLANDS', 'code' => '682'],
			'CL' => ['name' => 'CHILE', 'code' => '56'],
			'CM' => ['name' => 'CAMEROON', 'code' => '237'],
			'CN' => ['name' => 'CHINA', 'code' => '86'],
			'CO' => ['name' => 'COLOMBIA', 'code' => '57'],
			'CR' => ['name' => 'COSTA RICA', 'code' => '506'],
			'CU' => ['name' => 'CUBA', 'code' => '53'],
			'CV' => ['name' => 'CAPE VERDE', 'code' => '238'],
			'CW' => ['name' => 'CURAÇAO', 'code' => '599'],
			'CX' => ['name' => 'CHRISTMAS ISLAND', 'code' => '61'],
			'CY' => ['name' => 'CYPRUS', 'code' => '357'],
			'CZ' => ['name' => 'CZECH REPUBLIC', 'code' => '420'],
			'DE' => ['name' => 'GERMANY', 'code' => '49'],
			'DJ' => ['name' => 'DJIBOUTI', 'code' => '253'],
			'DK' => ['name' => 'DENMARK', 'code' => '45'],
			'DM' => ['name' => 'DOMINICA', 'code' => '1767'],
			'DO' => ['name' => 'DOMINICAN REPUBLIC', 'code' => '1809'],
			'DZ' => ['name' => 'ALGERIA', 'code' => '213'],
			'EC' => ['name' => 'ECUADOR', 'code' => '593'],
			'EE' => ['name' => 'ESTONIA', 'code' => '372'],
			'EG' => ['name' => 'EGYPT', 'code' => '20'],
			'EH' => ['name' => 'WESTERN SAHARA', 'code' => '212'],
			'ER' => ['name' => 'ERITREA', 'code' => '291'],
			'ES' => ['name' => 'SPAIN', 'code' => '34'],
			'ET' => ['name' => 'ETHIOPIA', 'code' => '251'],
			'FI' => ['name' => 'FINLAND', 'code' => '358'],
			'FJ' => ['name' => 'FIJI', 'code' => '679'],
			'FK' => ['name' => 'FALKLAND ISLANDS (MALVINAS)', 'code' => '500'],
			'FM' => ['name' => 'MICRONESIA, FEDERATED STATES OF', 'code' => '691'],
			'FO' => ['name' => 'FAROE ISLANDS', 'code' => '298'],
			'FR' => ['name' => 'FRANCE', 'code' => '33'],
			'GA' => ['name' => 'GABON', 'code' => '241'],
			'GB' => ['name' => 'UNITED KINGDOM', 'code' => '44'],
			'GD' => ['name' => 'GRENADA', 'code' => '1473'],
			'GE' => ['name' => 'GEORGIA', 'code' => '995'],
			'GF' => ['name' => 'FRENCH GUIANA', 'code' => '594'],
			'GG' => ['name' => 'GUERNSEY', 'code' => '44'],
			'GH' => ['name' => 'GHANA', 'code' => '233'],
			'GI' => ['name' => 'GIBRALTAR', 'code' => '350'],
			'GL' => ['name' => 'GREENLAND', 'code' => '299'],
			'GM' => ['name' => 'GAMBIA', 'code' => '220'],
			'GN' => ['name' => 'GUINEA', 'code' => '224'],
			'GP' => ['name' => 'GUADELOUPE', 'code' => '590'],
			'GQ' => ['name' => 'EQUATORIAL GUINEA', 'code' => '240'],
			'GR' => ['name' => 'GREECE', 'code' => '30'],
			'GS' => ['name' => 'SOUTH GEORGIA & SOUTH SANDWICH ISLANDS', 'code' => '500'],
			'GT' => ['name' => 'GUATEMALA', 'code' => '502'],
			'GU' => ['name' => 'GUAM', 'code' => '1671'],
			'GW' => ['name' => 'GUINEA-BISSAU', 'code' => '245'],
			'GY' => ['name' => 'GUYANA', 'code' => '592'],
			'HK' => ['name' => 'HONG KONG', 'code' => '852'],
			'HM' => ['name' => 'HEARD & MCDONALD ISLANDS', 'code' => '672'],
			'HN' => ['name' => 'HONDURAS', 'code' => '504'],
			'HR' => ['name' => 'CROATIA', 'code' => '385'],
			'HT' => ['name' => 'HAITI', 'code' => '509'],
			'HU' => ['name' => 'HUNGARY', 'code' => '36'],
			'ID' => ['name' => 'INDONESIA', 'code' => '62'],
			'IE' => ['name' => 'IRELAND', 'code' => '353'],
			'IL' => ['name' => 'ISRAEL', 'code' => '972'],
			'IM' => ['name' => 'ISLE OF MAN', 'code' => '44'],
			'IN' => ['name' => 'INDIA', 'code' => '91'],
			'IO' => ['name' => 'BRITISH INDIAN OCEAN TERRITORY', 'code' => '246'],
			'IQ' => ['name' => 'IRAQ', 'code' => '964'],
			'IR' => ['name' => 'IRAN, ISLAMIC REPUBLIC OF', 'code' => '98'],
			'IS' => ['name' => 'ICELAND', 'code' => '354'],
			'IT' => ['name' => 'ITALY', 'code' => '39'],
			'JE' => ['name' => 'JERSEY', 'code' => '44'],
			'JM' => ['name' => 'JAMAICA', 'code' => '1876'],
			'JO' => ['name' => 'JORDAN', 'code' => '962'],
			'JP' => ['name' => 'JAPAN', 'code' => '81'],
			'KE' => ['name' => 'KENYA', 'code' => '254'],
			'KG' => ['name' => 'KYRGYZSTAN', 'code' => '996'],
			'KH' => ['name' => 'CAMBODIA', 'code' => '855'],
			'KI' => ['name' => 'KIRIBATI', 'code' => '686'],
			'KM' => ['name' => 'COMOROS', 'code' => '269'],
			'KN' => ['name' => 'SAINT KITTS AND NEVIS', 'code' => '1869'],
			'KP' => ['name' => 'KOREA DEMOCRATIC PEOPLES REPUBLIC OF', 'code' => '850'],
			'KR' => ['name' => 'KOREA REPUBLIC OF', 'code' => '82'],
			'KW' => ['name' => 'KUWAIT', 'code' => '965'],
			'KY' => ['name' => 'CAYMAN ISLANDS', 'code' => '1345'],
			'KZ' => ['name' => 'KAZAKSTAN', 'code' => '7'],
			'LA' => ['name' => 'LAO PEOPLES DEMOCRATIC REPUBLIC', 'code' => '856'],
			'LB' => ['name' => 'LEBANON', 'code' => '961'],
			'LC' => ['name' => 'SAINT LUCIA', 'code' => '1758'],
			'LI' => ['name' => 'LIECHTENSTEIN', 'code' => '423'],
			'LK' => ['name' => 'SRI LANKA', 'code' => '94'],
			'LR' => ['name' => 'LIBERIA', 'code' => '231'],
			'LS' => ['name' => 'LESOTHO', 'code' => '266'],
			'LT' => ['name' => 'LITHUANIA', 'code' => '370'],
			'LU' => ['name' => 'LUXEMBOURG', 'code' => '352'],
			'LV' => ['name' => 'LATVIA', 'code' => '371'],
			'LY' => ['name' => 'LIBYAN ARAB JAMAHIRIYA', 'code' => '218'],
			'MA' => ['name' => 'MOROCCO', 'code' => '212'],
			'MC' => ['name' => 'MONACO', 'code' => '377'],
			'MD' => ['name' => 'MOLDOVA, REPUBLIC OF', 'code' => '373'],
			'ME' => ['name' => 'MONTENEGRO', 'code' => '382'],
			'MF' => ['name' => 'SAINT MARTIN', 'code' => '1599'],
			'MG' => ['name' => 'MADAGASCAR', 'code' => '261'],
			'MH' => ['name' => 'MARSHALL ISLANDS', 'code' => '692'],
			'MK' => ['name' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'code' => '389'],
			'ML' => ['name' => 'MALI', 'code' => '223'],
			'MM' => ['name' => 'MYANMAR', 'code' => '95'],
			'MN' => ['name' => 'MONGOLIA', 'code' => '976'],
			'MO' => ['name' => 'MACAU', 'code' => '853'],
			'MP' => ['name' => 'NORTHERN MARIANA ISLANDS', 'code' => '1670'],
			'MQ' => ['name' => 'MARTINIQUE', 'code' => '596'],
			'MR' => ['name' => 'MAURITANIA', 'code' => '222'],
			'MS' => ['name' => 'MONTSERRAT', 'code' => '1664'],
			'MT' => ['name' => 'MALTA', 'code' => '356'],
			'MU' => ['name' => 'MAURITIUS', 'code' => '230'],
			'MV' => ['name' => 'MALDIVES', 'code' => '960'],
			'MW' => ['name' => 'MALAWI', 'code' => '265'],
			'MX' => ['name' => 'MEXICO', 'code' => '52'],
			'MY' => ['name' => 'MALAYSIA', 'code' => '60'],
			'MZ' => ['name' => 'MOZAMBIQUE', 'code' => '258'],
			'NA' => ['name' => 'NAMIBIA', 'code' => '264'],
			'NC' => ['name' => 'NEW CALEDONIA', 'code' => '687'],
			'NE' => ['name' => 'NIGER', 'code' => '227'],
			'NF' => ['name' => 'NORFOLK ISLAND', 'code' => '672'],
			'NG' => ['name' => 'NIGERIA', 'code' => '234'],
			'NI' => ['name' => 'NICARAGUA', 'code' => '505'],
			'NL' => ['name' => 'NETHERLANDS', 'code' => '31'],
			'NO' => ['name' => 'NORWAY', 'code' => '47'],
			'NP' => ['name' => 'NEPAL', 'code' => '977'],
			'NR' => ['name' => 'NAURU', 'code' => '674'],
			'NU' => ['name' => 'NIUE', 'code' => '683'],
			'NZ' => ['name' => 'NEW ZEALAND', 'code' => '64'],
			'OM' => ['name' => 'OMAN', 'code' => '968'],
			'PA' => ['name' => 'PANAMA', 'code' => '507'],
			'PE' => ['name' => 'PERU', 'code' => '51'],
			'PF' => ['name' => 'FRENCH POLYNESIA', 'code' => '689'],
			'PG' => ['name' => 'PAPUA NEW GUINEA', 'code' => '675'],
			'PH' => ['name' => 'PHILIPPINES', 'code' => '63'],
			'PK' => ['name' => 'PAKISTAN', 'code' => '92'],
			'PL' => ['name' => 'POLAND', 'code' => '48'],
			'PM' => ['name' => 'SAINT PIERRE AND MIQUELON', 'code' => '508'],
			'PN' => ['name' => 'PITCAIRN', 'code' => '870'],
			'PR' => ['name' => 'PUERTO RICO', 'code' => '1'],
			'PS' => ['name' => 'PALESTINE', 'code' => '970'],
			'PT' => ['name' => 'PORTUGAL', 'code' => '351'],
			'PW' => ['name' => 'PALAU', 'code' => '680'],
			'PY' => ['name' => 'PARAGUAY', 'code' => '595'],
			'QA' => ['name' => 'QATAR', 'code' => '974'],
			'RE' => ['name' => 'RÉUNION', 'code' => '262'],
			'RO' => ['name' => 'ROMANIA', 'code' => '40'],
			'RS' => ['name' => 'SERBIA', 'code' => '381'],
			'RU' => ['name' => 'RUSSIAN FEDERATION', 'code' => '7'],
			'RW' => ['name' => 'RWANDA', 'code' => '250'],
			'SA' => ['name' => 'SAUDI ARABIA', 'code' => '966'],
			'SB' => ['name' => 'SOLOMON ISLANDS', 'code' => '677'],
			'SC' => ['name' => 'SEYCHELLES', 'code' => '248'],
			'SD' => ['name' => 'SUDAN', 'code' => '249'],
			'SE' => ['name' => 'SWEDEN', 'code' => '46'],
			'SG' => ['name' => 'SINGAPORE', 'code' => '65'],
			'SH' => ['name' => 'SAINT HELENA', 'code' => '290'],
			'SI' => ['name' => 'SLOVENIA', 'code' => '386'],
			'SJ' => ['name' => 'SVALBARD & JAN MAYEN', 'code' => '47'],
			'SK' => ['name' => 'SLOVAKIA', 'code' => '421'],
			'SL' => ['name' => 'SIERRA LEONE', 'code' => '232'],
			'SM' => ['name' => 'SAN MARINO', 'code' => '378'],
			'SN' => ['name' => 'SENEGAL', 'code' => '221'],
			'SO' => ['name' => 'SOMALIA', 'code' => '252'],
			'SR' => ['name' => 'SURINAME', 'code' => '597'],
			'SS' => ['name' => 'SOUTH SUDAN', 'code' => '211'],
			'ST' => ['name' => 'SAO TOME AND PRINCIPE', 'code' => '239'],
			'SV' => ['name' => 'EL SALVADOR', 'code' => '503'],
			'SX' => ['name' => 'SINT MAARTEN', 'code' => '1721'],
			'SY' => ['name' => 'SYRIAN ARAB REPUBLIC', 'code' => '963'],
			'SZ' => ['name' => 'SWAZILAND', 'code' => '268'],
			'TC' => ['name' => 'TURKS AND CAICOS ISLANDS', 'code' => '1649'],
			'TD' => ['name' => 'CHAD', 'code' => '235'],
			'TF' => ['name' => 'FRENCH SOUTHERN TERRITORIES ', 'code' => '262'],
			'TG' => ['name' => 'TOGO', 'code' => '228'],
			'TH' => ['name' => 'THAILAND', 'code' => '66'],
			'TJ' => ['name' => 'TAJIKISTAN', 'code' => '992'],
			'TK' => ['name' => 'TOKELAU', 'code' => '690'],
			'TL' => ['name' => 'TIMOR-LESTE', 'code' => '670'],
			'TM' => ['name' => 'TURKMENISTAN', 'code' => '993'],
			'TN' => ['name' => 'TUNISIA', 'code' => '216'],
			'TO' => ['name' => 'TONGA', 'code' => '676'],
			'TR' => ['name' => 'TURKEY', 'code' => '90'],
			'TT' => ['name' => 'TRINIDAD AND TOBAGO', 'code' => '1868'],
			'TV' => ['name' => 'TUVALU', 'code' => '688'],
			'TW' => ['name' => 'TAIWAN, PROVINCE OF CHINA', 'code' => '886'],
			'TZ' => ['name' => 'TANZANIA, UNITED REPUBLIC OF', 'code' => '255'],
			'UA' => ['name' => 'UKRAINE', 'code' => '380'],
			'UG' => ['name' => 'UGANDA', 'code' => '256'],
			'UM' => ['name' => 'U.S. OUTLYING ISLANDS', 'code' => '1'],
			'US' => ['name' => 'UNITED STATES', 'code' => '1'],
			'UY' => ['name' => 'URUGUAY', 'code' => '598'],
			'UZ' => ['name' => 'UZBEKISTAN', 'code' => '998'],
			'VA' => ['name' => 'HOLY SEE (VATICAN CITY STATE)', 'code' => '39'],
			'VC' => ['name' => 'SAINT VINCENT AND THE GRENADINES', 'code' => '1784'],
			'VE' => ['name' => 'VENEZUELA', 'code' => '58'],
			'VG' => ['name' => 'VIRGIN ISLANDS, BRITISH', 'code' => '1284'],
			'VI' => ['name' => 'VIRGIN ISLANDS, U.S.', 'code' => '1340'],
			'VN' => ['name' => 'VIETNAM', 'code' => '84'],
			'VU' => ['name' => 'VANUATU', 'code' => '678'],
			'WF' => ['name' => 'WALLIS AND FUTUNA', 'code' => '681'],
			'WS' => ['name' => 'SAMOA', 'code' => '685'],
			'XK' => ['name' => 'KOSOVO', 'code' => '383'],
			'YE' => ['name' => 'YEMEN', 'code' => '967'],
			'YT' => ['name' => 'MAYOTTE', 'code' => '262'],
			'ZA' => ['name' => 'SOUTH AFRICA', 'code' => '27'],
			'ZM' => ['name' => 'ZAMBIA', 'code' => '260'],
			'ZW' => ['name' => 'ZIMBABWE', 'code' => '263'],
		];
	}

	/**
	 * Query sysadmin module for default acp (Admin Control Panel) ports and wheter SSL is forced or not
	 *
	 * @return	array				['forcessl' => bool, 'port' => int]
	 * @throws	Exception			If some error happens while querying the data or executing the regex
	 */
	private function getSysAdminPorts()
	{
		exec('fwconsole sysadmin ports', $out); //retrieve ports from sysadmin

		foreach ($out as $line) {
			if (!isset($acp) || empty($acp))
				preg_match('/\|\s*(\S*)\s*\|\s*acp\s*\|\s*(\S*).*/', $line, $acp); //matches the http port of web UI
			if (!isset($sslacp) || empty($sslacp))
				preg_match('/\|\s*(\S*)\s*\|\s*sslacp.*/', $line, $sslacp); //matches the https port of web UI
		}

		if (!isset($acp[1]) || !isset($acp[2]) || !isset($sslacp[1]))
			throw new Exception(_('Failed to find default ports for sysadmin!'));

		$forceSSL = strcasecmp($acp[2], 'Enabled') === 0;

		return [
			'forcessl' => $forceSSL,
			'port' => $forceSSL ? $sslacp[1] : $acp[1]
		];
	}
}
