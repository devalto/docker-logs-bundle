<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\DependencyInjection;

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

		$rootNode = $treeBuilder->getRootNode();

		return $treeBuilder;
	}
}
