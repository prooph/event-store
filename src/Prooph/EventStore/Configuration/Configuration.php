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
use Prooph\EventStore\Mapping\AggregateRootPrototypeManager;
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
            
            if (isset($config['repository_map'])) {
                //Set map again to trigger validation
                $this->setRepositoryMap($config['repository_map']);
            }
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
                throw ConfigurationException::configurationError('Missing key adapter');
            }
            
            if (!is_array($this->config['adapter'])) {
                throw ConfigurationException::configurationError('Adapter configuration must be an array');
            }
            
            $adapterClass = key($this->config['adapter']);
            $adapterConfig = current($this->config['adapter']);
            
            if (!is_string($adapterClass)) {
                throw ConfigurationException::configurationError('AdapterClass must be a string');
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
    
    /**
     * Get map of $sourceFQCNs to $repositoryFQCNs
     * 
     * @return array
     */
    public function getRepositoryMap()
    {
        return (isset($this->config['repository_map']))? $this->config['repository_map'] : array();
    }
    
    /**
     * @param array $map
     */
    public function setRepositoryMap(array $map)
    {
        foreach ($map as $aggregateFQCN => $repositoryFQCN) {
            $this->addRepositoryMapping($aggregateFQCN, $repositoryFQCN);
        }
    }
    
    /**
     * @param string $aggregateFQCN
     * @param string $repositoryFQCN
     * @throws ConfigurationException
     */
    public function addRepositoryMapping($aggregateFQCN, $repositoryFQCN)
    {
        if (!class_exists($aggregateFQCN)) {
            throw ConfigurationException::configurationError(sprintf(
                'Unknown SourceClass: %s',
                $aggregateFQCN
            ));
        }
        
        if (!class_exists($repositoryFQCN)) {
            throw ConfigurationException::configurationError(sprintf(
                'Unknown RepositoryClass: %s',
                $repositoryFQCN
            ));
        }
        
        if (!isset($this->config['repository_map']) || !is_array($this->config['repository_map'])) {
            $this->config['repository_map'] = array();
        }
        
        $this->config['repository_map'][$aggregateFQCN] = $repositoryFQCN;
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
