<?php
/*
 * CardDAV Middleware UI
 * Written by Massi-X <support@massi-x.dev> © 2024
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

namespace FreePBX\modules\PhoneMiddleware;

/**
 * Helper class to manage background tasks.
 * To test job: runuser -l asterisk -c "fwconsole job --run [ID] --quiet". Do not run tests as root or you will get unexpected results
 */

require_once __DIR__ . '/core/core.php';

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Job implements \FreePBX\Job\TaskInterface
{
	public static function run(InputInterface $input, OutputInterface $output)
	{
		if (method_exists(\Core::class, 'run_job'))
			\Core::run_job(\FreePBX::create(), $input, $output); //you MUST manage timing yourself (this is called every minute) and you MUST catch any exception to prevent log spamming (except when it is a very serious one!)

		return true; //if YOU developer forget about it...
	}
}
