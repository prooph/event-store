<?php
/*
 * This file is part of the prooph/proophessor.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 20.08.15 - 16:20
 */

namespace Prooph\EventStore\Service;

use Interop\Container\ContainerInterface;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\Feature;
use Prooph\EventStore\Stream\AggregateStreamStrategy;

/**
 * Class EventStoreFactory
 * @package Prooph\EventStore\Service\Stream
 */
final class EventStoreFactory
{
    /**
     * @param ContainerInterface $container
     * @return AggregateStreamStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (! isset($config['prooph'])) {
            throw ConfigurationException::configurationError('Missing prooph config key in application config');
        }

        if (! isset($config['prooph']['event_store'])) {
            throw ConfigurationException::configurationError('Missing key event_store in prooph configuration');
        }

        $config = $config['prooph']['event_store'];

        if (!isset($config['adapter'])) {
            $adapter = new InMemoryAdapter();
        } else {
            $adapter = $container->get($config['adapter']);
        }

        if (!isset($config['event_emitter'])) {
            $eventEmitter = new ProophActionEventEmitter();
        } else {
            $eventEmitter = $container->get($config['event_emitter']);
        }

        $eventStore = new EventStore($adapter, $eventEmitter);

        $plugins = isset($config['plugins']) ? $config['plugins'] : [];

        foreach ($plugins as $pluginAlias) {
            $plugin = $container->get($pluginAlias);

            if (!$plugin instanceof Feature) {
                throw ConfigurationException::configurationError(sprintf(
                    'Feature %s does not implement the Feature interface',
                    $pluginAlias
                ));
            }

            $plugin->setUp($eventStore);
        }

        return $eventStore;
    }
}
