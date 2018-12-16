<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\DependencyInjection;

use Chrif\Bundle\DockerLogsBundle\Logging\LoggingConfiguration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class ChrifDockerLogsExtension extends Extension {

	/**
	 * Loads a specific configuration.
	 *
	 * @param array $configs
	 * @param ContainerBuilder $container
	 * @throws \Exception
	 */
	public function load(array $configs, ContainerBuilder $container) {
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		$loader->load('services.yml');

		$configuration = $this->getConfiguration([], $container);
		$config = $this->processConfiguration($configuration, []);

		$loggingConfiguration = new LoggingConfiguration(
			$config['channels'],
			'chrif_docker_logs.handler.',
			$config['env_prefix'],
			$config['debug_channel'],
			$config['create_other_handler']
		);
		$loggingConfiguration->configureContainer($container);
	}

}
