<?php
/*
 * CardDAV Middleware UI
 * Written by Massi-X <support@massi-x.dev> © 2024
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

/******************************************************************************/
/***	These are the methods to implement to create a working Core class	***/
/******************************************************************************/

interface CoreInterface
{
	public static function getInstance(): self; //return a Core instance
	public function init(): void; //initialize anything you might need to

	//getter and setter for various parameters settable in UI. BMO class will get the values and pass them back through these methods. Any logic should be implemented at your wish.
	public static function get_url(): string; //server url
	public static function set_url(string $url): void;
	public static function get_carddav_addressbooks(): array; //addressbooks URIs
	public static function set_carddav_addressbooks(array $carddav_addressbooks): void;
	public static function get_auth(): array; //carddav auth info
	public static function set_auth(string $username, string $password): void;
	public static function get_cache_expire(): int; //cache duration
	public static function set_cache_expire(int $expire): void;
	public static function get_country_code(): string; //country code
	public static function set_country_code(string $code): void;
	public static function get_output_construct(): string; //format of the CNAM output
	public static function set_output_construct(string $output_construct): void;
	public static function get_max_cnam_length(): int; //CNAM max allowed length
	public static function set_max_cnam_length(int $max_cnam_length): void;
	public static function get_phone_type(): int; //user device type
	public static function set_phone_type(int $phone_type): void;
	public static function get_mail_level(): array; //importance level when notifications will be sent also via mail
	public static function set_mail_level(array $types): void;
	public static function get_superfecta_compat(): bool; //superfecta compatibility switch
	public static function set_superfecta_compat(bool $superfecta_compat): void;
	public static function get_spam_match(): bool; //spam match report
	public static function set_spam_match(bool $spam_match): void;

	public function store_config(): bool; //called when the UI class wants to store the data (i.e. when the user clicks "Apply")
	public static function delete_cache(): bool; //if you have any cache, this method is called to invalidate it if there is any breaking change.

	public function getXMLforPhones(bool $force = false, int $type = -1): string; //returns a well formatted xml phonebook that can be read by a phone/other device. $force to force refresh. $type to specify a phone type only for this request (from  CoreInterface.PHONE_TYPE)
	public function getCNFromPhone(string $number, bool $force = false); //returns a CNAM (name) given a phone number. You should really not use $force because the result must come as fast as possible - output type should be string|null (not supported in PHP7)

	public function discover_addressbooks_to_display(): array; //returns an array of addressbooks based on the current url, username and password. This s used by the UI in conjunction with get_carddav_addressbooks() to create a list of the current active/inactive addressbooks.
	public static function sendUINotification(int $type, string $message, int $id, int $flags): void; //send a notification that will be displayed in the top right corner of the UI. $type = the type (see constants below), $message = the text of the notification, $id = unique id, $flags = flags (see constants below).
	public static function retrieveUINotifications(): array; //retrieve an array of UI notifications so that UI can display it
	public static function deleteUINotification(int $id): bool; //delete a notification by ID
	public static function deleteAllUINotifications(): bool; //delete all the notifications

	/******************************************************************************/
	/***					Do not modify/delete constants!						***/
	/******************************************************************************/

	//if new are added remember to update getXMLforPhones + BMO class with new languages. MUST BE CONSECUTIVE and >= 1
	public const PHONE_TYPE_NO_LIMITS = 1;
	public const PHONE_TYPE_FANVIL = 2;
	public const PHONE_TYPE_SNOM = 3;
	//frendly names that HAVE TO correspond to the IDs (= array index) above. Mainly used fot GET requests (they cannot be translated)
	public const PHONE_TYPES = [
		null, //this is not defined but may be used in the future
		'UNLIMITED', //=PHONE_TYPE_NO_LIMITS
		'FANVIL', //=PHONE_TYPE_FANVIL
		'SNOM' //=PHONE_TYPE_SNOM
	];

	//notification type and options constants. You can put below fixed notification IDs (> 0 && < 1000)
	public const NOTIFICATION_TYPE_VERBOSE = 1; //this is a verbose message
	public const NOTIFICATION_TYPE_ERROR = 2; //this is an error message
	public const NOTIFICATION_TYPE_INFO = 3; //this is an info message
	public const NOTIFICATION_FLAG_NO_MAIL = 0b0001; //overwrite mail send if needed
}

/******************************************************************************/
/***			These are optional methods of your Core class				***/
/******************************************************************************/

interface Branding
{
	public static function get_module_name(): string; //return here your module name to be displayed in UI
	public static function get_author(): string; //return here your name to be displayed in UI
	public static function get_readme_url(): string; //return here your readme URL if you have one. Must be a valid HTML <a href=...>
}

interface Legal
{
	public static function get_license_to_display(): array; //if you have a license, you should have this method too. returns ['description' => footer text with '%linkstart' and '%Linkend' anchors to be replaced for license link, 'text' => full text of the license, 'title' => title of the dialog]
	public static function get_libraries_to_display(): array; //any library you used. returns array of arrays containing ['name' => library name, 'url' => library url]
}

interface Footer
{
	public static function get_additional_footer(): string; //any additional information to print in footer
}

interface Help
{
	public static function get_help(): array; //return an array of raw strings containing help informations to print for the user. You should use the standard format: <b>title:</b>text...
}

interface SSL
{
	public static function get_ssl_enabled(): bool; //if you want to give the user permission to bypass the SSL this is the method for you! Will enable a checkbox in the carddav popup.
	public static function set_ssl_enabled(bool $enabled): void; //store the current state of SSL validation
}

interface Activation
{
	public static function get_purchase_buttons(): string; //return a well formatted HTML string containing your purchase/donation button(s). Include any <script> or <style> you want to use here. You can take advantage of the ajax calls createorder (that will call create_order()), validatepurchase (validate_purchase()) and restorepurchase (restore_purchase())
	/**
	 * @throws Exception in case of errors
	 */
	public static function create_order(): array; //this function is called by ajax. Return array formatted like ["result" => true/false, "message" => ...]
	/**
	 * @throws Exception in case of errors
	 */
	public static function validate_purchase(array $POST, string $php_input): array; //this function is called by ajax and gives you the $_POST array and php_input data (if any). Return array formatted like ["result" => true/false, "message" => ...]
	/**
	 * @throws Exception in case of errors
	 */
	public static function restore_purchase(array $POST, string $php_input): array; //this function is called by ajax and gives you the $_POST array and php_input data (if any). Return array formatted like ["result" => true/false, "message" => ...]
}


interface InstallUninstall
{
	public static function post_install_hook(\FreePBX $FreePBX): void; //hooks to be executed on module install. NB! Do not throw Exceptions if not necessary or the installation will fail!
	public static function post_uninstall_hook(\FreePBX $FreePBX): void; //hooks to be executed on module uninstall. Any thrown exception will be catched and a warning will be printed. Uninstallation will succeed anyway
}

interface BackupRestore
{
	public static function run_backup(object $backupInstance): void; //place here your code to be executed when the user request a backup. instance is the backup instance, see https://wiki.freepbx.org/display/FOP/Implementing+Backup for the methods
	public static function run_restore(object $restoreInstance, string $version): void; //place here your code to be executed when the user request a restore. instance is the restore instance, see https://wiki.freepbx.org/display/FOP/Implementing+Backup for the methods
}

interface Scheduler
{
	public static function run_job(\FreePBX $FreePBX, Symfony\Component\Console\Input\InputInterface $input, Symfony\Component\Console\Output\OutputInterface $output): bool; //run a periodic Job automatically. This method is called every minute and you must implement a logic to prevent it from running over and over again. $FreePBX = BMO object, $input = Symfony InputInterface, $output = Symfony OutputInterface
}
