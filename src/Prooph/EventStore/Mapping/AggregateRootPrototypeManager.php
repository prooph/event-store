<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.05.14 - 20:38
 */

namespace Prooph\EventStore\Mapping;

use Prooph\EventStore\EventSourcing\EventSourcedAggregateRoot;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception;

/**
 * Class AggregateRootPrototypeManager
 *
 * @package Prooph\EventStore\Mapping
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateRootPrototypeManager extends AbstractPluginManager
{
    /**
     * @param ConfigInterface $configuration
     */
    public function __construct(ConfigInterface $configuration = null)
    {
        parent::__construct($configuration);

        $this->addAbstractFactory(new FQCNPrototypeFactory());
    }

    /**
     * Validate the plugin
     *
     * Checks that the AggregateRoot loaded is an instance of EventSourcedAggregateRoot
     *
     * @param  mixed $plugin
     * @return void
     * @throws \RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if (!$plugin instanceof EventSourcedAggregateRoot) {
            throw new \RuntimeException(
                sprintf(
                    "AggregateRoot of type %s is not an instance of EventSourcedAggregateRoot",
                    (is_object($plugin))? get_class($plugin) : gettype($plugin)
                )
            );
        }
    }
}
 