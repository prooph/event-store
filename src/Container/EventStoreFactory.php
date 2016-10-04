<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Container;

use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfig;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherAggregate;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Prooph\EventStore\Plugin\Plugin;

/**
 * Class EventStoreFactory
 * @package Prooph\EventStore\Container\Stream
 */
final class EventStoreFactory implements RequiresConfig, RequiresMandatoryOptions, ProvidesDefaultOptions
{
    use ConfigurationTrait;

    public function __invoke(ContainerInterface $container) : EventStore
    {
        $config = $container->get('config');
        $config = $this->options($config);

        $adapter = $container->get($config['adapter']['type']);

        if (!isset($config['event_emitter'])) {
            $eventEmitter = new ProophActionEventEmitter();
        } else {
            $eventEmitter = $container->get($config['event_emitter']);
        }

        $eventStore = new EventStore($adapter, $eventEmitter);

        foreach ($config['plugins'] as $pluginAlias) {
            $plugin = $container->get($pluginAlias);

            if (!$plugin instanceof Plugin) {
                throw ConfigurationException::configurationError(sprintf(
                    'Plugin %s does not implement the Plugin interface',
                    $pluginAlias
                ));
            }

            $plugin->setUp($eventStore);
        }

        if (count($config['metadata_enrichers']) > 0) {
            $metadataEnrichers = [];

            foreach ($config['metadata_enrichers'] as $metadataEnricherAlias) {
                $metadataEnricher = $container->get($metadataEnricherAlias);

                if (!$metadataEnricher instanceof MetadataEnricher) {
                    throw ConfigurationException::configurationError(sprintf(
                        'Metadata enricher %s does not implement the MetadataEnricher interface',
                        $metadataEnricherAlias
                    ));
                }

                $metadataEnrichers[] = $metadataEnricher;
            }

            $plugin = new MetadataEnricherPlugin(
                new MetadataEnricherAggregate($metadataEnrichers)
            );

            $plugin->setUp($eventStore);
        }

        return $eventStore;
    }

    /**
     * @inheritdoc
     */
    public function dimensions() : array
    {
        return ['prooph', 'event_store'];
    }

    /**
     * @inheritdoc
     */
    public function mandatoryOptions() : array
    {
        return [
            'adapter' => [
                'type'
            ],
            'metadata_enrichers',
            'plugins'
        ];
    }

    /**
     * @inheritdoc
     */
    public function defaultOptions() : array
    {
        return [
            'metadata_enrichers' => [],
            'plugins' => []
        ];
    }
}
