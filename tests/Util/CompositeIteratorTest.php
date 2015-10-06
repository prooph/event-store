<?php

namespace ProophTest\Util;

use ArrayIterator;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Util\CompositeIterator;
use Prooph\EventStoreTest\Mock\TestIteratorAggregate;

/**
 * Class CompositeIteratorTest
 * @package ProophTest\Util
 */
final class CompositeIteratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_iterator_in_correct_order()
    {
        $a1 = [1, 2, 3];
        $a2 = [4, 7, 10];
        $a3 = [5, 6, 8, 9];

        $it1 = new ArrayIterator($a1);
        $it2 = new ArrayIterator($a2);
        $it3 = new ArrayIterator($a3);

        $compositeIterator = new CompositeIterator([$it1, $it2, $it3], function($v1, $v2) {
            if (null === $v1) {
                return true;
            }
            return $v1 > $v2;
        });

        $result = [];

        while($compositeIterator->valid()) {
            $result[] = $compositeIterator->current();
            $compositeIterator->next();
        }

        $compositeIterator->key();
        $compositeIterator->rewind();

        while($compositeIterator->valid()) {
            $result[] = $compositeIterator->current();
            $compositeIterator->next();
        }

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $result);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_cannot_construct_without_iterators()
    {
        new CompositeIterator([], 'substr');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_required_iterators()
    {
        new CompositeIterator(['foo'], 'substr');
    }

    /**
     * @test
     */
    public function it_accepts_iterator_aggregate()
    {
        new CompositeIterator([new TestIteratorAggregate()], 'substr');
    }
}
