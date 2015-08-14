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
use Interop\Container\ContainerInterface;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Configuration\Exception\ConfigurationException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\Feature;

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
     * @var ContainerInterface
     */
    protected $featureManager;

    /**
     * @var ActionEventEmitter
     */
    protected $actionEventEmitter;

    /**
     * @var array
     */
    protected $featureList = array();

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var MessageConverter
     */
    protected $messageConverter;

    /**
     * @param array $config
     */
    public function __construct(array $config = null)
    {
        if (is_array($config)) {            
            $this->config = $config;
        }

        if (isset($config['feature_manager'])) {
            Assertion::isInstanceOf($config['feature_manager'], ContainerInterface::class);

            $this->featureManager = $config['feature_manager'];
        }

        if (isset($config['features'])) {
            Assertion::isArray($config['features'], "EventStore.Configuration.features must be an array");

            $this->featureList = $config['features'];
        }

        if (isset($config['action_event_emitter'])) {
            Assertion::isInstanceOf($config['action_event_emitter'], ActionEventEmitter::class);

            $this->actionEventEmitter = $config['action_event_emitter'];
        }

        if (isset($config['message_factory'])) {
            Assertion::isInstanceOf($config['message_factory'], MessageFactory::class);

            $this->messageFactory = $config['message_factory'];
        }

        if (isset($config['message_converter'])) {
            Assertion::isInstanceOf($config['message_converter'], MessageConverter::class);

            $this->messageConverter = $config['message_converter'];
        }
    }

    /**
     * @param EventStore $eventStore
     * @throws Exception\ConfigurationException
     */
    public function setUpEventStoreEnvironment(EventStore $eventStore)
    {
        if ($this->featureManager === null) {
            return;
        }

        foreach ($this->featureList as $featureAlias) {
            $feature = $this->featureManager->get($featureAlias);

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

            if (!isset($adapterConfig['message_factory'])) {
                $adapterConfig['message_factory'] = $this->getMessageFactory();
            }

            if (!isset($adapterConfig['message_converter'])) {
                $adapterConfig['message_converter'] = $this->getMessageConverter();
            }
            
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
     * @param ContainerInterface $featureManager
     */
    public function setFeatureManager(ContainerInterface $featureManager)
    {
        $this->featureManager = $featureManager;
    }

    /**
     * @return ActionEventEmitter
     */
    public function getActionEventEmitter()
    {
        if (is_null($this->actionEventEmitter)) {
            $this->actionEventEmitter = new ProophActionEventEmitter();
        }

        return $this->actionEventEmitter;
    }

    /**
     * @param ActionEventEmitter $actionEventEmitter
     */
    public function setActionEventEmitter(ActionEventEmitter $actionEventEmitter)
    {
        $this->actionEventEmitter = $actionEventEmitter;
    }

    /**
     * @return MessageFactory
     */
    public function getMessageFactory()
    {
        if (is_null($this->messageFactory)) {
            $this->messageFactory = new FQCNMessageFactory();
        }

        return $this->messageFactory;
    }

    /**
     * @param MessageFactory $messageFactory
     */
    public function setMessageFactory(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return MessageConverter
     */
    public function getMessageConverter()
    {
        if (is_null($this->messageConverter)) {
            $this->messageConverter = new NoOpMessageConverter();
        }

        return $this->messageConverter;
    }

    /**
     * @param MessageConverter $messageConverter
     */
    public function setMessageConverter(MessageConverter $messageConverter)
    {
        $this->messageConverter = $messageConverter;
    }
}
