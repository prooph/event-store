<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStore\Util;

use ArrayIterator;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Util\CompositeIterator;
use ProophTest\EventStore\Mock\TestIteratorAggregate;

/**
 * Class CompositeIteratorTest
 * @package ProophTest\EventStore\Util
 */
final class CompositeIteratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_iterator_in_correct_order(): void
    {
        $a1 = [1, 2, 3];
        $a2 = [4, 7, 10];
        $a3 = [5, 6, 8, 9];

        $it1 = new ArrayIterator($a1);
        $it2 = new ArrayIterator($a2);
        $it3 = new ArrayIterator($a3);

        $compositeIterator = new CompositeIterator([$it1, $it2, $it3], function ($v1, $v2) {
            if (null === $v1) {
                return true;
            }
            return $v1 > $v2;
        });

        $result = [];

        while ($compositeIterator->valid()) {
            $result[] = $compositeIterator->current();
            $compositeIterator->next();
        }

        $compositeIterator->key();
        $compositeIterator->rewind();

        while ($compositeIterator->valid()) {
            $result[] = $compositeIterator->current();
            $compositeIterator->next();
        }

        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $result);
    }

    /**
     * @test
     */
    public function it_cannot_construct_without_iterators(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No iterators given');

        new CompositeIterator([], 'substr');
    }

    /**
     * @test
     */
    public function it_required_iterators(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an array of Iterator or IteratorAggregate');

        new CompositeIterator(['foo'], 'substr');
    }

    /**
     * @test
     */
    public function it_accepts_iterator_aggregate(): void
    {
        new CompositeIterator([new TestIteratorAggregate()], 'substr');
    }
}
