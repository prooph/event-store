<?php
/**
 * This file is part of the prooph/event-store.
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
use Interop\Config\RequiresConfigId;
use Interop\Container\ContainerInterface;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\CanControlTransactionActionEventEmitterAware;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Metadata\MetadataEnricherAggregate;
use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Prooph\EventStore\Plugin\Plugin;

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
    public static function __callStatic(string $name, array $arguments): InMemoryEventStore
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
    public function __invoke(ContainerInterface $container): InMemoryEventStore
    {
        $config = $container->get('config');
        $config = $this->options($config, $this->configId);

        if (! isset($config['event_emitter'])) {
            $eventEmitter = new ProophActionEventEmitter([
                CanControlTransactionActionEventEmitterAware::EVENT_APPEND_TO,
                CanControlTransactionActionEventEmitterAware::EVENT_CREATE,
                CanControlTransactionActionEventEmitterAware::EVENT_LOAD,
                CanControlTransactionActionEventEmitterAware::EVENT_LOAD_REVERSE,
                CanControlTransactionActionEventEmitterAware::EVENT_DELETE,
                CanControlTransactionActionEventEmitterAware::EVENT_HAS_STREAM,
                CanControlTransactionActionEventEmitterAware::EVENT_FETCH_STREAM_METADATA,
                CanControlTransactionActionEventEmitterAware::EVENT_BEGIN_TRANSACTION,
                CanControlTransactionActionEventEmitterAware::EVENT_COMMIT,
                CanControlTransactionActionEventEmitterAware::EVENT_ROLLBACK,
            ]);
        } else {
            $eventEmitter = $container->get($config['event_emitter']);
        }

        $eventStore = new InMemoryEventStore($eventEmitter);

        foreach ($config['plugins'] as $pluginAlias) {
            $plugin = $container->get($pluginAlias);

            if (! $plugin instanceof Plugin) {
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

            $plugin->setUp($eventStore);
        }

        return $eventStore;
    }

    /**
     * {@inheritdoc}
     */
    public function dimensions(): array
    {
        return ['prooph', 'event_store'];
    }

    /**
     * {@inheritdoc}
     */
    public function defaultOptions(): array
    {
        return [
            'metadata_enrichers' => [],
            'plugins' => []
        ];
    }
}
