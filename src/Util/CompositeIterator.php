<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 06.10.15 - 17:33
 */

namespace Prooph\EventStore\Util;

use Iterator;
use IteratorAggregate;
use Prooph\EventStore\Exception;

/**
 * Class CompositeIterator
 * @package Prooph\EventStore\Util
 */
final class CompositeIterator implements Iterator
{
    /**
     * @var Iterator[]
     */
    private $iterators;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @param Iterator[]|IteratorAggregate[] $iterators
     * @param callable $callback
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $iterators, callable $callback)
    {
        if (empty($iterators)) {
            throw new Exception\InvalidArgumentException('No iterators given');
        }

        foreach ($iterators as $iterator) {
            if ($iterator instanceof IteratorAggregate) {
                $iterator = $iterator->getIterator();
            }

            if (! $iterator instanceof Iterator) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Expected an array of %s or %s',
                    Iterator::class,
                    IteratorAggregate::class
                ));
            }

            $this->iterators[] = $iterator;
        }

        $this->callback = $callback;
    }

    /**
     * @return Iterator
     */
    private function nextIterator()
    {
        $current = null;
        $nextIterator = $this->iterators[0];

        foreach ($this->iterators as $iterator) {
            if ($iterator->valid() && call_user_func($this->callback, $current, $iterator->current())) {
                $current = $iterator->current();
                $nextIterator = $iterator;
            }
        }

        return $nextIterator;
    }

    /**
     * Return the current element
     * @return mixed
     */
    public function current()
    {
        return $this->nextIterator()->current();
    }

    /**
     * Move forward to next element
     * @return void
     */
    public function next()
    {
        $this->nextIterator()->next();
    }

    /**
     * Return the key of the current element
     * Note: You should not rely on the key, unless the key tracks information from which iterator it comes
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->nextIterator()->key();
    }

    /**
     * Checks if current position is valid
     * @return boolean
     */
    public function valid()
    {
        return $this->nextIterator()->valid();
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        foreach ($this->iterators as $iterator) {
            $iterator->rewind();
        }
    }
}
