<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 24.05.14 - 20:43
 */

namespace Prooph\EventStore\Mapping;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class FQCNPrototypeFactory
 *
 * @package Prooph\EventStore\Mapping
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class FQCNPrototypeFactory implements AbstractFactoryInterface
{

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return class_exists($requestedName);
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $aggregateRef = new \ReflectionClass($requestedName);

        return $aggregateRef->newInstanceWithoutConstructor();
    }
}
 