<?php declare(strict_types=1);

namespace Chrif\Bundle\DockerLogsBundle\Logging;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 *
 * Sample config created with:
 * new LoggingConfiguration(['app', 'request']):
 *
 *
 * parameters:
 *   env(LOGGING_APP): "debug"
 *   env(LOGGING_REQUEST): "notice"
 *   env(LOGGING_OTHER): "debug"
 *
 * monolog:
 *   handlers:
 *     app:
 *       type: service
 *         id: app.logging.handler.app
 *         channels: ["app"]
 *     request:
 *       type: service
 *         id: app.logging.handler.request
 *         channels: ["request"]
 *     other:
 *       type: service
 *         id: app.logging.handler.other
 *         channels: ["!app","!request"]
 *
 * services:
 *   app.logging.handler.app:
 *     class: App\Logging\NonCliProcessConsoleHandler
 *     arguments: [ '%env(string:LOGGING_APP)%' ]
 *   app.logging.handler.request:
 *     class: App\Logging\NonCliProcessConsoleHandler
 *     arguments: [ '%env(string:LOGGING_REQUEST)%' ]
 *   app.logging.handler.other:
 *     class: App\Logging\NonCliProcessConsoleHandler
 *     arguments: [ '%env(string:LOGGING_OTHER)%' ]
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
		$container->setDefinition(
			$serviceId,
			new Definition(
				NonCliProcessConsoleHandler::class,
				['%env(string:' . $levelEnvName . ')%', $this->colors ]
			)
		);

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
