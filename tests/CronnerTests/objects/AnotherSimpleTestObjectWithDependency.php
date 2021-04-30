<?php

namespace stekycz\Cronner\tests\objects;


class AnotherSimpleTestObjectWithDependency
{
	public function __construct(FooService $service)
	{
	}


	/**
	 * @cronner-task
	 */
	public function run()
	{
	}
}
