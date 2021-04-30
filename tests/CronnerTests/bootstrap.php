<?php

declare(strict_types=1);

$autoloader = require_once __DIR__ . '/../../vendor/autoload.php';

define("TEST_DIR", __DIR__);
define("TEMP_DIR", TEST_DIR . '/../tmp/' . (isset($_SERVER['argv']) ? md5(serialize($_SERVER['argv'])) : getmypid()));
Tester\Environment::setup();

function run(Tester\TestCase $testCase)
{
	$testCase->run(isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null);
}

abstract class TestCase extends Tester\TestCase
{
	protected function tearDown()
	{
		Mockery::close();
	}
}

return $autoloader;
