<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\TimestampStorage;


use DateTime;
use Nette;
use Nette\Utils\FileSystem;
use stekycz\Cronner\TimestampStorage\FileStorage;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

class FileStorageTest extends \TestCase
{

	/** @var FileStorage */
	private $storage;


	private static function getTempDirPath()
	{
		return TEMP_DIR . '/cronner';
	}


	public function testIsAbleToSetTaskName()
	{
		$this->storage->setTaskName('Test task 1');
		$this->storage->setTaskName(null);
		$this->storage->setTaskName();
		Assert::$counter++; // Hack for nette tester
	}


	/**
	 * @dataProvider dataProviderSetTaskName
	 * @throws \stekycz\Cronner\Exceptions\InvalidTaskNameException
	 */
	public function testThrowsExceptionOnInvalidTaskName(string $taskName = null)
	{
		$this->storage->setTaskName($taskName);
	}


	public function dataProviderSetTaskName(): array
	{
		return [
			[''],
		];
	}


	/**
	 * Tests that saving do not throws any exception.
	 *
	 * @dataProvider dataProviderSaveRunTime
	 * @param DateTime $date
	 */
	public function testLoadsAndSavesLastRunTimeWithoutErrors(DateTime $date)
	{
		$this->storage->setTaskName('Test task 1');

		$lastRunTime = $this->storage->loadLastRunTime();
		Assert::null($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		Assert::type('\DateTime', $lastRunTime);
		Assert::equal($date->format('Y-m-d H:i:s O'), $lastRunTime->format('Y-m-d H:i:s O'));
	}


	public function dataProviderSaveRunTime(): array
	{
		return [
			[new Nette\Utils\DateTime('2013-01-30 17:30:00')],
			[new Nette\Utils\DateTime('2013-01-30 18:30:01')],
			[new Nette\Utils\DateTime('2013-01-30 18:31:01')],
		];
	}


	public function testSavesLastRunTimeByTaskName()
	{
		$date = new DateTime('2013-01-30 17:30:00');

		$this->storage->setTaskName('Test task 1');
		$lastRunTime = $this->storage->loadLastRunTime();
		Assert::null($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		Assert::type('\DateTime', $lastRunTime);
		Assert::same($date->format('Y-m-d H:i:s O'), $lastRunTime->format('Y-m-d H:i:s O'));

		$this->storage->setTaskName('Test task 2');
		$lastRunTime = $this->storage->loadLastRunTime();
		Assert::null($lastRunTime);
		$this->storage->saveRunTime($date);
		$lastRunTime = $this->storage->loadLastRunTime();

		$this->storage->setTaskName();

		Assert::type('\DateTime', $lastRunTime);
		Assert::equal($date->format('Y-m-d H:i:s O'), $lastRunTime->format('Y-m-d H:i:s O'));
	}


	protected function setUp()
	{
		parent::setUp();
		FileSystem::createDir(static::getTempDirPath());
		$this->storage = new FileStorage(static::getTempDirPath());
	}


	protected function tearDown()
	{
		parent::tearDown();
		try {
			FileSystem::delete(static::getTempDirPath());
		} catch (Nette\IOException $e) {
		}
	}
}

run(new FileStorageTest());
