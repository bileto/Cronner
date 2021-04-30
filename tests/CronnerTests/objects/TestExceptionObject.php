<?php

declare(strict_types=1);

namespace stekycz\Cronner\tests\objects;


use Exception;

class TestExceptionObject
{
	use \Nette\SmartObject;


	/**
	 * @cronner-task
	 * @cronner-period 5 minutes
	 */
	public function test01()
	{
		throw new Exception('Test 01');
	}


	/**
	 * @cronner-task
	 * @cronner-period 5 minutes
	 */
	public function test02()
	{
	}
}
