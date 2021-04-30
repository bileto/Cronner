<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\Tasks;


use Mockery;
use Nette;
use Nette\Reflection\Method;
use stekycz\Cronner\ITimestampStorage;
use stekycz\Cronner\Tasks\Task;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

class TaskTest extends \TestCase
{

	/** @var object */
	private $object;


	public function testInvokesTaskWithSavingLastRunTime()
	{
		$now = new Nette\Utils\DateTime();
		$timestampStorage = Mockery::mock(ITimestampStorage::class);
		$timestampStorage->shouldReceive("saveRunTime")->with($now)->once();
		$timestampStorage->shouldReceive("setTaskName")->times(2);

		$method = new Method($this->object, 'test01');
		$task = new Task($this->object, $method, $timestampStorage);
		$task($now);
		Assert::$counter++; // Hack for nette tester
	}


	/**
	 * @dataProvider dataProviderShouldBeRun
	 * @param bool $expected
	 * @param int $loads
	 * @param string $methodName
	 * @param string $now
	 * @param string|null $lastRunTime
	 */
	public function testChecksIfCanBeRun(bool $expected, int $loads, string $methodName, string $now, string $lastRunTime = null)
	{
		$now = new Nette\Utils\DateTime($now);
		$lastRunTime = $lastRunTime ? new Nette\Utils\DateTime($lastRunTime) : null;

		$method = (new \Nette\Reflection\ClassType($this->object))->getMethod($methodName);

		$timestampStorage = Mockery::mock(ITimestampStorage::class);
		$timestampStorage->shouldReceive("loadLastRunTime")->times($loads)->andReturn($lastRunTime);
		$timestampStorage->shouldReceive("setTaskName")->atLeast(1);

		$task = new Task($this->object, $method, $timestampStorage);
		Assert::same($expected, $task->shouldBeRun($now));
	}


	public function dataProviderShouldBeRun(): array
	{
		return [
			// Test 01
			[true, 1, 'test01', '2013-02-01 12:00:00', null],
			[true, 1, 'test01', '2013-02-01 12:10:00', '2013-02-01 12:00:00'],
			[false, 1, 'test01', '2013-02-01 12:04:00', '2013-02-01 12:00:00'],
			// Test 02
			[false, 0, 'test02', '2013-02-05 12:00:00', null],
			[false, 0, 'test02', '2013-02-04 12:00:00', null],
			[false, 1, 'test02', '2013-02-04 09:30:00', '2013-02-04 09:00:00'],
			[true, 1, 'test02', '2013-02-04 09:30:00', null],
			[true, 1, 'test02', '2013-02-04 09:30:00', '2013-02-03 15:30:00'],
		];
	}


	public function testShouldBeRunOnShortLaterRun()
	{
		$timestampStorage = Mockery::mock(ITimestampStorage::class);
		$timestampStorage->shouldReceive("loadLastRunTime")->once()->andReturn(new Nette\Utils\DateTime('2014-08-15 09:00:01'));
		$timestampStorage->shouldReceive("setTaskName")->atLeast(1);

		$method = new Method($this->object, 'test03');
		$task = new Task($this->object, $method, $timestampStorage);
		Assert::true($task->shouldBeRun(new Nette\Utils\DateTime('2014-08-15 09:17:00')));
	}


	protected function setUp()
	{
		parent::setUp();
		$this->object = new TestObject();
	}
}

run(new TaskTest());
