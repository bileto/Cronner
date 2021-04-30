<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\Cronner\tests;


use Bileto\CriticalSection\ICriticalSection;
use Exception;
use Mockery;
use Nette\Utils\DateTime;
use stdClass;
use stekycz\Cronner\Cronner;
use stekycz\Cronner\Exceptions\DuplicateTaskNameException;
use stekycz\Cronner\ITimestampStorage;
use stekycz\Cronner\Tasks\Task;
use stekycz\Cronner\tests\objects\AnotherSimpleTestObject;
use stekycz\Cronner\tests\objects\NextSimpleTestObject;
use stekycz\Cronner\tests\objects\SameTaskNameObject;
use stekycz\Cronner\tests\objects\TestExceptionObject;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;

require_once(__DIR__ . "/bootstrap.php");

class CronnerTest extends \TestCase
{

	/** @var Cronner */
	private $cronner;

	/** @var ITimestampStorage */
	private $timestampStorage;


	/**
	 * @dataProvider dataProviderSetMaxExecutionTime
	 * @param int|null $expected
	 * @param int|null $value
	 */
	public function testCanSetMaxExecutionTime(int $expected = null, int $value = null)
	{
		$this->cronner->setMaxExecutionTime($value);
		Assert::same($expected, $this->cronner->getMaxExecutionTime());
	}


	public function dataProviderSetMaxExecutionTime(): array
	{
		return [
			[1234, 1234],
			[null, null],
		];
	}


	/**
	 * @dataProvider dataProviderSetMaxExecutionTimeError
	 * @throws \TypeError
	 */
	public function testThrowsExceptionOnWrongTypeOfMaxExecutionTime($value)
	{
		$this->cronner->setMaxExecutionTime($value);
	}


	public function dataProviderSetMaxExecutionTimeError()
	{
		return [
			['-1234'],
			['0'],
			[-2.5],
			['-2.5'],
			[0.0],
			['0.0'],
			['nejaky blabol'],
			[true],
			[false],
			[new stdClass()],
		];
	}


	/**
	 * @dataProvider dataProviderSetMaxExecutionTimeWrongValue
	 * @throws \stekycz\Cronner\Exceptions\InvalidArgumentException
	 */
	public function testThrowsExceptionOnWrongValueOfMaxExecutionTime($value)
	{
		$this->cronner->setMaxExecutionTime($value);
	}


	public function dataProviderSetMaxExecutionTimeWrongValue()
	{
		return [
			[-1234],
			[0],
		];
	}


	public function testAcceptsTasksObjectWithTaskMethods()
	{
		$this->cronner->addTasks(new stdClass());
		Assert::equal(1, $this->cronner->countTaskObjects());
	}


	/**
	 * @throws \stekycz\Cronner\Exceptions\InvalidArgumentException
	 */
	public function testThrowsExceptionOnDuplicateTasksObjectAddition()
	{
		$tasks = new stdClass();
		$this->cronner->addTasks($tasks);
		$this->cronner->addTasks($tasks);
	}


	public function testProcessesAllAddedTasks()
	{
		$now = new DateTime('2013-02-04 09:30:00');
		$tasks = new TestObject();

		$this->timestampStorage->shouldReceive('setTaskName')->atLeast(8);
		$this->timestampStorage->shouldReceive('saveRunTime')->with($now)->atLeast(1);

		$this->cronner->addTasks($tasks);
		Assert::equal(4, $this->cronner->countTasks());
		$this->cronner->run($now);
	}


	public function testCanSetOnTaskBeginCallback()
	{
		$this->cronner->onTaskBegin[] = function (Cronner $cronner, Task $task) {
			// This method is dummy
		};
		Assert::equal(1, count($this->cronner->onTaskBegin));
	}


	public function testCanSetOnTaskFinishedCallback()
	{
		$this->cronner->onTaskFinished[] = function (Cronner $cronner, Task $task) {
			// This method is dummy
		};
		Assert::equal(1, count($this->cronner->onTaskFinished));
	}


	public function testCanSetOnTaskErrorCallback()
	{
		$this->cronner->onTaskError[] = function (Cronner $cronner, Exception $e, Task $task) {
			// This method is dummy
		};
		Assert::equal(1, count($this->cronner->onTaskError));
	}


	public function testIsAbleToContinueWithNextTaskWhenOneTaskThrowException()
	{
		$now = new DateTime('2013-02-04 09:30:00');

		$logCallback = Mockery::mock(stdClass::class);
		$logCallback->shouldReceive('logError')->once()->andReturnUsing(function (Exception $e, Task $task) {
			Assert::equal('Test 01', $e->getMessage());
		});
		$logCallback->shouldReceive('logBegin')->twice();
		$logCallback->shouldReceive('logFinished')->once();

		$this->timestampStorage->shouldReceive('setTaskName');
		$this->timestampStorage->shouldReceive('saveRunTime');

		$this->cronner->onTaskBegin[] = function (Cronner $cronner, Task $task) use ($logCallback) {
			$logCallback->logBegin($task);
		};
		$this->cronner->onTaskFinished[] = function (Cronner $cronner, Task $task) use ($logCallback) {
			$logCallback->logFinished($task);
		};
		$this->cronner->onTaskError[] = function (Cronner $cronner, Exception $e, Task $task) use ($logCallback) {
			$logCallback->logError($e, $task);
		};
		$this->cronner->addTasks(new TestExceptionObject());
		$this->cronner->run($now);
	}


	public function testAddingTwoTestsWithTheSameNameInOneObject()
	{
		$cronner = $this->cronner;
		Assert::exception(function () use ($cronner) {
			$cronner->addTasks(new SameTaskNameObject());
		}, DuplicateTaskNameException::class);
	}


	public function testAddingTwoTestsWithTheSameNameInMoreObjects()
	{
		$cronner = $this->cronner;
		Assert::exception(function () use ($cronner) {
			$cronner->addTasks(new AnotherSimpleTestObject());
			$cronner->addTasks(new NextSimpleTestObject());
		}, DuplicateTaskNameException::class);
	}


	protected function setUp()
	{
		parent::setUp();
		$timestampStorage = Mockery::mock(ITimestampStorage::class);
		$timestampStorage->shouldReceive('setTaskName');
		$timestampStorage->shouldReceive('saveRunTime');
		$timestampStorage->shouldReceive('loadLastRunTime')->andReturn(new DateTime('2013-02-04 08:00:00'));
		$this->timestampStorage = $timestampStorage;

		$criticalSection = Mockery::mock(ICriticalSection::class);
		$criticalSection->shouldReceive("enter")->andReturn(true);
		$criticalSection->shouldReceive("leave")->andReturn(true);
		$criticalSection->shouldReceive("isEntered")->andReturn(false);

		$this->cronner = new Cronner($this->timestampStorage, $criticalSection);
		$this->cronner->onTaskBegin = [];
		$this->cronner->onTaskFinished = [];
		$this->cronner->onTaskError = [];
	}
}

run(new CronnerTest());
