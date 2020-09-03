<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2020 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\StreamIterator;

class MergedStreamIterator implements StreamIterator
{
    use TimSort;

    /**
     * @var array
     */
    private $iterators = [];

    /**
     * @var int
     */
    private $numberOfIterators;

    /**
     * @var array
     */
    private $originalIteratorOrder;

    public function __construct(array $streamNames, StreamIterator ...$iterators)
    {
        foreach ($iterators as $key => $iterator) {
            $this->iterators[$key][0] = $iterator;
            $this->iterators[$key][1] = $streamNames[$key];
        }
        $this->numberOfIterators = \count($this->iterators);
        $this->originalIteratorOrder = $this->iterators;

        $this->prioritizeIterators();
    }

    public function rewind(): void
    {
        foreach ($this->iterators as $iter) {
            $iter[0]->rewind();
        }

        $this->prioritizeIterators();
    }

    public function valid(): bool
    {
        foreach ($this->iterators as $key => $iterator) {
            if ($iterator[0]->valid()) {
                return true;
            }
        }

        return false;
    }

    public function next(): void
    {
        // only advance the prioritized iterator
        $this->iterators[0][0]->next();

        $this->prioritizeIterators();
    }

    public function current()
    {
        return $this->iterators[0][0]->current();
    }

    public function streamName(): string
    {
        return $this->iterators[0][1];
    }

    public function key(): int
    {
        return $this->iterators[0][0]->key();
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->iterators as $iterator) {
            $count += \count($iterator[0]);
        }

        return $count;
    }

    /**
     * Will prioritize iterators via sorting them according to;
     *
     * 1) invalid iterators are placed last.
     * 2) by comparing the createdAt of the current prooph message of the iterator.
     */
    private function prioritizeIterators(): void
    {
        if ($this->numberOfIterators > 1) {
            $this->iterators = $this->originalIteratorOrder;

            $this->timSort($this->iterators, $this->numberOfIterators);
        }
    }
}
