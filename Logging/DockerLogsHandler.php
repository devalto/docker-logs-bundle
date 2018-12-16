<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\Logging;

use Monolog\Logger;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class DockerLogsHandler extends ConsoleHandler {

	private $levelToVerbosityMap = [
		Logger::ERROR => OutputInterface::VERBOSITY_QUIET,
		Logger::WARNING => OutputInterface::VERBOSITY_NORMAL,
		Logger::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
		Logger::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
		Logger::DEBUG => OutputInterface::VERBOSITY_DEBUG,
	];

	public function __construct(string $level, bool $colors) {
		$level = Logger::toMonologLevel($level);
		$consoleOutput = new ConsoleOutput($this->levelToVerbosityMap[$level], $colors);

		parent::__construct($consoleOutput->getErrorOutput());
	}
}
