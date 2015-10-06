<?php

namespace Prooph\EventStoreTest\Mock;

use ArrayIterator;
use IteratorAggregate;

/**
 * Class TestIteratorAggregate
 * @package Prooph\EventStoreTest\Mock
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
