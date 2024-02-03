<?php
/*
 * CardDAV Middleware UI
 * Written by Massi-X <firemetris@gmail.com> © 2023
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

namespace FreePBX\modules\PhoneMiddleware;

use FreePBX\modules\Backup as Base;

class Backup extends Base\BackupBase
{
	public function runBackup($id, $transaction)
	{
		$Core = $this->FreePBX->PhoneMiddleware->getCore(); //get instance beacuse I cannot access statically here

		if (method_exists($Core, 'run_backup')) {
			$Core->run_backup($this);
		} else
			throw new \Exception(_('Backup is not implemented!'));
	}
}
