<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\DependencyInjection;

use Chrif\Bundle\DockerLogsBundle\Logging\LoggingConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class DockerLogsExtension extends Extension {

	/**
	 * Loads a specific configuration.
	 *
	 * @param array $configs
	 * @param ContainerBuilder $container
	 */
	public function load(array $configs, ContainerBuilder $container) {
		(new LoggingConfiguration())->configureContainer($container);
	}
}
