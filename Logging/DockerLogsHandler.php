<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\Logging;

use Chrif\Bundle\DockerLogsBundle\DependencyInjection\Compiler\DockerLogsOptionPass;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\VarDumper\Dumper\CliDumper;

final class DockerLogsHandler extends AbstractProcessingHandler implements EventSubscriberInterface {

	/**
	 * @var bool
	 */
	private $ignoredInConsole;
	/**
	 * @var
	 */
	private $isConsole = false;
	/**
	 * @var bool
	 */
	private $colors;
	/**
	 * @var int
	 */
	private $verbosity;
	/**
	 * @var bool
	 */
	private $noContext;
	/**
	 * @var ConsoleOutput
	 */
	private $output;
	/**
	 * @var array
	 */
	private $verbosityToLevelMap = [
		OutputInterface::VERBOSITY_QUIET => Logger::ERROR,
		OutputInterface::VERBOSITY_NORMAL => Logger::WARNING,
		OutputInterface::VERBOSITY_VERBOSE => Logger::NOTICE,
		OutputInterface::VERBOSITY_VERY_VERBOSE => Logger::INFO,
		OutputInterface::VERBOSITY_DEBUG => Logger::DEBUG,
	];
	/**
	 * @var array
	 */
	private $levelToVerbosityMap = [
		Logger::ERROR => OutputInterface::VERBOSITY_QUIET,
		Logger::WARNING => OutputInterface::VERBOSITY_NORMAL,
		Logger::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
		Logger::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
		Logger::DEBUG => OutputInterface::VERBOSITY_DEBUG,
	];

	public function __construct(string $level, bool $colors, bool $ignoredInConsole, bool $noContext) {
		parent::__construct($level, true);

		$this->colors = $colors;
		$this->ignoredInConsole = $ignoredInConsole;
		$this->noContext = $noContext;
		$this->verbosity = $this->levelToVerbosityMap[$this->level];
		$this->output = new ConsoleOutput($this->verbosity, $this->colors);

		if ($this->noContext) {
			$this->pushProcessor(function ($record) {
				$formatter = $this->getFormatter();
				if ($formatter instanceof ConsoleFormatter) {
					$record = $formatter->replacePlaceHolder($record);
				}

				$record['extra'] = [];
				$record['context'] = [];

				return $record;
			});
		}
	}

	/**
	 * @param array $record
	 * @return bool
	 */
	public function isHandling(array $record) {
		if ($this->ignoredInConsole && $this->isConsole) {
			return false;
		} else {
			return $this->updateLevel() && parent::isHandling($record);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle(array $record) {
		// we have to update the logging level each time because the verbosity of the
		// console output might have changed in the meantime (it is not immutable)
		return $this->updateLevel() && parent::handle($record);
	}

	/**
	 * Sets the console output to use for printing logs.
	 */
	public function setOutput(OutputInterface $output) {
		$this->output = $output;
	}

	/**
	 * Disables the output.
	 */
	public function close() {
		$this->output = null;

		parent::close();
	}

	/**
	 * @param ConsoleCommandEvent $event
	 */
	public function onCommand(ConsoleCommandEvent $event) {
		$this->isConsole = true;
		$input = $event->getInput();
		$dockerLogs = DockerLogsOptionPass::DOCKER_LOGS;
		if (!$input->hasOption($dockerLogs) || !$input->getOption($dockerLogs)) {
			$output = $event->getOutput();
			if ($output instanceof ConsoleOutputInterface) {
				$output = $output->getErrorOutput();
			}

			$this->setOutput($output);
		}
	}

	public function onTerminate(
		/** @noinspection PhpUnusedParameterInspection */
		ConsoleTerminateEvent $event
	) {
		$this->close();
	}

	/**
	 * @param ConsoleErrorEvent $event
	 */
	public function onConsoleError(
		/** @noinspection PhpUnusedParameterInspection */
		ConsoleErrorEvent $event
	) {
		$this->isConsole = true;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function write(array $record) {
		// at this point we've determined for sure that we want to output the record, so use the output's own verbosity
		$this->output->write((string)$record['formatted'], false, $this->output->getVerbosity());
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getDefaultFormatter() {
		if (!class_exists(CliDumper::class)) {
			return new LineFormatter();
		}
		if (!$this->output) {
			return new ConsoleFormatter();
		}

		return new ConsoleFormatter([
			'colors' => $this->output->isDecorated(),
			'multiline' => OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity(),
		]);
	}

	/**
	 * Updates the logging level based on the verbosity setting of the console output.
	 *
	 * @return bool Whether the handler is enabled and verbosity is not set to quiet
	 */
	private function updateLevel() {
		if (null === $this->output) {
			return false;
		}

		$verbosity = $this->output->getVerbosity();
		if (isset($this->verbosityToLevelMap[$verbosity])) {
			$this->setLevel($this->verbosityToLevelMap[$verbosity]);
		} else {
			$this->setLevel(Logger::DEBUG);
		}

		return true;
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
