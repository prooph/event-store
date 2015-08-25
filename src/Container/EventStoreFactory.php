<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08/20/15 - 06:31 PM
 */

namespace Prooph\EventStore\Container;

use Interop\Container\ContainerInterface;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;

/**
 * Class EventStoreFactory
 * @package Prooph\EventStore\Container\Stream
 */
final class EventStoreFactory
{
    /**
     * @param ContainerInterface $container
     * @return EventStore
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

        if (!isset($config['adapter']['type'])) {
            $adapter = new InMemoryAdapter();
        } else {
            $adapter = $container->get($config['adapter']['type']);
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

            if (!$plugin instanceof Plugin) {
                throw ConfigurationException::configurationError(sprintf(
                    'Plugin %s does not implement the Plugin interface',
                    $pluginAlias
                ));
            }

            $plugin->setUp($eventStore);
        }

        return $eventStore;
    }
}
