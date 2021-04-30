<?php

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\DI;


use Bileto\CriticalSection\CriticalSection;
use Nette\Configurator;
use Nette\DI\Compiler;
use stekycz\Cronner\Cronner;
use stekycz\Cronner\DI\CronnerExtension;
use stekycz\Cronner\TimestampStorage\DummyStorage;
use stekycz\Cronner\TimestampStorage\FileStorage;
use Tester\Assert;

require_once(__DIR__ . "/../bootstrap.php");

class CronnerExtensionTest extends \TestCase
{

	/** @var Compiler */
	private $compiler;


	public function testDefaultConfiguration()
	{
		$compiler = $this->compiler;
		$compiler->compile();

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same(FileStorage::class, $timestampStorage->getType());
		Assert::same(CriticalSection::class, $criticalSection->getType());
		Assert::same(Cronner::class, $runner->getType());
	}


	public function testCompleteConfiguration()
	{
		$compiler = $this->compiler;
		$compiler->getContainerBuilder()->addDefinition('cronner.dummyStorage')->setFactory(DummyStorage::class);
		$compiler->addConfig([
			'cronner' => [
				'timestampStorage' => DummyStorage::class,
				'maxExecutionTime' => 120,
				'criticalSectionTempDir' => __DIR__ . '../../tmp/cronner',
			],
		]);
		$compiler->compile();

		$timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
		$criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
		$runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

		Assert::same(DummyStorage::class, $timestampStorage->getType());
		Assert::same(CriticalSection::class, $criticalSection->getType());
		Assert::same(Cronner::class, $runner->getType());
	}


	public function testRegisterTasks()
	{
		\Tester\Helpers::purge(__DIR__ . '/../../tmp/');

		$config = new Configurator();
		$config->setTempDirectory(__DIR__ . '/../../tmp/');
		$config->addConfig(__DIR__ . '/../config/config.neon');
		$container = $config->createContainer();

		$cronner = $container->getByType('stekycz\Cronner\Cronner');

		Assert::same(2, count($cronner->getTasks()));
	}


	protected function setUp()
	{
		parent::setUp();
		$this->compiler = new Compiler();
		$this->compiler->addConfig([
			'parameters' => [
				'appDir' => __DIR__ . '/../..',
				'wwwDir' => __DIR__ . '/../..',
				'tempDir' => TEMP_DIR,
				'debugMode' => false,
				'productionMode' => true,
			],
		]);
		$this->compiler->addExtension('cronner', new CronnerExtension());
	}
}

run(new CronnerExtensionTest());
