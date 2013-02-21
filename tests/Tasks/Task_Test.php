<?php

namespace stekycz\Cronner\tests\Tasks;

require_once(TEST_DIR . '/objects/TestObject.php');

use PHPUnit_Framework_TestCase;
use DateTime;
use Nette;
use stekycz\Cronner\Tasks\Task;
use stekycz\Cronner\tests\objects\TestObject;

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @since 2013-02-21
 */
class Task_Test extends PHPUnit_Framework_TestCase {

	/**
	 * @var \stekycz\Cronner\Tasks
	 */
	private $object;

	protected function setUp() {
		parent::setUp();
		$this->object = new TestObject();
		$a = '';
	}

	/**
	 * @test
	 */
	public function invokesTaskWithSavingLastRunTime() {
		$method = $this->getMock('\Nette\Reflection\Method');
		$method->expects($this->once())
			->method('invoke')
			->with($this->equalTo($this->object));

		$timestampStorage = $this->getMock('\stekycz\Cronner\ITimestampStorage');
		$timestampStorage->expects($this->once())
			->method('saveRunTime');

		$task = new Task($this->object, $method, $timestampStorage);
		$task();
	}

	/**
	 * @test
	 * @dataProvider dataProviderShouldBeRun
	 * @param bool $expected
	 * @param int $loads
	 * @param string $methodName
	 * @param string $now
	 * @param string $lastRunTime
	 */
	public function checksIfCanBeRun($expected, $loads, $methodName, $now, $lastRunTime) {
		$now = new Nette\DateTime($now);
		$lastRunTime = $lastRunTime ? new Nette\DateTime($lastRunTime) : null;

		$method = $this->object->reflection->getMethod($methodName);

		$timestampStorage = $this->getMock(
			'\stekycz\Cronner\ITimestampStorage',
			array('saveRunTime', 'loadLastRunTime', )
		);
		$timestampStorage->expects($this->exactly($loads))
			->method('loadLastRunTime')
			->will($this->returnValue($lastRunTime));

		$task = new Task($this->object, $method, $timestampStorage);
		$this->assertEquals($expected, $task->shouldBeRun($now));
	}

	public function dataProviderShouldBeRun() {
		return array(
			// Test 01
			array(true, 1, 'test01', '2013-02-01 12:00:00', null),
			array(true, 1, 'test01', '2013-02-01 12:10:00', '2013-02-01 12:00:00'),
			array(false, 1, 'test01', '2013-02-01 12:04:00', '2013-02-01 12:00:00'),
			// Test 02
			array(true, 0, 'test02', '2013-02-04 09:30:00', null),
			array(false, 1, 'test02', '2013-02-04 12:00:00', null),
			array(true, 1, 'test02', '2013-02-04 10:00:00', '2013-02-03 15:30:00'),
		);
	}

}
