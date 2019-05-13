<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\StreamIterator;

use Countable;
use Prooph\Common\Messaging\Message;

class MergedStreamIterator implements StreamIterator
{
    /**
     * @var StreamIterator[]
     */
    private $iterators;

    /**
     * @var integer
     */
    private $index = 0;

    public function __construct(StreamIterator ...$iterators)
    {
        $this->iterators = [];

        foreach ($iterators as $key => $iterator) {
            $this->iterators[$key] = $iterator;
        }

        $this->prioritizeIterators();
    }

    public function rewind(): void
    {
        foreach ($this->iterators as $iter) {
            $iter->rewind();
        }

        $this->index = 0;

        $this->prioritizeIterators();
    }

    public function valid(): bool
    {
        foreach ($this->iterators as $key => $iterator) {
            if ($iterator->valid()) {
                return true;
            }
        }

        return false;
    }

    public function next(): void
    {
        // only advance the prioritized iterator
        $this->iterators[\array_keys($this->iterators)[0]]->next();

        $this->index++;

        $this->prioritizeIterators();
    }

    public function current()
    {
        return $this->iterators[\array_keys($this->iterators)[0]]->current();
    }

    public function key(): int
    {
        return $this->index;
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->iterators as $iterator) {
            if ($iterator instanceof Countable) {
                $count += \count($iterator);
            } else {
                $count += \iterator_count($iterator);
            }
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
        $compareValue = function (\Iterator $iterator): \DateTimeImmutable {
            /** @var Message $message */
            $message = $iterator->current();

            return $message->createdAt();
        };

        $compareFunction = function (\Iterator $a, \Iterator $b) use ($compareValue) {
            // valid iterators should be prioritized over invalid ones
            if (! $a->valid() or ! $b->valid()) {
                return $b->valid() <=> $a->valid();
            }

            return $compareValue($a) <=> $compareValue($b);
        };

        \uasort($this->iterators, $compareFunction);
    }
}
