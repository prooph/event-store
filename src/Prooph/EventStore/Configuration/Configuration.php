<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Configuration;

use Prooph\EventStore\Adapter\AdapterInterface;
use Prooph\EventStore\Configuration\Exception\ConfigurationException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\FeatureManager;
use Zend\ServiceManager\Config;

/**
 * Configuration
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 */
class Configuration
{
    /**
     * @var array
     */
    protected $config = array();

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var FeatureManager
     */
    protected $featureManager;

    /**
     * @var array
     */
    protected $featureList = array();

    /**
     * @param array $config
     */
    public function __construct(array $config = null)
    {
        if (is_array($config)) {            
            $this->config = $config;
        }

        if (isset($config['feature_manager'])) {
            \Assert\that($config['feature_manager'])->isArray("EventStore.Configuration.feature_manager must be an array");

            $this->featureManager = new FeatureManager(new Config($config['feature_manager']));
        }

        if (isset($config['features'])) {
            \Assert\that($config['features'])->isArray("EventStore.Configuration.features must be an array");

            $this->featureList = $config['features'];
        }
    }

    /**
     * @param EventStore $eventStore
     */
    public function setUpEventStoreEnvironment(EventStore $eventStore)
    {
        foreach ($this->featureList as $featureAlias) {
            $feature = $this->getFeatureManager()->get($featureAlias);

            $feature->setUp($eventStore);
        }
    }
    
    /**
     * @return AdapterInterface
     * @throws ConfigurationException
     */
    public function getAdapter()
    {
        if (is_null($this->adapter)) {
            if (!isset($this->config['adapter'])) {
                throw ConfigurationException::configurationError('Missing key adapter in event store configuration');
            }

            if (is_object($this->config['adapter']) && $this->config['adapter'] instanceof AdapterInterface) {
                $this->adapter = $this->config['adapter'];
                return $this->config['adapter'];
            }
            
            if (!is_array($this->config['adapter'])) {
                throw ConfigurationException::configurationError('Event store adapter configuration must be an array');
            }

            if (!isset($this->config['adapter']['type'])) {
                throw ConfigurationException::configurationError('Missing key type in event store adapter configuration.');
            }

            if (!isset($this->config['adapter']['options'])) {
                $this->config['adapter']['options'] = array();
            }
            
            $adapterClass = $this->config['adapter']['type'];
            $adapterConfig = $this->config['adapter']['options'];
            
            if (!is_string($adapterClass)) {
                throw ConfigurationException::configurationError('Adapter.type must be a string');
            }
            
            if (!class_exists($adapterClass)) {
                throw ConfigurationException::configurationError(sprintf(
                    'Unknown AdapterClass: %s',
                    $adapterClass
                ));
            }
            
            $this->adapter = new $adapterClass($adapterConfig); 
            
            if (!$this->adapter instanceof AdapterInterface) {
                throw ConfigurationException::configurationError('EventStore Adapter must be instance of Prooph\EventStore\Adapter\AdapterInterface');
            }
        } 
        
        return $this->adapter;
    }

    /**
     * Set the active adapter
     *
     * @param AdapterInterface $adapter
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getFeatureManager()
    {
        if (is_null($this->featureManager)) {
            $this->featureManager = new FeatureManager();
        }

        return $this->featureManager;
    }

    /**
     * @param FeatureManager $featureManager
     */
    public function setFeatureManager(FeatureManager $featureManager)
    {
        $this->featureManager = $featureManager;
    }
}
