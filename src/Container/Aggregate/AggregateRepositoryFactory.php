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

namespace Prooph\EventStore\Container\Aggregate;

use Interop\Config\ConfigurationTrait;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Aggregate\Exception\InvalidArgumentException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\Stream\StreamName;

/**
 * Creates aggregate repository classes
 */
final class AggregateRepositoryFactory implements RequiresConfigId, RequiresMandatoryOptions
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
     *     'your_aggregate_class' => [AggregateRepositoryFactory::class, 'your_aggregate_class'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments) : AggregateRepository
    {
        if (!isset($arguments[0]) || !$arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }
        return (new static($name))->__invoke($arguments[0]);
    }

    /**
     * @param string $configId
     */
    public function __construct(string $configId)
    {
        $this->configId = $configId;
    }

    /**
     * @throws ConfigurationException
     */
    public function __invoke(ContainerInterface $container) : AggregateRepository
    {
        $config = $container->get('config');
        $config = $this->options($config, $this->configId);

        $repositoryClass = $config['repository_class'];

        if (! class_exists($repositoryClass)) {
            throw ConfigurationException::configurationError(sprintf('Repository class %s cannot be found', $repositoryClass));
        }

        if (! is_subclass_of($repositoryClass, AggregateRepository::class)) {
            throw ConfigurationException::configurationError(sprintf('Repository class %s must be a sub class of %s', $repositoryClass, AggregateRepository::class));
        }

        $eventStore = $container->get(EventStore::class);
        $aggregateType = AggregateType::fromAggregateRootClass($config['aggregate_type']);
        $aggregateTranslator = $container->get($config['aggregate_translator']);

        $snapshotStore = isset($config['snapshot_store'])? $container->get($config['snapshot_store']) : null;

        $streamName = isset($config['stream_name'])? new StreamName($config['stream_name']) : null;

        $oneStreamPerAggregate = isset($config['one_stream_per_aggregate'])? (bool)$config['one_stream_per_aggregate'] : false;

        return new $repositoryClass($eventStore, $aggregateType, $aggregateTranslator, $snapshotStore, $streamName, $oneStreamPerAggregate);
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
            'repository_class',
            'aggregate_type',
            'aggregate_translator',
        ];
    }
}
