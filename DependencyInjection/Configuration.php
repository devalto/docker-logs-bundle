<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface {

	/**
	 * Generates the configuration tree builder.
	 *
	 * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
	 */
	public function getConfigTreeBuilder() {
		$treeBuilder = new TreeBuilder('chrif_docker_logs');

		/** @var ArrayNodeDefinition $rootNode */
		$rootNode = $treeBuilder->getRootNode();

		// @formatter:off
		$rootNode
			->children()
				->arrayNode('channels')
					->info("Each channel will have a configurable logging level through an env var " .
						"which defaults to 'notice', except for the debug channel which defaults to 'debug'.")
					->scalarPrototype()
						->cannotBeEmpty()
					->end()
					->defaultValue([ "app", "php" ])
					->cannotBeEmpty()
				->end()
				->scalarNode('env_prefix')
					->info("This is the prefix for the env var.")
					->defaultValue('LOGGING_')
				->end()
				->scalarNode('debug_channel')
					->info('This is the main channel being monitored. Its default logging level is debug.')
					->defaultValue('app')
					->cannotBeEmpty()
				->end()
				->booleanNode('create_other_handler')
					->info("If true, all channels not listed will have the LOGGING_OTHER level which defaults to 'debug'.")
					->defaultValue(true)
				->end()
			->end();
		// @formatter:on

		return $treeBuilder;
	}
}
