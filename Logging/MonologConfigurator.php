<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\Logging;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 *
 * Example config:
 *
 * chrif_docker_logs:
 *   channels: ["app","request"]
 *
 *
 * Result after container build:
 *
 * parameters:
 *   env(LOGGING_APP): "debug"
 *   env(LOGGING_REQUEST): "notice"
 *   env(LOGGING_OTHER): "debug"
 *
 * monolog:
 *   handlers:
 *     chrif_docker_logs.logging.handler.app:
 *       type: service
 *         id: chrif_docker_logs.logging.handler.app
 *         channels: ["app"]
 *     chrif_docker_logs.logging.handler.request:
 *       type: service
 *         id: chrif_docker_logs.logging.handler.request
 *         channels: ["request"]
 *     chrif_docker_logs.logging.handler.other:
 *       type: service
 *         id: chrif_docker_logs.logging.handler.other
 *         channels: ["!app","!request"]
 *
 * services:
 *   chrif_docker_logs.logging.handler.app:
 *     class: Chrif\Bundle\DockerLogsBundle\Logging\DockerLogsHandler
 *     arguments: [ '%env(string:LOGGING_APP)%', true ]
 *   chrif_docker_logs.logging.handler.request:
 *     class: Chrif\Bundle\DockerLogsBundle\Logging\DockerLogsHandler
 *     arguments: [ '%env(string:LOGGING_REQUEST)%', true ]
 *   chrif_docker_logs.logging.handler.other:
 *     class: Chrif\Bundle\DockerLogsBundle\Logging\DockerLogsHandler
 *     arguments: [ '%env(string:LOGGING_OTHER)%', true ]
 *
 */
final class MonologConfigurator {

	/**
	 * @var string[]
	 */
	private $channels;
	/**
	 * @var string
	 */
	private $servicePrefix;
	/**
	 * @var string
	 */
	private $envPrefix;
	/**
	 * @var string
	 */
	private $debugChannel;

	/**
	 * @var bool
	 */
	private $createOtherHandler;
	/**
	 * @var bool
	 */
	private $colors;

	public function __construct(
		array $channels,
		string $servicePrefix,
		string $envPrefix,
		string $debugChannel,
		bool $createOtherHandler,
		bool $colors
	) {
		$this->channels = $channels;
		$this->servicePrefix = $servicePrefix;
		$this->envPrefix = $envPrefix;
		$this->debugChannel = $debugChannel;
		$this->createOtherHandler = $createOtherHandler;
		$this->colors = $colors;
	}

	public function handlersConfig(ContainerBuilder $container) {
		$handlers = [];

		foreach ($this->channels as $channel) {
			$serviceId = $this->servicePrefix . $channel;
			$handlers[$serviceId] = $this->addHandler(
				$container,
				$serviceId,
				$this->envPrefix . strtoupper($channel),
				$channel == $this->debugChannel ? 'debug' : 'notice',
				[$channel],
				false
			);
		}
		if ($this->createOtherHandler) {
			$serviceId = $this->servicePrefix . 'other';
			$handlers[$serviceId] = $this->addHandler(
				$container,
				$serviceId,
				$this->envPrefix . 'OTHER',
				'debug',
				$this->channels,
				true
			);
		}
		return $handlers;
	}

	private function addHandler(
		ContainerBuilder $container,
		string $serviceId,
		string $levelEnvName,
		string $defaultLevel,
		array $channels,
		bool $exclusive
	): array {
		$container->setParameter("env($levelEnvName)", $defaultLevel);
		$definition = $container->setDefinition(
			$serviceId,
			new Definition(
				DockerLogsHandler::class,
				['%env(string:' . $levelEnvName . ')%', $this->colors ]
			)
		);
		$definition->addTag('kernel.event_subscriber');

		return [
			'type' => 'service',
			'id' => $serviceId,
			'channels' => !$exclusive ? $channels : array_map(
				function ($v) {
					return "!$v";
				},
				$channels
			),
		];
	}

}
