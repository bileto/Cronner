<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\Tasks;


use DateTime;
use stekycz\Cronner\Tasks\Parameters;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

class ParametersParsingTest extends \TestCase
{

	/** @var object */
	private $object;


	/**
	 * @dataProvider dataProviderParse
	 * @param array $expected
	 * @param string $methodName
	 */
	public function testParsesTaskSettings(array $expected, string $methodName)
	{
		if (!(new \Nette\Reflection\ClassType($this->object))->hasMethod($methodName)) {
			Assert::fail('Tested class does not have method "' . $methodName . '".');

			return;
		}

		Assert::same($expected,
			Parameters::parseParameters(
				(new \Nette\Reflection\ClassType($this->object))->getMethod($methodName),
				new DateTime('NOW')
			)
		);
	}


	public function dataProviderParse(): array
	{
		return [
			[
				[
					Parameters::TASK => 'E-mail notifications',
					Parameters::PERIOD => '5 minutes',
					Parameters::DAYS => null,
					Parameters::DAYS_OF_MONTH => null,
					Parameters::TIME => null,
				],
				'test01',
			],
			[
				[
					Parameters::TASK => 'stekycz\Cronner\tests\objects\TestObject - test02',
					Parameters::PERIOD => '1 hour',
					Parameters::DAYS => ['Mon', 'Wed', 'Fri',],
					Parameters::DAYS_OF_MONTH => null,
					Parameters::TIME => [
						[
							'from' => '09:00',
							'to' => '10:00',
						],
						[
							'from' => '15:00',
							'to' => '16:00',
						],
					],
				],
				'test02',
			],
			[
				[
					Parameters::TASK => 'Test 3',
					Parameters::PERIOD => '17 minutes',
					Parameters::DAYS => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri',],
					Parameters::DAYS_OF_MONTH => null,
					Parameters::TIME => [
						[
							'from' => '09:00',
							'to' => '10:45',
						],
					],
				],
				'test03',
			],
			[
				[
					Parameters::TASK => 'Test 4',
					Parameters::PERIOD => '1 day',
					Parameters::DAYS => ['Sat', 'Sun',],
					Parameters::DAYS_OF_MONTH => null,
					Parameters::TIME => null,
				],
				'test04',
			],
		];
	}


	protected function setUp()
	{
		parent::setUp();
		$this->object = new TestObject();
	}

}

run(new ParametersParsingTest());
