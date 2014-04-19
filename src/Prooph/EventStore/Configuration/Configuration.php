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
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
/**
 * Configuration
 * 
 * @author Alexander Miertsch <contact@prooph.de>
 */
class Configuration
{
    protected $config = array();
    
    protected $adapter;


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
}
