<?php

declare(strict_types=1);

namespace stekycz\Cronner\Bar;


use stekycz\Cronner\Cronner;
use Tracy\IBarPanel;

final class Tasks implements IBarPanel
{

	/** @var Cronner */
	private $cronner;


	public function __construct(Cronner $cronner)
	{
		$this->cronner = $cronner;
	}


	public function getPanel(): string
	{
		$tasks = [];
		foreach ($this->cronner->getTasks() as $task) {
			if (!array_key_exists($task->getObjectName(), $tasks)) {
				$tasks[$task->getObjectName()] = [];
			}

			$tasks[$task->getObjectName()][] = $task;
		}
		ob_start();
		require __DIR__ . '/templates/panel.phtml';

		return ob_get_clean();
	}


	public function getTab(): string
	{
		ob_start();
		$count = $this->cronner->countTasks();
		require __DIR__ . '/templates/tab.phtml';

		return ob_get_clean();
	}
}
