<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 20.10.14 - 20:56
 */

namespace Prooph\EventStore\Stream\MappedSuperclass;

use Prooph\EventStore\Aggregate\AggregateType;

/**
 * Class SuperclassAggregateTypeChanger
 *
 * This class is a helper for the MappedSuperclassStreamStrategy. It uses the decorator pattern to access the
 * protected aggregateType property of an AggregateType and replace the value with the given subclass value.
 * The AggregateType object reference is kept but the type is changed silently.
 *
 * Don't use this class outside of the MappedSuperclassStreamStrategy!
 *
 * @package Prooph\EventStore\Stream\MappedSuperclass
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class SuperclassAggregateTypeChanger extends AggregateType
{
    /**
     * @param AggregateType $superclass
     * @param $subclass
     */
    public function convertToSubclassAggregateType(AggregateType $superclass, $subclass)
    {
        $superclass->aggregateType = $subclass;
    }
}
 