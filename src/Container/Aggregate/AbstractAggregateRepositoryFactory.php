<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/21/15 - 6:10 PM
 */
namespace Prooph\EventStore\Container\Aggregate;

use Interop\Config\ConfigurationTrait;
use Interop\Config\RequiresContainerId;
use Interop\Config\RequiresMandatoryOptions;
use Interop\Container\ContainerInterface;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\ConfigurationException;
use Prooph\EventStore\Stream\StreamName;

/**
 * Class AbstractAggregateRepositoryFactory
 *
 * @package Prooph\EventStore\Container
 */
abstract class AbstractAggregateRepositoryFactory implements RequiresContainerId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @param ContainerInterface $container
     * @throws ConfigurationException
     * @return AggregateRepository
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $config = $this->options($config);

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
    public function vendorName()
    {
        return 'prooph';
    }

    /**
     * @inheritdoc
     */
    public function packageName()
    {
        return 'event_store';
    }

    /**
     * @inheritdoc
     */
    public function mandatoryOptions()
    {
        return [
            'repository_class',
            'aggregate_type',
            'aggregate_translator',
        ];
    }
}
