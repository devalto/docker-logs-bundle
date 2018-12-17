<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\DependencyInjection;

use Chrif\Bundle\DockerLogsBundle\DependencyInjection\Compiler\DockerLogsOptionPass;
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
			->children() // todo array prototype instead?
				->arrayNode('channels')
					->info("Each channel will have a configurable logging level through an env var named 'env_prefix' + 'channel'. Example: LOGGING_APP")
					->scalarPrototype()
						->cannotBeEmpty()
					->end()
					->defaultValue([ "app", "event", "doctrine", "console", "php" ])
					->cannotBeEmpty()
				->end()
				->scalarNode('default_logging_level')
					->info("Default logging level for all channels in 'channels'.")
					->defaultValue('notice')
					->cannotBeEmpty()
					->validate()
						->ifTrue(function($level) {
							$levelConstant = 'Monolog\Logger::'.strtoupper($level);
							return !defined($levelConstant);
						})
						->thenInvalid('The configured minimum log level %s is invalid as it is not defined in Monolog\Logger.')
					->end()
				->end()
				->arrayNode('channels_to_ignore_in_console')
					->info("These channels will be muted in a Symfony command without the --'".DockerLogsOptionPass::DOCKER_LOGS."' option.")
					->scalarPrototype()
						->cannotBeEmpty()
					->end()
					->defaultValue([ "event", "doctrine", "console" ])
					->cannotBeEmpty()
				->end()
				->arrayNode('channels_with_muted_context')
					->info("The context for these channels will not be logged.")
					->scalarPrototype()
						->cannotBeEmpty()
					->end()
					->defaultValue([ ])
				->end()
				->scalarNode('env_prefix')
					->info("This is the prefix for the env vars.")
					->defaultValue('LOGGING_')
				->end()
				->booleanNode('create_other_handler')
					->info("If true, all channels not listed in 'channels' will have the LOGGING_OTHER level which defaults to 'debug'. Useful for finding new channels to add in the 'channels' config.")
					->defaultValue(true)
				->end()
				->booleanNode('colors')
					// todo validate class_exists(VarCloner::class)
					->info("If true, use a decorated (colored) console output (when available).")
					->defaultValue(true)
				->end()
			->end();
		// @formatter:on

		return $treeBuilder;
	}
}
