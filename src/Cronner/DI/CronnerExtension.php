<?php

declare(strict_types=1);

namespace stekycz\Cronner\DI;


use Bileto\CriticalSection\CriticalSection;
use Bileto\CriticalSection\Driver\FileDriver;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\Extensions\InjectExtension;
use Nette\DI\Helpers;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stekycz\Cronner\Bar\Tasks;
use stekycz\Cronner\Cronner;
use stekycz\Cronner\TimestampStorage\FileStorage;
use Tracy\Bar;

final class CronnerExtension extends CompilerExtension
{
	private const TASKS_TAG = 'cronner.tasks';


	public static function register(Configurator $configurator): void
	{
		$configurator->onCompile[] = static function (Configurator $config, Compiler $compiler): void {
			$compiler->addExtension('cronner', new CronnerExtension());
		};
	}


	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'assets' => Expect::arrayOf(Expect::string()),
			'timestampStorage' => Expect::string(),
			'maxExecutionTime' => Expect::int(),
			'criticalSectionTempDir' => Expect::string(),
			'criticalSectionDriver' => Expect::string(),
			'tasks' => Expect::array(),
			'bar' => Expect::bool(),
		])->castTo('array');
	}


	public function loadConfiguration(): void
	{
		/** @var mixed[] $config */
		$config = $this->config;
		$builder = $this->getContainerBuilder();

		if ($config['timestampStorage'] === null) {
			$builder->addDefinition($this->prefix('fileStorage'))
				->setFactory(FileStorage::class)
				->setArgument('directory', $builder->parameters['tempDir'] . '/cronner')
				->addTag(InjectExtension::TAG_INJECT, false);
		}
		if ($config['criticalSectionDriver'] === null) {
			$builder->addDefinition($this->prefix('criticalSectionDriver'))
				->setFactory(FileDriver::class)
				->setArgument('lockFilesDir', $builder->parameters['tempDir'] . '/critical-section');
		}

		$builder->addDefinition($this->prefix('criticalSection'))
			->setFactory(CriticalSection::class)
			->setArgument('driver', $builder->getDefinitionByType($config['criticalSectionDriver'] ?? FileDriver::class))
			->addTag(InjectExtension::TAG_INJECT, false);

		$builder->addDefinition($this->prefix('runner'))
			->setFactory(Cronner::class)
			->setArgument('maxExecutionTime', $config['maxExecutionTime'])
			->setArgument('skipFailedTask', array_key_exists('debugMode', $config) ? !$config['debugMode'] : true);

		foreach ($config['tasks'] ?? [] as $task) {
			$def = $builder->addDefinition($this->prefix('task.' . md5(is_string($task) ? $task : $task->getEntity() . '-' . json_encode($task))));
			[$def->factory] = Helpers::filterArguments([
				is_string($task) ? new Statement($task) : $task,
			]);

			if (class_exists($def->factory->entity)) {
				$def->setFactory($def->factory->entity);
			}

			$def->setAutowired(false);
			$def->addTag(InjectExtension::TAG_INJECT, false);
			$def->addTag(self::TASKS_TAG);
		}

		if ($config['bar'] ?? false) {
			$builder->addDefinition($this->prefix('bar'))
				->setFactory(Tasks::class);
		}
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var ServiceDefinition $runner */
		$runner = $builder->getDefinition($this->prefix('runner'));
		foreach (array_keys($builder->findByTag(self::TASKS_TAG)) as $serviceName) {
			$runner->addSetup('addTasks', ['@' . $serviceName]);
		}
	}


	public function afterCompile(ClassType $class): void
	{
		if ($this->getContainerBuilder()->hasDefinition($this->prefix('bar'))) {
			$class->getMethod('initialize')
				->addBody('$this->getByType(?)->addPanel($this->getService(?));', [
					Bar::class,
					$this->prefix('bar'),
				]);
		}
	}
}
