<?php

declare(strict_types=1);

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\Tasks;


use stdClass;
use stekycz\Cronner\Tasks\Parser;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

class ParserTest extends \TestCase
{

	/**
	 * @dataProvider dataProviderParseName
	 * @param string $expected
	 * @param string $annotation
	 */
	public function parsesName(string $expected, string $annotation)
	{
		Assert::equal($expected, Parser::parseName($annotation));
	}


	public function dataProviderParseName(): array
	{
		return [
			['Testovací úkol', 'Testovací úkol'],
			['Testovací úkol', '   Testovací úkol   '],
			['true', 'true'],
			['false', 'false'],
			['0', '0'],
			['1', '1'],
			[null, true],
			[null, false],
			[null, 0],
			[null, 1],
			[null, new stdClass()],
		];
	}


	/**
	 * @dataProvider dataProviderParsePeriod
	 * @param string $expected
	 * @param string $annotation
	 */
	public function testParsesPeriod(string $expected, string $annotation)
	{
		Assert::equal($expected, Parser::parsePeriod($annotation));
	}


	public function dataProviderParsePeriod(): array
	{
		return [
			['5 minutes', '5 minutes'],
			['5 minutes', '   5 minutes   '],
			['2 seconds', '2 seconds'],
			['2 weeks', '2 weeks'],
			['2 months', '2 months'],
		];
	}


	/**
	 * @dataProvider dataProviderParsePeriodError
	 * @param string $annotation
	 * @throws \stekycz\Cronner\Exceptions\InvalidParameterException
	 */
	public function testThrowsExceptionOnWrongPeriodDefinition(string $annotation)
	{
		Parser::parsePeriod($annotation);
	}


	public function dataProviderParsePeriodError(): array
	{
		return [
			['nejaky blabol'],
			['true'],
			['false'],
			['0'],
			['1'],
		];
	}


	/**
	 * @dataProvider dataProviderParseDays
	 * @param string[] $expected
	 * @param string $annotation
	 */
	public function testParsesDays(array $expected, string $annotation)
	{
		Assert::equal($expected, Parser::parseDays($annotation));
	}


	public function dataProviderParseDays(): array
	{
		return [
			// Regular and simple values
			[['Mon',], 'Mon'],
			[['Mon', 'Tue',], 'Mon, Tue'],
			[['Mon', 'Fri',], 'Mon, Fri'],
			[['Mon', 'Tue', 'Wed', 'Thu', 'Fri',], 'working days'],
			[['Sat', 'Sun',], 'weekend'],
			// Day groups
			[['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',], 'working days, weekend'],
			[['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',], 'weekend, working days'],
			[['Mon', 'Tue', 'Wed', 'Thu', 'Fri',], 'working days, Mon'],
			[['Sat', 'Sun',], 'weekend, Sat'],
			[['Mon', 'Tue', 'Wed', 'Thu', 'Fri',], 'Wed, working days'],
			[['Sat', 'Sun',], 'Sat, weekend'],
			[['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',], 'Sat, working days'],
			[['Wed', 'Sat', 'Sun',], 'Wed, weekend'],
			// Day ranges
			[['Mon', 'Tue', 'Wed',], 'Mon-Wed'],
			[['Mon', 'Tue', 'Wed', 'Fri',], 'Mon-Wed, Fri'],
			[['Mon', 'Wed', 'Thu', 'Fri', 'Sun',], 'Mon, Wed-Fri, Sun'],
			[['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',], 'Mon-Wed, Tue-Fri, Thu-Sun'],
			[['Thu', 'Fri', 'Sat', 'Sun',], 'Thu-Sat, weekend'],
			[['Sat', 'Sun',], 'Sat-Tue'],
			// Special cases (whitespaces)
			[['Mon',], '   Mon   '],
			[['Mon', 'Tue', 'Wed', 'Thu', 'Fri',], '   working days   '],
			[['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',], '   Mon   ,   weekend   ,   working days   '],
			[['Tue', 'Wed', 'Thu',], '   Tue   -   Thu   '],
		];
	}


	/**
	 * @dataProvider dataProviderParseDaysError
	 * @param string $annotation
	 * @throws \stekycz\Cronner\Exceptions\InvalidParameterException
	 */
	public function testThrowsExceptionOnWrongDaysDefinition(string $annotation)
	{
		Parser::parseDays($annotation);
	}


	public function dataProviderParseDaysError(): array
	{
		return [
			['nejaky blabol'],
			['true'],
			['false'],
			['0'],
			['1'],
		];
	}


	/**
	 * @dataProvider dataProviderParseTimes
	 * @param string[][] $expected
	 * @param string $annotation
	 */
	public function testParsesTimes(array $expected, string $annotation)
	{
		Assert::equal($expected, Parser::parseTimes($annotation));
	}


	public function dataProviderParseTimes(): array
	{
		return [
			// Basic
			[
				[
					[
						'from' => '11:00',
						'to' => null,
					],
				],
				'11:00',
			],
			[
				[
					[
						'from' => '11:00',
						'to' => '12:00',
					],
				],
				'11:00 - 12:00',
			],
			// Multiple
			[
				[
					[
						'from' => '11:00',
						'to' => null,
					],
					[
						'from' => '17:00',
						'to' => null,
					],
				],
				'11:00, 17:00',
			],
			[
				[
					[
						'from' => '11:00',
						'to' => '12:00',
					],
					[
						'from' => '17:00',
						'to' => '19:00',
					],
				],
				'11:00 - 12:00, 17:00-19:00',
			],
			// Many whitespaces
			[
				[
					[
						'from' => '11:00',
						'to' => '12:00',
					],
				],
				'    11:00     -     12:00    ',
			],
			[
				[
					[
						'from' => '11:00',
						'to' => '12:00',
					],
					[
						'from' => '17:00',
						'to' => '19:00',
					],
				],
				'   11:00   -   12:00   ,   17:00   -   19:00   ',
			],
			// Over midnight
			[
				[
					[
						'from' => '00:00',
						'to' => '05:00',
					],
					[
						'from' => '21:30',
						'to' => '23:59',
					],
				],
				'21:30 - 05:00',
			],
			// Critical
			[
				[
					[
						'from' => '00:00',
						'to' => '05:00',
					],
					[
						'from' => '16:00',
						'to' => '18:00',
					],
					[
						'from' => '21:30',
						'to' => '23:59',
					],
				],
				'16:00 - 18:00, 21:30 - 05:00',
			],
			// Shortcuts
			[
				[
					[
						'from' => '06:00',
						'to' => '11:59',
					],
				],
				'morning',
			],
			[
				[
					[
						'from' => '12:00',
						'to' => '12:29',
					],
				],
				'noon',
			],
			[
				[
					[
						'from' => '12:30',
						'to' => '16:59',
					],
				],
				'afternoon',
			],
			[
				[
					[
						'from' => '17:00',
						'to' => '21:59',
					],
				],
				'evening',
			],
			[
				[
					[
						'from' => '00:00',
						'to' => '05:59',
					],
					[
						'from' => '22:00',
						'to' => '23:59',
					],
				],
				'night',
			],
			[
				[
					[
						'from' => '00:00',
						'to' => '00:29',
					],
				],
				'midnight',
			],
			// Combined
			[
				[
					[
						'from' => '00:00',
						'to' => '00:29',
					],
					[
						'from' => '06:00',
						'to' => '11:59',
					],
				],
				'morning, midnight',
			],
			[
				[
					[
						'from' => '00:00',
						'to' => '00:29',
					],
					[
						'from' => '03:00',
						'to' => '04:00',
					],
					[
						'from' => '06:00',
						'to' => '11:59',
					],
				],
				'morning, midnight, 03:00 - 04:00',
			],
		];
	}


	/**
	 * @dataProvider dataProviderParseTimesError
	 * @param string $annotation
	 * @throws \stekycz\Cronner\Exceptions\InvalidParameterException
	 */
	public function testThrowsExceptionOnWrongTimesDefinition(string $annotation)
	{
		Parser::parseTimes($annotation);
	}


	public function dataProviderParseTimesError(): array
	{
		return [
			['nejaky blabol'],
			['true'],
			['false'],
			['0'],
			['1'],
		];
	}
}

run(new ParserTest());
