<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle;

use Chrif\Bundle\DockerLogsBundle\DependencyInjection\Compiler\DockerLogsOptionPass;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ChrifDockerLogsBundle extends Bundle {

	public function build(ContainerBuilder $container) {
		parent::build($container);

		$extensions = array_keys($container->getExtensions());
		if (array_search('monolog', $extensions) < array_search('chrif_docker_logs', $extensions)) {
			throw new \Exception(
				sprintf(
					"%s must be before %s in config/bundles.php",
					ChrifDockerLogsBundle::class,
					MonologBundle::class
				)
			);
		}

		$container->addCompilerPass(new DockerLogsOptionPass());
	}
}
