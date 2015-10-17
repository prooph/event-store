<?php

namespace ProophTest\EventStore\Mock;

use ArrayIterator;
use IteratorAggregate;

/**
 * Class TestIteratorAggregate
 * @package ProophTest\EventStore\Mock
 */
final class TestIteratorAggregate implements IteratorAggregate
{
    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator([]);
    }
}
