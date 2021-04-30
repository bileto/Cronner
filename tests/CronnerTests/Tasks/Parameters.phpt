<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\Tasks;


use DateTime;
use DateTimeInterface;
use Nette;
use stekycz\Cronner\Tasks\Parameters;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

class ParametersTest extends \TestCase
{

	/**
	 * @dataProvider dataProviderGetName
	 * @param string $expected
	 * @param array $parameters
	 */
	public function testReturnsTaskName(string $expected, array $parameters)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->getName());
	}


	public function dataProviderGetName(): array
	{
		return [
			['Name of task', [Parameters::TASK => 'Name of task',]],
			['0', [Parameters::TASK => '0',]],
			['', [Parameters::TASK => '   ',]],
			['', [Parameters::TASK => '',]],
			['', [Parameters::TASK => true,]],
			['', [Parameters::TASK => false,]],
			['', [Parameters::TASK => null,]],
			['', [Parameters::TASK => 0,]],
			['', []],
		];
	}


	/**
	 * @dataProvider dataProviderIsTask
	 * @param bool $expected
	 * @param array $parameters
	 */
	public function testDetectsTask(bool $expected, array $parameters)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isTask());
	}


	public function dataProviderIsTask(): array
	{
		return [
			[true, [Parameters::TASK => 'Name of task',]],
			[true, [Parameters::TASK => '0',]],
			[false, [Parameters::TASK => '   ',]],
			[false, [Parameters::TASK => '',]],
			[false, [Parameters::TASK => true,]],
			[false, [Parameters::TASK => false,]],
			[false, [Parameters::TASK => null,]],
			[false, [Parameters::TASK => 0,]],
			[false, []],
		];
	}


	/**
	 * @dataProvider dataProviderIsNextPeriod
	 * @param bool $expected
	 * @param DateTimeInterface $now
	 * @param DateTimeInterface|null $lastRunTime
	 * @param array $parameters
	 */
	public function testDetectsIfNowIsInNextPeriod(bool $expected, DateTimeInterface $now, DateTimeInterface $lastRunTime = null, array $parameters)
	{
		$params = new Parameters($parameters);
		Assert::same($expected, $params->isNextPeriod($now, $lastRunTime));
	}


	public function dataProviderIsNextPeriod(): array
	{
		return [
			[
				true,
				new \DateTimeImmutable('2013-02-03 17:00:00'),
				new \DateTimeImmutable('2013-02-03 16:54:59'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				true,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:54:59'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				true,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:00'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				true,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:01'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				true,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:05'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				false,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:06'),
				[Parameters::PERIOD => '5 minutes',],
			],
			[
				false,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:55:01'),
				[Parameters::PERIOD => '1 hour',],
			],
			[
				true,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:00:00'),
				[Parameters::PERIOD => '1 hour',],
			],
			[
				true,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				new Nette\Utils\DateTime('2013-02-03 16:00:00'),
				[],
			],
			[
				true,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				null,
				[Parameters::PERIOD => '1 hour',],
			],
			[
				true,
				new Nette\Utils\DateTime('2013-02-03 17:00:00'),
				null,
				[],
			],
		];
	}


	/**
	 * @dataProvider dataProviderIsInDay
	 * @param bool $expected
	 * @param array $parameters
	 * @param DateTime $now
	 */
	public function testDetectsAllowedDaysOfWeek(bool $expected, array $parameters, DateTime $now)
	{
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isInDay($now));
	}


	public function dataProviderIsInDay(): array
	{
		return [
			// One day
			[true, [Parameters::DAYS => ['Mon',],], new Nette\Utils\DateTime('2013-02-11 12:34:56')],
			[false, [Parameters::DAYS => ['Mon',],], new Nette\Utils\DateTime('2013-02-12 12:34:56')],
			[true, [Parameters::DAYS => ['Tue',],], new Nette\Utils\DateTime('2013-02-12 12:34:56')],
			[false, [Parameters::DAYS => ['Tue',],], new Nette\Utils\DateTime('2013-02-13 12:34:56')],
			[true, [Parameters::DAYS => ['Wed',],], new Nette\Utils\DateTime('2013-02-13 12:34:56')],
			[false, [Parameters::DAYS => ['Wed',],], new Nette\Utils\DateTime('2013-02-14 12:34:56')],
			[true, [Parameters::DAYS => ['Thu',],], new Nette\Utils\DateTime('2013-02-14 12:34:56')],
			[false, [Parameters::DAYS => ['Thu',],], new Nette\Utils\DateTime('2013-02-15 12:34:56')],
			[true, [Parameters::DAYS => ['Fri',],], new Nette\Utils\DateTime('2013-02-15 12:34:56')],
			[false, [Parameters::DAYS => ['Fri',],], new Nette\Utils\DateTime('2013-02-16 12:34:56')],
			[true, [Parameters::DAYS => ['Sat',],], new Nette\Utils\DateTime('2013-02-16 12:34:56')],
			[false, [Parameters::DAYS => ['Sat',],], new Nette\Utils\DateTime('2013-02-17 12:34:56')],
			[true, [Parameters::DAYS => ['Sun',],], new Nette\Utils\DateTime('2013-02-17 12:34:56')],
			[false, [Parameters::DAYS => ['Sun',],], new Nette\Utils\DateTime('2013-02-18 12:34:56')],
			// Empty days
			[false, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-11 12:34:56')],
			[false, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-12 12:34:56')],
			[false, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-13 12:34:56')],
			[false, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-14 12:34:56')],
			[false, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-15 12:34:56')],
			[false, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-16 12:34:56')],
			[false, [Parameters::DAYS => [],], new Nette\Utils\DateTime('2013-02-17 12:34:56')],
			// Without days
			[true, [Parameters::DAYS => null,], new Nette\Utils\DateTime('2013-02-11 12:34:56')],
			[true, [Parameters::DAYS => null,], new Nette\Utils\DateTime('2013-02-12 12:34:56')],
			[true, [Parameters::DAYS => null,], new Nette\Utils\DateTime('2013-02-13 12:34:56')],
			[true, [Parameters::DAYS => null,], new Nette\Utils\DateTime('2013-02-14 12:34:56')],
			[true, [Parameters::DAYS => null,], new Nette\Utils\DateTime('2013-02-15 12:34:56')],
			[true, [Parameters::DAYS => null,], new Nette\Utils\DateTime('2013-02-16 12:34:56')],
			[true, [Parameters::DAYS => null,], new Nette\Utils\DateTime('2013-02-17 12:34:56')],
		];
	}


	/**
	 * @dataProvider dataProviderIsInTime
	 * @param bool $expected
	 * @param array $parameters
	 * @param string $now
	 */
	public function testDetectsAllowedTimeRange(bool $expected, array $parameters, string $now)
	{
		$now = new Nette\Utils\DateTime($now);
		$params = new Parameters($parameters);
		Assert::equal($expected, $params->isInTime($now));
	}


	public function dataProviderIsInTime(): array
	{
		return [
			// One minute
			[
				true,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => null,
						],
					],
				],
				'2013-02-11 11:00:00',
			],
			[
				true,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => null,
						],
					],
				],
				'2013-02-11 11:00:59',
			],
			[
				false,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => null,
						],
					],
				],
				'2013-02-11 10:59:59',
			],
			[
				false,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => null,
						],
					],
				],
				'2013-02-11 11:01:00',
			],
			// Range
			[
				true,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => '12:00',
						],
					],
				],
				'2013-02-11 11:00:00',
			],
			[
				true,
				[
					Parameters::TIME => [
						[
							'from' => '11:00',
							'to' => '12:00',
						],
					],
				],
				'2013-02-11 11:30:00',
			],
			[
				true,
				[
					Parameters::TIME => [
						['from' => '11:00', 'to' => '12:00',],
					],
				],
				'2013-02-11 12:00:59',
			],
			[
				false,
				[
					Parameters::TIME => [
						['from' => '11:00', 'to' => '12:00',],
					],
				],
				'2013-02-11 10:59:59',
			],
			[
				false,
				[
					Parameters::TIME => [
						['from' => '11:00', 'to' => '12:00',],
					],
				],
				'2013-02-11 12:01:00',
			],
			// Empty
			[
				true,
				[Parameters::TIME => [],],
				'2013-02-11 12:00:00',
			],
			[
				true,
				[Parameters::TIME => null,],
				'2013-02-11 12:00:00',
			],
		];
	}

}

run(new ParametersTest());
