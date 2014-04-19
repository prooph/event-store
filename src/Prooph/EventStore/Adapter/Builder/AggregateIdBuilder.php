<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 17.04.14 - 22:41
 */

namespace Prooph\EventStore\Adapter\Builder;

/**
 * Class AggregateIdBuilder
 *
 * @package Prooph\EventStore\Adapter\Builder
 * @author Alexander Miertsch <contact@prooph.de>
 */
class AggregateIdBuilder 
{
    protected static $typeMap = array();

    /**
     * @param string $aggregateIdFQCN
     * @param string $typeFQCN
     */
    public static function registerType($aggregateIdFQCN, $typeFQCN)
    {
        static::$typeMap[$aggregateIdFQCN] = $typeFQCN;
    }

    /**
     * @param mixed $aggregateId
     * @return string
     */
    public static function toString($aggregateId)
    {
        if (is_string($aggregateId)) {
            return $aggregateId;
        }

        if (is_object($aggregateId)) {
            if (isset(static::$typeMap[get_class($aggregateId)])) {
                $typeClass = static::$typeMap[get_class($aggregateId)];
                $type = new $typeClass();

                return $type->convertToString($aggregateId);
            }
        }

        return (string)$aggregateId;
    }
}
 