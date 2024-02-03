<?php
/*
 * CardDAV Middleware UI
 * Written by Massi-X <firemetris@gmail.com> © 2023
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

/**
 * Helper class to manage some BMO functions and other small useful things.
 **/

class Utilities
{
	//list of strings that will be replaced by the script when sending an email (for now only one)
	public const MAIL_SERVER_NAME = '%server_name';

	//other constants
	const SUPERFECTA_SCHEME = 'carddavmiddleware';
	const SUPERFECTA_SCHEME_CONFIG = [
		'POST_Data' => '"number" = "$thenumber"',
		'Regular_Expressions' => '/<name>(.*?)<\\/name>/m',
		'SPAM_Regular_Expressions' => '/<spam>(true)<\\/spam>[\\s\\S]*<threshold>(.*?)<\\/threshold>/m'
	];

	/**
	 * Get module version. Returns 0 in case of unknown failure
	 * 
	 * @return string	version number
	 */
	public static function get_version()
	{
		$xml = simplexml_load_file(__DIR__ . "/module.xml"); //this loads the value set by build script in CM UI
		return $xml ? $xml->version : 0;
	}

	/**
	 * Same as file_put_contents, but automatically handle folder creation and related errors.
	 *
	 * @param	string	$filename	See @file_put_contents for description
	 * @param	mixed	$data		See @file_put_contents for description
	 * @param	int		$flags		See @file_put_contents for description
	 * @param			$context	See @file_put_contents for description
	 * @return	int|false			The function returns false in case of failure creating the folder structure or returns the result of @file_put_contents.
	 */
	public static function file_put_contents_i($filename, $data, $flags = 0, $context = null)
	{
		$dirname = dirname($filename);

		if (!is_dir($dirname) && !mkdir($dirname))
			return false;

		return file_put_contents($filename, $data, $flags, $context);
	}

	/**
	 * Deletes a directory recursively. Does not follow symlinks.
	 *
	 * @param	string		$dirPath	Path to dir
	 * @return	boolean					true if success, false otherwise
	 * @throws	Exception				If the provided path is not a directory
	 */
	public static function delete_dir($dirPath)
	{
		if (!is_dir($dirPath) || is_link($dirPath))
			throw new Exception("$dirPath must be a directory");

		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')
			$dirPath .= '/';

		$files = array_diff(scandir($dirPath), array('..', '.'));

		foreach ($files as $file) {
			$file = $dirPath . $file;
			if (!is_link($file) && is_dir($file))
				self::delete_dir($file);
			else
				unlink($file);
		}
		return rmdir($dirPath);
	}

	/**
	 * BMO Function
	 * Get email "To" and "From" addresses from fpbx config
	 * 
	 * @param	FreePBX	$FreePBX	BMO object
	 * @return 	array			the registered email address in the format ["To" => "...", "From" => "..."]
	 * @throws	Exception		If the "To" (and only "To") email address is not set
	 */
	public static function get_fpbx_registered_email_config($FreePBX)
	{
		if (!is_a($FreePBX, 'FreePBX', true))
			throw new Exception(_('Not given a FreePBX Object'));

		$to = (new FreePBX\Builtin\UpdateManager())->getCurrentUpdateSettings()['notification_emails'];
		if (empty($to))
			throw new Exception(_('"To" field is empty.'));

		$from = $FreePBX->Config()->get('AMPUSERMANEMAILFROM');
		if (empty($from))
			$from = null; //make sure to null it so the caller will have a consistent result

		return ["To" => $to, "From" => $from];
	}

	/**
	 * BMO Function
	 * Returns the server name extracted from shell.
	 *
	 * @param	FreePBX	$FreePBX	BMO object
	 * @return	string				Server name or 'Unknown' in case of errors
	 */
	public static function get_server_name($FreePBX)
	{
		if (!is_a($FreePBX, 'FreePBX', true))
			throw new Exception(_('Not given a FreePBX Object'));

		$serverName = trim($FreePBX->Config()->get('FREEPBX_SYSTEM_IDENT'));

		if (empty($serverName))
			$serverName = 'Unknown';

		return $serverName;
	}

	/**
	 * BMO Function
	 * Construct the email body.
	 *
	 * @param	FreePBX	$FreePBX	BMO object
	 * @param	string		$to			"To" address
	 * @param	string		$subject	Subject of email
	 * @param	string		$html_txt	Message of email formatted in html
	 * @param	string		$from_name	"From" name. Defaults to the email itself (WARNING! only the name, not the formatted "name <email>")
	 * @return	boolean					True in case of success, false otherwise
	 */
	public static function send_mail($FreePBX, $to, $subject, $html_txt, $from_name = '')
	{
		if (!is_a($FreePBX, 'FreePBX', true))
			throw new Exception(_('Not given a FreePBX Object'));

		//this is all taken from BMO/Mail.class.php
		$from_email = get_current_user() . '@' . gethostname();

		//sysadmin allows to change "from" address
		if (function_exists('sysadmin_get_storage_email')) {
			$emails = call_user_func('sysadmin_get_storage_email');
			//Check that what we got back above is a email address
			if (!empty($emails['fromemail']) && filter_var($emails['fromemail'], FILTER_VALIDATE_EMAIL)) {
				//Fallback address
				$from_email = $emails['fromemail'];
			}
		}

		//set sender name to the address if nothing provided
		if (empty($from_name))
			$from_name = $from_email;

		//replace strings matching the pattern (yeah for now I have only this so it's rather simple...)
		$to = str_replace(self::MAIL_SERVER_NAME, self::get_server_name($FreePBX), $to);
		$subject = str_replace(self::MAIL_SERVER_NAME, self::get_server_name($FreePBX), $subject);
		$html_txt = str_replace(self::MAIL_SERVER_NAME, self::get_server_name($FreePBX), $html_txt);
		$from_name = str_replace(self::MAIL_SERVER_NAME, self::get_server_name($FreePBX), $from_name);

		$headers =
			"MIME-Version: 1.0\r\n" .
			"Content-type: text/html; charset=UTF-8\r\n" .
			"From: $from_name <$from_email>\r\n";

		return mail($to, $subject, $html_txt, $headers);
	}
}