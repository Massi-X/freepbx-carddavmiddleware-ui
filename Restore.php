<?php
/*
 * CardDAV Middleware UI
 * Written by Massi-X <support@massi-x.dev> © 2024
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

namespace FreePBX\modules\PhoneMiddleware;

use FreePBX\modules\Backup as Base;

class Restore extends Base\RestoreBase
{
	public function runRestore()
	{
		$Core = $this->FreePBX->PhoneMiddleware->getCore(); //get instance beacuse I cannot access statically here

		if (method_exists($Core, 'run_restore')) {
			$Core->run_restore($this, $this->getVersion());
		} else
			throw new \Exception(_('Retore is not implemented!'));
	}
}
