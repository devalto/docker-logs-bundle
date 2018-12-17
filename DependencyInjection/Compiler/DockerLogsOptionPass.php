<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\DependencyInjection\Compiler;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class DockerLogsOptionPass implements CompilerPassInterface {

	const DOCKER_LOGS = 'docker-logs';

	public function process(ContainerBuilder $container) {
		$commandServices = $container->findTaggedServiceIds('console.command', true);

		foreach ($commandServices as $id => $tags) {
			/** @var Definition $definition */
			$definition = $container->getDefinition($id);
			$definition->addMethodCall(
				'addOption',
				[
					self::DOCKER_LOGS,
					null,
					InputOption::VALUE_NONE,
					'DockerLogsBundle => Ignore verbosity option and use the configured values for Monolog levels. ' .
					'Useful for Docker services started with a symfony command.',
				]
			);
		}
	}

}
