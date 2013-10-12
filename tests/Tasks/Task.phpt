<?php

namespace stekycz\Cronner\tests\Tasks;

use Nette\Reflection\Method;
use Nette;
use stekycz\Cronner\Tasks\Task;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;



require_once(__DIR__ . "/../bootstrap.php");
require_once(__DIR__ . "/../objects/TestObject.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class TaskTest extends \TestCase
{

	/**
	 * @var object
	 */
	private $object;



	protected function setUp()
	{
		parent::setUp();
		$this->object = new TestObject();
	}



	public function testInvokesTaskWithSavingLastRunTime()
	{
		$timestampStorage = $this->mockista->create(
			'\stekycz\Cronner\ITimestampStorage',
			array("setTaskName", "saveRunTime", "loadLastRunTime")
		);
		$timestampStorage->expects("saveRunTime")
			->once();
		$timestampStorage->expects("setTaskName")
			->exactly(2);

		$method = new Method($this->object, 'test01');
		$task = new Task($this->object, $method, $timestampStorage);
		$task();
	}



	/**
	 * @dataProvider dataProviderShouldBeRun
	 * @param bool $expected
	 * @param int $loads
	 * @param string $methodName
	 * @param string $now
	 * @param string $lastRunTime
	 */
	public function testChecksIfCanBeRun($expected, $loads, $methodName, $now, $lastRunTime)
	{
		$now = new Nette\DateTime($now);
		$lastRunTime = $lastRunTime ? new Nette\DateTime($lastRunTime) : NULL;

		$method = $this->object->getReflection()->getMethod($methodName);

		$timestampStorage = $this->mockista->create(
			'\stekycz\Cronner\ITimestampStorage',
			array('setTaskName', 'saveRunTime', 'loadLastRunTime',)
		);
		$timestampStorage->expects("loadLastRunTime")
			->exactly($loads)
			->andReturn($lastRunTime);
		$timestampStorage->expects("setTaskName")
			->atLeastOnce();

		$task = new Task($this->object, $method, $timestampStorage);
		Assert::equal($expected, $task->shouldBeRun($now));
	}



	public function dataProviderShouldBeRun()
	{
		return array(
			// Test 01
			array(TRUE, 1, 'test01', '2013-02-01 12:00:00', NULL),
			array(TRUE, 1, 'test01', '2013-02-01 12:10:00', '2013-02-01 12:00:00'),
			array(FALSE, 1, 'test01', '2013-02-01 12:04:00', '2013-02-01 12:00:00'),
			// Test 02
			array(FALSE, 0, 'test02', '2013-02-05 12:00:00', NULL),
			array(FALSE, 0, 'test02', '2013-02-04 12:00:00', NULL),
			array(FALSE, 1, 'test02', '2013-02-04 09:30:00', '2013-02-04 09:00:00'),
			array(TRUE, 1, 'test02', '2013-02-04 09:30:00', NULL),
			array(TRUE, 1, 'test02', '2013-02-04 09:30:00', '2013-02-03 15:30:00'),
		);
	}

}

run(new TaskTest());
