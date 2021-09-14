<?php

namespace App\CoreModule\AuthModule;

use App\Module\Module;

/**
 * Class AuthModule
 * @package App\CoreModule\AuthModule
 */
class AuthModule extends Module
{
	protected static string $name = 'Authentication module';
	protected static string $description = <<<TEXT
User login, logout
TEXT;
	public static function getDir()
	{
		return __DIR__;
	}
}
