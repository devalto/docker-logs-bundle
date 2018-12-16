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
final class LoggingConfiguration {

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

	public function __construct(
		array $channels,
		string $servicePrefix,
		string $envPrefix,
		string $debugChannel,
		bool $createOtherHandler
	) {
		$this->channels = $channels;
		$this->servicePrefix = $servicePrefix;
		$this->envPrefix = $envPrefix;
		$this->debugChannel = $debugChannel;
		$this->createOtherHandler = $createOtherHandler;
	}

	public function configureContainer(ContainerBuilder $container) {
		foreach ($this->channels as $channel) {
			$this->addHandler(
				$container,
				$channel,
				$this->servicePrefix . $channel,
				$this->envPrefix . strtoupper($channel),
				$channel == $this->debugChannel ? 'debug' : 'notice',
				[$channel],
				false
			);
		}
		if ($this->createOtherHandler) {
			$this->addHandler(
				$container,
				'other',
				$this->servicePrefix . 'other',
				$this->envPrefix . 'OTHER',
				'debug',
				$this->channels,
				true
			);
		}
	}

	private function addHandler(
		ContainerBuilder $container,
		string $name,
		string $serviceId,
		string $levelEnvName,
		string $defaultLevel,
		array $channels,
		bool $exclusive
	) {
		$container->setParameter("env($levelEnvName)", $defaultLevel);
		$handler = $container->setDefinition(
			$serviceId,
			new Definition(
				NonCliProcessConsoleHandler::class,
				['%env(string:' . $levelEnvName . ')%']
			)
		);
		$handler->setAutowired(true);
		$container->prependExtensionConfig(
			'monolog',
			[
				'handlers' => [
					$name => [
						'type' => 'service',
						'id' => $serviceId,
						'channels' => !$exclusive ? $channels : array_map(
							function ($v) {
								return "!$v";
							},
							$channels
						),
					],
				],
			]
		);
	}

}
