<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\Logging;

use Chrif\Bundle\DockerLogsBundle\DependencyInjection\Compiler\DockerLogsOptionPass;
use Monolog\Logger;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class DockerLogsHandler extends ConsoleHandler {

	/**
	 * @var bool
	 */
	private $ignoredInConsole;

	/**
	 * @var
	 */
	private $isConsole = false;

	private $levelToVerbosityMap = [
		Logger::ERROR => OutputInterface::VERBOSITY_QUIET,
		Logger::WARNING => OutputInterface::VERBOSITY_NORMAL,
		Logger::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
		Logger::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
		Logger::DEBUG => OutputInterface::VERBOSITY_DEBUG,
	];

	public function __construct(string $level, bool $colors, bool $ignoredInConsole) {
		$this->ignoredInConsole = $ignoredInConsole;
		$level = Logger::toMonologLevel($level);
		$consoleOutput = new ConsoleOutput($this->levelToVerbosityMap[$level], $colors);
		parent::__construct($consoleOutput->getErrorOutput());
	}

	public function isHandling(array $record) {
		if ($this->ignoredInConsole && $this->isConsole) {
			return false;
		} else {
			return parent::isHandling($record);
		}
	}

	public function onCommand(ConsoleCommandEvent $event) {
		$this->isConsole = true;
		$input = $event->getInput();
		$dockerLogs = DockerLogsOptionPass::DOCKER_LOGS;
		if (!$input->hasOption($dockerLogs) || !$input->getOption($dockerLogs)) {
			parent::onCommand($event);
		}
	}

	public function onConsoleError(
		/** @noinspection PhpUnusedParameterInspection */
		ConsoleErrorEvent $event
	) {
		$this->isConsole = true;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents() {
		return [
			ConsoleEvents::COMMAND => ['onCommand', 255],
			ConsoleEvents::TERMINATE => ['onTerminate', -255],
			ConsoleEvents::ERROR => ['onConsoleError', 255],
		];
	}

}
