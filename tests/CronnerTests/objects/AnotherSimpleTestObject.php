<?php

declare(strict_types=1);

namespace stekycz\Cronner\tests\objects;


class AnotherSimpleTestObject
{
	use \Nette\SmartObject;


	/**
	 * @cronner-task Test
	 */
	public function test01()
	{
	}
}
