<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 20.04.14 - 23:56
 */

namespace Prooph\EventStore\Feature;

use Prooph\Common\ServiceLocator\ServiceLocator;
use Prooph\EventStore\Exception\RuntimeException;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception;

/**
 * Class ZF2FeatureManager
 *
 * @method Feature get($name) Get Feature by name or alias
 *
 * @package Prooph\EventStore\Feature
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ZF2FeatureManager extends AbstractPluginManager
{
    /**
     * Validate the plugin
     *
     * Checks that the plugin loaded is a Feature
     * of FilterInterface.
     *
     * @param  mixed $plugin
     * @return void
     * @throws \Prooph\EventStore\Exception\RuntimeException if invalid
     */
    public function validatePlugin($plugin)
    {
        if (! $plugin instanceof Feature) {
            throw new RuntimeException(sprintf(
                'Feature must be instance of Prooph\EventStore\Feature\FeatureInterface,'
                . 'instance of type %s given',
                ((is_object($plugin)? get_class($plugin) : gettype($plugin)))
            ));
        }
    }
}
 