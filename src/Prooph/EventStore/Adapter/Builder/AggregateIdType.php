<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 17.04.14 - 22:44
 */

namespace Prooph\EventStore\Adapter\Builder;

/**
 * Interface AggregateIdType
 *
 * @package Prooph\EventStore\Adapter\Builder
 * @author Alexander Miertsch <contact@prooph.de>
 */
interface AggregateIdType 
{
    /**
     * @param mixed $aggregateId
     * @return string
     */
    public function convertToString($aggregateId);
}
 