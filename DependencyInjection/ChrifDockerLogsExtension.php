<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\DependencyInjection;

use Chrif\Bundle\DockerLogsBundle\Logging\MonologConfigurator;
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
		$configuration = $this->getConfiguration($configs, $container);
		$config = $this->processConfiguration($configuration, $configs);

		$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		$loader->load('services.yml');

		$monologConfigurator = new MonologConfigurator(
			$config['channels'],
			$config['env_prefix'],
			$config['default_logging_level'],
			$config['create_other_handler'],
			$config['colors'],
			$config['channels_to_ignore_in_console'],
			$config['channels_with_muted_context']
		);

		$handlers = $monologConfigurator->handlersConfig($container);

		$container->prependExtensionConfig(
			'monolog',
			[
				'handlers' => $handlers,
			]
		);
	}

}
