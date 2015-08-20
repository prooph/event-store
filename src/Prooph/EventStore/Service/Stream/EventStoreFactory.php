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

namespace Prooph\EventStore\Service\Stream;

use Interop\Container\ContainerInterface;
use Prooph\Common\ServiceLocator\ZF2\Zf2ServiceManagerProxy;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\Configuration\Exception\ConfigurationException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\ZF2FeatureManager;
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
        $config = $container->get('configuration');

        if (! isset($config['proophessor'])) {
            throw ConfigurationException::configurationError('Missing proophessor config key in application config');
        }

        if (! isset($config['proophessor']['event_store'])) {
            throw ConfigurationException::configurationError('Missing key event_store in proophessor configuration');
        }

        $config = $config['proophessor']['event_store'];

        if (! isset($config['adapter'])) {
            throw ConfigurationException::configurationError('Missing adapter configuration in proophessor event_store configuration');
        }

        $adapterConfig = $config['adapter'];

        if (! isset($adapterConfig['type'])) {
            throw ConfigurationException::configurationError('Missing adapter type configuration in proophessor event_store configuration');
        }

        $adapterType    = $adapterConfig['type'];
        $adapterOptions = isset($adapterConfig['options'])? $adapterConfig['options'] : [];

        //Check if we have to use the application wide database connection
        if ($adapterType == 'Prooph\EventStore\Adapter\Doctrine\DoctrineEventStoreAdapter'
            && !isset($adapterOptions['connection'])
            && isset($adapterOptions['doctrine_connection_alias'])
        ) {
            $config['adapter']['options']['connection'] = $container->get('doctrine.connection.' . $adapterOptions['doctrine_connection_alias']);
        } elseif ($adapterType == 'Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter'
            && isset($adapterOptions['zend_db_adapter'])
            && is_string($adapterOptions['zend_db_adapter'])
        ) {
            $config['adapter']['options']['zend_db_adapter'] = $container->get($adapterOptions['zend_db_adapter']);
        } elseif ($adapterType == 'Prooph\EventStore\Adapter\MongoDb\MongoDbEventStoreAdapter'
            && !isset($adapterOptions['mongo_client'])
        ) {
            isset($adapterOptions['mongo_connection_alias'])
                ? $config['adapter']['options']['mongo_client'] = $container->get($adapterOptions['mongo_connection_alias'])
                : $config['adapter']['options']['mongo_client'] = new \MongoClient();
        }

        $featureManagerConfig = null;

        if (isset($config['feature_manager'])) {
            $featureManagerConfig = new Configuration($config['feature_manager']);
            unset($config['feature_manager']);
        }

        $featureManager = new ZF2FeatureManager($featureManagerConfig);
        /* @todo: ContainerInteropProxy ??? */
        $featureManager->setServiceLocator($container);

        $esConfiguration = new Configuration($config);
        $esConfiguration->setFeatureManager(Zf2ServiceManagerProxy::proxy($featureManager));

        return new EventStore($esConfiguration);
    }
}
