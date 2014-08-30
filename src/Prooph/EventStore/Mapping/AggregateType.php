<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 06.06.14 - 23:34
 */

namespace Prooph\EventStore\Stream;

/**
 * Class AggregateType
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AggregateType 
{
    /**
     * @var string
     */
    protected $aggregateType;

    public function __construct($aggregateType)
    {
        \Assert\that($aggregateType)->notEmpty()->string('AggregateType must be a string');

        $this->aggregateType = $aggregateType;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->aggregateType;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
 