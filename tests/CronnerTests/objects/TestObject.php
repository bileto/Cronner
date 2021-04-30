<?php

declare(strict_types=1);

namespace stekycz\Cronner\tests\objects;


class TestObject
{
	use \Nette\SmartObject;


	/**
	 * @cronner-task
	 * @cronner-period 1 day
	 */
	public function __construct()
	{
	}


	/**
	 * @cronner-task E-mail notifications
	 * @cronner-period 5 minutes
	 */
	public function test01()
	{
	}


	/**
	 * @cronner-task
	 * @cronner-period 1 hour
	 * @cronner-days Mon, Wed, Fri
	 * @cronner-time 09:00 - 10:00, 15:00 - 16:00
	 */
	public function test02()
	{
	}


	/**
	 * @cronner-task Test 3
	 * @cronner-period 17 minutes
	 * @cronner-days working days
	 * @cronner-time 09:00 - 10:45
	 */
	public function test03()
	{
	}


	/**
	 * @cronner-task Test 4
	 * @cronner-period 1 day
	 * @cronner-days weekend
	 */
	public function test04()
	{
	}
}
