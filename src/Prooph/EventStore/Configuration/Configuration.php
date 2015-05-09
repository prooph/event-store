<?php
/*
 * This file is part of the prooph/event-store package.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStore\Configuration;

use Assert\Assertion;
use Prooph\Common\Event\ActionEventDispatcher;
use Prooph\Common\Event\ZF2\Zf2ActionEvent;
use Prooph\Common\Event\ZF2\Zf2ActionEventDispatcher;
use Prooph\Common\ServiceLocator\ServiceLocator;
use Prooph\Common\ServiceLocator\ZF2\Zf2ServiceManagerProxy;
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Configuration\Exception\ConfigurationException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\Feature;
use Prooph\EventStore\Feature\ZF2FeatureManager;
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
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var ServiceLocator
     */
    protected $featureManager;

    /**
     * @var ActionEventDispatcher
     */
    protected $actionEventDispatcher;

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
            Assertion::isArray($config['feature_manager'], "EventStore.Configuration.feature_manager must be an array");

            $this->featureManager = new ZF2FeatureManager(new Config($config['feature_manager']));
        }

        if (isset($config['features'])) {
            Assertion::isArray($config['features'], "EventStore.Configuration.features must be an array");

            $this->featureList = $config['features'];
        }
    }

    /**
     * @param EventStore $eventStore
     * @throws Exception\ConfigurationException
     */
    public function setUpEventStoreEnvironment(EventStore $eventStore)
    {
        foreach ($this->featureList as $featureAlias) {
            $feature = $this->getFeatureManager()->get($featureAlias);

            if (! $feature instanceof Feature) {
                throw ConfigurationException::configurationError(sprintf('Feature %s does not implement the Feature interface', $featureAlias));
            }

            $feature->setUp($eventStore);
        }
    }
    
    /**
     * @return Adapter
     * @throws ConfigurationException
     */
    public function getAdapter()
    {
        if (is_null($this->adapter)) {
            if (!isset($this->config['adapter'])) {
                throw ConfigurationException::configurationError('Missing key adapter in event store configuration');
            }

            if (is_object($this->config['adapter']) && $this->config['adapter'] instanceof Adapter) {
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
            
            if (!$this->adapter instanceof Adapter) {
                throw ConfigurationException::configurationError('EventStore Adapter must be instance of Prooph\EventStore\Adapter\AdapterInterface');
            }
        } 
        
        return $this->adapter;
    }

    /**
     * Set the active adapter
     *
     * @param Adapter $adapter
     */
    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return ServiceLocator
     */
    public function getFeatureManager()
    {
        if (is_null($this->featureManager)) {
            $this->featureManager = Zf2ServiceManagerProxy::proxy(new ZF2FeatureManager());
        }

        return $this->featureManager;
    }

    /**
     * @param ServiceLocator $featureManager
     */
    public function setFeatureManager(ServiceLocator $featureManager)
    {
        $this->featureManager = $featureManager;
    }

    /**
     * @return ActionEventDispatcher
     */
    public function getActionEventDispatcher()
    {
        if (is_null($this->actionEventDispatcher)) {
            $this->actionEventDispatcher = new Zf2ActionEventDispatcher();
        }

        return $this->actionEventDispatcher;
    }

    /**
     * @param ActionEventDispatcher $actionEventDispatcher
     */
    public function setActionEventDispatcher(ActionEventDispatcher $actionEventDispatcher)
    {
        $this->actionEventDispatcher = $actionEventDispatcher;
    }
}
