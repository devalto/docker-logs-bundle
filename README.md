# DockerLogsBundle

DockerLogsBundle is intended for containerized Symfony applications whose logs you would like to redirect `stderr` for use with either `docker logs` or `docker-compose logs`, but without tampering with the normal output to `stderr` of Symfony commands ran manually, not as a service.

## Features
* Automatic redirect of non cli processes like web server or queue consumer to `stderr`.
* Configurable level for each Monolog channel with an env var like `LOGGIN_APP=debug`.
* Decorated console formatter.

## Installation

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require chrif/docker-logs-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require chrif/docker-logs-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

_Make sure to place it above Monolog._

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new \Chrif\Bundle\DockerLogsBundle\ChrifDockerLogsBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
        );

        // ...
    }

    // ...
}
```

## Default configuration
```
chrif_docker_logs:

    # Each channel will have a configurable logging level through an env var named 'env_prefix' + 'channel'. Example: LOGGING_APP
    channels:

        # Defaults:
        - app
        - event
        - doctrine
        - console
        - php

    # Default logging level for all channels in 'channels'.
    default_logging_level: notice

    # These channels will be muted in a Symfony command without the --'docker-logs' option.
    channels_to_ignore_in_console:

        # Defaults:
        - event
        - doctrine
        - console

    # This is the prefix for the env vars.
    env_prefix:           LOGGING_

    # If true, all channels not listed in 'channels' will have the LOGGING_OTHER level which defaults to 'debug'. Useful for finding new channels to add in the 'channels' config.
    create_other_handler: true

    # If true, use a decorated (colored) console output (when available).
    colors:               true
```
