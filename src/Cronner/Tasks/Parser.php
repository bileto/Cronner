<?php

declare(strict_types=1);

namespace stekycz\Cronner\Tasks;


use Nette\Utils\Strings;
use stekycz\Cronner\Exceptions\InvalidParameterException;

final class Parser
{
	/**
	 * Parses name of cron task.
	 */
	public static function parseName(string $annotation): ?string
	{
		$name = Strings::trim($annotation);
		$name = Strings::length($name) > 0 ? $name : null;

		return $name;
	}


	/**
	 * Parses period of cron task. If annotation is invalid throws exception.
	 *
	 * @throws InvalidParameterException
	 */
	public static function parsePeriod(string $annotation): ?string
	{
		$period = null;
		$annotation = Strings::trim($annotation);
		if (Strings::length($annotation)) {
			if (strtotime('+ ' . $annotation) === false) {
				throw new InvalidParameterException(
					'Given period parameter "' . $annotation . '" must be valid for strtotime() '
					. 'with "+" (plus) sign as its prefix (added by Cronner automatically).'
				);
			}
			$period = $annotation;
		}

		return $period ?: null;
	}


	/**
	 * Parses allowed days for cron task. If annotation is invalid throws exception.
	 *
	 * @return string[]|null
	 * @throws InvalidParameterException
	 */
	public static function parseDays(string $annotation): ?array
	{
		static $validValues = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',];

		$days = null;
		$annotation = Strings::trim($annotation);
		if (Strings::length($annotation)) {
			$days = static::translateToDayNames($annotation);
			$days = static::expandDaysRange($days);
			foreach ($days as $day) {
				if (in_array($day, $validValues, true) === false) {
					throw new InvalidParameterException("Given day parameter '" . $day . "' must be one from " . implode(', ', $validValues) . ".");
				}
			}

			$days = array_values(array_intersect($validValues, $days));
		}

		return $days ?: null;
	}


	/**
	 * Parses allowed days om month for cron task. If annotation is invalid throws exception.
	 *
	 * @return string[]|null
	 * @throws InvalidParameterException
	 */
	public static function parseDaysOfMonth(string $annotation, \DateTimeInterface $now): ?array
	{
		$days = null;
		$annotation = Strings::trim($annotation);
		if (Strings::length($annotation)) {
			$days = static::expandDaysOfMonthRange(static::splitMultipleValues($annotation));

			$dayInMonthCount = cal_days_in_month(CAL_GREGORIAN, (int) $now->format('n'), (int) $now->format('Y'));

			foreach ($days as $day) {
				if (($day < 1 || $day > 31) || !is_numeric($day)) {
					throw new InvalidParameterException("Given day parameter '" . $day . "' must be numeric from range 1-31.");
				}
				if ($day > $dayInMonthCount && !in_array($dayInMonthCount, $days, true)) {
					$days[] = $dayInMonthCount;
				}
			}
		}

		return $days ?: null;
	}


	/**
	 * Parses allowed time ranges for cron task. If annotation is invalid
	 * throws exception.
	 *
	 * @return string[][]|null
	 * @throws InvalidParameterException
	 */
	public static function parseTimes(string $annotation): ?array
	{
		$times = null;
		$annotation = Strings::trim($annotation);
		if (Strings::length($annotation) && $values = static::splitMultipleValues($annotation)) {
			$times = [];
			foreach ($values as $time) {
				// TODO: Fix by https://php.baraja.cz/mergovani-velkeho-pole
				$times = array_merge($times, static::parseOneTime($time)); // TODO: Slow merge algorithm
			}
			usort($times, static function ($a, $b) {
				return $a < $b ? -1 : ($a > $b ? 1 : 0);
			});
		}

		return $times ?: null;
	}


	/**
	 * Translates given annotation to day names.
	 *
	 * @return string[]
	 */
	private static function translateToDayNames(string $annotation): array
	{
		static $workingDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri',];
		static $weekend = ['Sat', 'Sun',];

		$days = [];
		foreach (static::splitMultipleValues($annotation) as $value) {
			switch ($value) {
				case 'working days':
					$days = array_merge($days, $workingDays);
					break;
				case 'weekend':
					$days = array_merge($days, $weekend);
					break;
				default:
					$days[] = $value;
					break;
			}
		}

		return array_unique($days);
	}


	/**
	 * Expands given day names and day ranges to day names only. The day range must be
	 * in "Mon-Fri" format.
	 *
	 * @param string[] $days
	 * @return string[]
	 */
	private static function expandDaysRange(array $days): array
	{
		static $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',];
		$expandedValues = [];

		foreach ($days as $day) {
			if (Strings::match($day, '~^\w{3}\s*-\s*\w{3}$~u')) {
				[$begin, $end] = Strings::split($day, '~\s*-\s*~');
				$started = false;
				foreach ($dayNames as $dayName) {
					if ($dayName === $begin) {
						$started = true;
					}
					if ($started) {
						$expandedValues[] = $dayName;
					}
					if ($dayName === $end) {
						$started = false;
					}
				}
			} else {
				$expandedValues[] = $day;
			}
		}

		return array_unique($expandedValues);
	}


	/**
	 * Expands given day of month ranges to array.
	 *
	 * @param string[] $days
	 * @return string[]
	 */
	private static function expandDaysOfMonthRange(array $days): array
	{
		$expandedValues = [];

		foreach ($days as $day) {
			if (Strings::match($day, '~^(3[01]|[12][0-9]|[1-9])\s*-\s*(3[01]|[12][0-9]|[1-9])$~u')) {
				[$begin, $end] = Strings::split($day, '~\s*-\s*~');
				for ($i = $begin; $i <= $end; $i++) {
					if (!in_array($i, $expandedValues, true)) {
						$expandedValues[] = $i;
					}
				}
			} elseif (!in_array($day, $expandedValues, true)) {
				$expandedValues[] = $day;
			}
		}

		return array_unique($expandedValues);
	}


	/**
	 * Splits given annotation by comma into array.
	 *
	 * @return string[]
	 */
	private static function splitMultipleValues(string $annotation): array
	{
		return Strings::split($annotation, '/\s*,\s*/');
	}


	/**
	 * Returns True if time in valid format is given, False otherwise.
	 */
	private static function isValidTime(string $time): bool
	{
		return (bool) Strings::match($time, '/^\d{2}:\d{2}$/u');
	}


	/**
	 * Parses one time annotation. If it is invalid throws exception.
	 *
	 * @return string[][]
	 * @throws InvalidParameterException
	 */
	private static function parseOneTime(string $time): array
	{
		$time = static::translateToTimes($time);
		$parts = Strings::split($time, '/\s*-\s*/');
		if (!static::isValidTime($parts[0]) || (isset($parts[1]) && !static::isValidTime($parts[1]))) {
			throw new InvalidParameterException(
				"Times annotation is not in valid format. It must looks like 'hh:mm[ - hh:mm]' but '" . $time . "' was given."
			);
		}
		$times = [];
		if (static::isTimeOverMidnight($parts[0], $parts[1] ?? null)) {
			$times[] = static::timePartsToArray('00:00', $parts[1]);
			$times[] = static::timePartsToArray($parts[0], '23:59');
		} else {
			$times[] = static::timePartsToArray($parts[0], $parts[1] ?? null);
		}

		return $times;
	}


	/**
	 * Translates given annotation to day names.
	 */
	private static function translateToTimes(string $time): string
	{
		static $translationMap = [
			'morning' => '06:00 - 11:59',
			'noon' => '12:00 - 12:29',
			'afternoon' => '12:30 - 16:59',
			'evening' => '17:00 - 21:59',
			'night' => '22:00 - 05:59',
			'midnight' => '00:00 - 00:29',
		];

		return array_key_exists($time, $translationMap) ? $translationMap[$time] : $time;
	}


	/**
	 * Returns True if given times includes midnight, False otherwise.
	 */
	private static function isTimeOverMidnight(string $from, string $to = null): bool
	{
		return $to !== null && $to < $from;
	}


	/**
	 * Returns array structure with given times.
	 */
	private static function timePartsToArray(string $from, string $to = null): array
	{
		return [
			'from' => $from,
			'to' => $to,
		];
	}
}
