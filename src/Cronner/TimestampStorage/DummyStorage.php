<?php

declare(strict_types=1);

namespace stekycz\Cronner\TimestampStorage;


use DateTimeInterface;
use Nette\SmartObject;
use stekycz\Cronner\ITimestampStorage;

final class DummyStorage implements ITimestampStorage
{
	use SmartObject;


	/**
	 * Sets name of current task.
	 */
	public function setTaskName(?string $taskName = null): void
	{
		// Dummy
	}


	/**
	 * Saves current date and time as last invocation time.
	 */
	public function saveRunTime(DateTimeInterface $now): void
	{
		// Dummy
	}


	/**
	 * Returns date and time of last cron task invocation.
	 */
	public function loadLastRunTime(): ?DateTimeInterface
	{
		return null; // Dummy
	}
}
