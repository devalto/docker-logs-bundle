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
 * Changes to container after build will look like this:
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
 *     arguments: [ '%env(string:LOGGING_APP)%', true, false ]
 *   chrif_docker_logs.logging.handler.request:
 *     class: Chrif\Bundle\DockerLogsBundle\Logging\DockerLogsHandler
 *     arguments: [ '%env(string:LOGGING_REQUEST)%', true, false ]
 *   chrif_docker_logs.logging.handler.other:
 *     class: Chrif\Bundle\DockerLogsBundle\Logging\DockerLogsHandler
 *     arguments: [ '%env(string:LOGGING_OTHER)%', true, false ]
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
	private $servicePrefix = 'chrif_docker_logs.handler.';
	/**
	 * @var string
	 */
	private $envPrefix;
	/**
	 * @var string
	 */
	private $defaultLoggingLevel;

	/**
	 * @var bool
	 */
	private $createOtherHandler;
	/**
	 * @var bool
	 */
	private $colors;
	/**
	 * @var array
	 */
	private $channelsToIgnoreInConsole;
	/**
	 * @var array
	 */
	private $channelsWithMutedContext;

	public function __construct(
		array $channels,
		string $envPrefix,
		string $defaultLoggingLevel,
		bool $createOtherHandler,
		bool $colors,
		array $channelsToIgnoreInConsole,
		array $channelsWithMutedContext
	) {
		$this->channels = $channels;
		$this->envPrefix = $envPrefix;
		$this->defaultLoggingLevel = $defaultLoggingLevel;
		$this->createOtherHandler = $createOtherHandler;
		$this->colors = $colors;
		$this->channelsToIgnoreInConsole = $channelsToIgnoreInConsole;
		$this->channelsWithMutedContext = $channelsWithMutedContext;
	}

	public function handlersConfig(ContainerBuilder $container) {
		$handlers = [];

		foreach ($this->channels as $channel) {
			$serviceId = $this->servicePrefix . $channel;
			$handlers[$serviceId] = $this->addHandler(
				$container,
				$serviceId,
				$this->envPrefix . strtoupper($channel),
				$this->defaultLoggingLevel,
				[$channel],
				false,
				in_array($channel, $this->channelsToIgnoreInConsole),
				$channel
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
				true,
				false
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
		bool $exclusive,
		bool $ignoredInConsole,
		string $channel = null
	): array {
		$container->setParameter("env($levelEnvName)", $defaultLevel);
		$definition = $container->setDefinition(
			$serviceId,
			new Definition(
				DockerLogsHandler::class,
				[
					'%env(string:' . $levelEnvName . ')%',
					$this->colors,
					$ignoredInConsole,
					in_array($channel, $this->channelsWithMutedContext),
				]
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
