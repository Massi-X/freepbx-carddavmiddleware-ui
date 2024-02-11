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
	 * @return string		version number
	 */
	public static function get_version()
	{
		$xml = simplexml_load_file(__DIR__ . "/module.xml"); //this loads the value set by build script in CM UI
		return $xml ? $xml->version : 0;
	}

	/**
	 * Same as file_put_contents, but automatically handle folder creation and related errors.
	 *
	 * @param	string		$filename	See @file_put_contents for description
	 * @param	mixed		$data		See @file_put_contents for description
	 * @param	int			$flags		See @file_put_contents for description
	 * @param				$context	See @file_put_contents for description
	 * @return	int|false				The function returns false in case of failure creating the folder structure or returns the result of @file_put_contents.
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
	 * Check connection to a server, throws an Exception in case any error occurs with a friendly description ready to print.
	 *
	 * @param	string		$url		The URL to check against
	 * @param	bool		$skip_ssl	If you want to skip SSL checks and errors
	 * @return	boolean				Always true
	 * @throws	Exception			If the connection failed for any reason
	 */
	public static function check_connection($url, $skip_ssl = false)
	{
		$ch = curl_init();

		try {
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36');
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_AUTOREFERER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			if ($skip_ssl) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			}
			$html = curl_exec($ch);

			if (!$html)
				throw new Exception();

			//no errors? OK we can proceed!
		} catch (Throwable $t) { //something went wrong
			$message = curl_error($ch);
			$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

			//Those are the only messages parsed for now, could be expanded in the future with your help!
			if (stripos($message, 'SSL received a record that exceeded the maximum permissible length') !== false) //SSL error
				$message = _('Unable to estabilish a secure connection. Are you connecting to an http server over https?');
			else if (stripos($message, 'Peer\'s Certificate has expired') !== false) //SSL error
				$message = _('Unable to estabilish a secure connection. Please ensure that the server certificate is valid and not expired.');
			else if (stripos($message, 'Network is unreachable') !== false) //no network
				$message = _('The network is unreachable.');
			else if (stripos($message, 'Connection refused') !== false) //connection refused
				$message = _('Connection to the server refused.');
			else if (stripos($message, 'This function may only be used against URLs') !== false) //connection refused
				$message = _('This does not seem to be an URL. Did you include the protocol?');
			else if ($code == 404) //404
				$message = str_replace('%code', '404 Not Found', _('%code error.'));
			else if ($code == 403) //403
				$message = str_replace('%code', '403 Forbidden', _('%code error.'));

			if ($code != 401) //unathorized is fine, this is not the point of the check
				throw new Exception($message);
		}

		return true;
	}

	/**
	 * BMO Function
	 * Get email "To" address from fpbx config
	 * 
	 * @param	FreePBX		$FreePBX	BMO object
	 * @return 	string					The registered email address
	 * @throws	Exception				If the "To" email address is not set
	 */
	public static function get_fpbx_to_email_config($FreePBX)
	{
		if (!is_a($FreePBX, 'FreePBX', true))
			throw new Exception(_('Not given a FreePBX Object'));

		$to = (new FreePBX\Builtin\UpdateManager())->getCurrentUpdateSettings()['notification_emails'];
		if (empty($to))
			throw new Exception(_('"To" field is empty.'));

		return $to;
	}

	/**
	 * BMO Function
	 * Returns the server name extracted from shell.
	 *
	 * @param	FreePBX		$FreePBX	BMO object
	 * @return	string					Server name or 'Unknown' in case of errors
	 * @throws	Exception				If $FreePBX is not the BMO object
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
	 * @param	FreePBX		$FreePBX	BMO object
	 * @param	string		$to			"To" address
	 * @param	string		$subject	Subject of email
	 * @param	string		$html_txt	Message of email formatted in html
	 * @param	string		$from_name	"From" name. Defaults to the email itself (WARNING! only the name, not the formatted "name <email>")
	 * @return	boolean					True in case of success, false otherwise
	 * @throws	Exception				If $FreePBX is not the BMO object or from field can't be retrieved
	 */
	public static function send_mail($FreePBX, $to, $subject, $html_txt, $from_name = '')
	{
		if (!is_a($FreePBX, 'FreePBX', true))
			throw new Exception(_('Not given a FreePBX Object'));

		if (function_exists('fetchFromEmail'))
			$from_email = call_user_func('fetchFromEmail');

		if (!isset($from_email) || empty($from_email))
			throw new Exception(_('Could not retrieve FROM field!'));

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
