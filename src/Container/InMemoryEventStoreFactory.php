<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Container;

use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfig;
use Interop\Config\RequiresConfigId;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherAggregate;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Prooph\EventStore\TransactionalEventStore;
use Psr\Container\ContainerInterface;

final class InMemoryEventStoreFactory implements
    ProvidesDefaultOptions,
    RequiresConfig,
    RequiresConfigId
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $configId;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     InMemoryEventStore::class => [InMemoryEventStoreFactory::class, 'service_name'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): TransactionalEventStore
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($name))->__invoke($arguments[0]);
    }

    public function __construct(string $configId = 'default')
    {
        $this->configId = $configId;
    }

    /**
     * @throws ConfigurationException
     */
    public function __invoke(ContainerInterface $container): TransactionalEventStore
    {
        $config = $container->get('config');
        $config = $this->options($config, $this->configId);

        $eventStore = new InMemoryEventStore();

        if (! $config['wrap_action_event_emitter']) {
            return $eventStore;
        }

        if (! isset($config['event_emitter'])) {
            $eventEmitter = new ProophActionEventEmitter([
                TransactionalActionEventEmitterEventStore::EVENT_APPEND_TO,
                TransactionalActionEventEmitterEventStore::EVENT_CREATE,
                TransactionalActionEventEmitterEventStore::EVENT_LOAD,
                TransactionalActionEventEmitterEventStore::EVENT_LOAD_REVERSE,
                TransactionalActionEventEmitterEventStore::EVENT_DELETE,
                TransactionalActionEventEmitterEventStore::EVENT_HAS_STREAM,
                TransactionalActionEventEmitterEventStore::EVENT_FETCH_STREAM_METADATA,
                TransactionalActionEventEmitterEventStore::EVENT_UPDATE_STREAM_METADATA,
                TransactionalActionEventEmitterEventStore::EVENT_DELETE_PROJECTION,
                TransactionalActionEventEmitterEventStore::EVENT_RESET_PROJECTION,
                TransactionalActionEventEmitterEventStore::EVENT_STOP_PROJECTION,
                TransactionalActionEventEmitterEventStore::EVENT_FETCH_STREAM_NAMES,
                TransactionalActionEventEmitterEventStore::EVENT_FETCH_CATEGORY_NAMES,
                TransactionalActionEventEmitterEventStore::EVENT_FETCH_PROJECTION_NAMES,
                TransactionalActionEventEmitterEventStore::EVENT_FETCH_PROJECTION_STATUS,
                TransactionalActionEventEmitterEventStore::EVENT_FETCH_PROJECTION_STREAM_POSITIONS,
                TransactionalActionEventEmitterEventStore::EVENT_FETCH_PROJECTION_STATE,
                TransactionalActionEventEmitterEventStore::EVENT_BEGIN_TRANSACTION,
                TransactionalActionEventEmitterEventStore::EVENT_COMMIT,
                TransactionalActionEventEmitterEventStore::EVENT_ROLLBACK,
            ]);
        } else {
            $eventEmitter = $container->get($config['event_emitter']);
        }

        $wrapper = new TransactionalActionEventEmitterEventStore($eventStore, $eventEmitter);

        foreach ($config['plugins'] as $pluginAlias) {
            $plugin = $container->get($pluginAlias);

            if (! $plugin instanceof Plugin) {
                throw ConfigurationException::configurationError(sprintf(
                    'Plugin %s does not implement the Plugin interface',
                    $pluginAlias
                ));
            }

            $plugin->attachToEventStore($wrapper);
        }

        if (count($config['metadata_enrichers']) > 0) {
            $metadataEnrichers = [];

            foreach ($config['metadata_enrichers'] as $metadataEnricherAlias) {
                $metadataEnricher = $container->get($metadataEnricherAlias);

                if (! $metadataEnricher instanceof MetadataEnricher) {
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

            $plugin->attachToEventStore($wrapper);
        }

        return $wrapper;
    }

    /**
     * {@inheritdoc}
     */
    public function dimensions(): iterable
    {
        return ['prooph', 'event_store'];
    }

    /**
     * {@inheritdoc}
     */
    public function defaultOptions(): iterable
    {
        return [
            'metadata_enrichers' => [],
            'plugins' => [],
            'wrap_action_event_emitter' => true,
        ];
    }
}
